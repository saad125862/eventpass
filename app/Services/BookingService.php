<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BookingException;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Reserve tickets for a user.
     *
     * Correctness under concurrency: the ticket_types rows being bought are locked
     * (SELECT ... FOR UPDATE, always in id order to avoid deadlocks) before stock is
     * counted, so two buyers can never both see the "last" ticket as free. Prices are
     * always read from the database, never from the client. An idempotency key makes
     * double-clicks and retries return the same order instead of creating a second one.
     *
     * @param  array<int, int>  $quantities  ticket_type_id => quantity
     */
    public function reserve(User $user, Event $event, array $quantities, ?string $idempotencyKey = null): Order
    {
        $quantities = array_filter(array_map('intval', $quantities), fn (int $qty) => $qty > 0);

        if ($quantities === []) {
            throw new BookingException('Choose at least one ticket.');
        }

        if (array_sum($quantities) > config('eventpass.max_tickets_per_order')) {
            throw new BookingException('You can book up to '.config('eventpass.max_tickets_per_order').' tickets per order.');
        }

        if (! $event->isPublished()) {
            throw new BookingException('This event is not open for booking.');
        }

        if ($event->starts_at->isPast()) {
            throw new BookingException('This event has already started.');
        }

        if ($idempotencyKey !== null && ($existing = $this->findByKey($user, $idempotencyKey))) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($user, $event, $quantities, $idempotencyKey) {
                /** @var Collection<int, TicketType> $types */
                $types = TicketType::query()
                    ->where('event_id', $event->id)
                    ->whereIn('id', array_keys($quantities))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($types->count() !== count($quantities)) {
                    throw new BookingException('One of the selected tickets is no longer available.');
                }

                $reserved = $this->reservedByType($types->keys()->all());
                $total = 0;

                foreach ($quantities as $typeId => $qty) {
                    $type = $types[$typeId];

                    if (! $type->isOnSale()) {
                        throw new BookingException("{$type->name} is not on sale right now.");
                    }

                    if ($qty > $type->max_per_order) {
                        throw new BookingException("You can buy up to {$type->max_per_order} × {$type->name} per order.");
                    }

                    $left = max(0, $type->quantity - ($reserved[$typeId] ?? 0));

                    if ($qty > $left) {
                        throw new BookingException($left === 0
                            ? "{$type->name} just sold out."
                            : "Only {$left} × {$type->name} left.");
                    }

                    $total += $qty * $type->price_cents;
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'status' => OrderStatus::Pending,
                    'total_cents' => $total,
                    'expires_at' => now()->addMinutes(config('eventpass.hold_minutes')),
                    'idempotency_key' => $idempotencyKey,
                ]);

                foreach ($quantities as $typeId => $qty) {
                    $order->items()->create([
                        'ticket_type_id' => $typeId,
                        'quantity' => $qty,
                        'unit_price_cents' => $types[$typeId]->price_cents,
                    ]);
                }

                return $order->load('items.ticketType', 'event');
            });
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request with the same idempotency key won the race.
            if ($idempotencyKey !== null && ($existing = $this->findByKey($user, $idempotencyKey))) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Units currently taken per ticket type: paid orders plus unexpired pending holds.
     *
     * @param  array<int, int>  $typeIds
     * @return array<int, int> ticket_type_id => reserved units
     */
    public function reservedByType(array $typeIds, ?int $exceptOrderId = null): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.ticket_type_id', $typeIds)
            ->when($exceptOrderId, fn ($q) => $q->where('orders.id', '!=', $exceptOrderId))
            ->where(function ($q) {
                $q->where('orders.status', OrderStatus::Paid->value)
                    ->orWhere(function ($q) {
                        $q->where('orders.status', OrderStatus::Pending->value)
                            ->where('orders.expires_at', '>', now());
                    });
            })
            ->groupBy('order_items.ticket_type_id')
            ->selectRaw('order_items.ticket_type_id as type_id, sum(order_items.quantity) as reserved')
            ->pluck('reserved', 'type_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Mark pending orders whose hold has run out as expired. Returns how many were released. */
    public function releaseExpired(): int
    {
        return Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => OrderStatus::Expired->value]);
    }

    public function cancel(Order $order): void
    {
        if ($order->isPending()) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }
    }

    private function findByKey(User $user, string $key): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $key)
            ->first();
    }
}
