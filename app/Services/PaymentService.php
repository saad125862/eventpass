<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly BookingService $booking,
    ) {}

    /** Charge the order's card through the gateway, then confirm it. */
    public function pay(Order $order, array $card): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        if (! $order->holdIsActive()) {
            throw new PaymentException('Your reservation has expired. Please pick your tickets again.');
        }

        $reference = $this->gateway->charge($order, $card);

        return $this->markPaid($order, $reference);
    }

    /**
     * Confirm payment and issue tickets. Safe to call many times (webhook retries,
     * double submits): a paid order is returned unchanged and tickets are created once.
     */
    public function markPaid(Order $order, string $reference): Order
    {
        $justPaid = false;

        $paid = DB::transaction(function () use ($order, $reference, &$justPaid) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            if ($locked->status === OrderStatus::Cancelled) {
                throw new PaymentException('This order was cancelled.');
            }

            $locked->load('items');

            // A late confirmation (hold already released) may only succeed if stock is still free.
            if (! $locked->holdIsActive()) {
                $typeIds = $locked->items->pluck('ticket_type_id')->all();
                $types = TicketType::query()->whereIn('id', $typeIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $reserved = $this->booking->reservedByType($typeIds, $locked->id);

                foreach ($locked->items as $item) {
                    $left = $types[$item->ticket_type_id]->quantity - ($reserved[$item->ticket_type_id] ?? 0);

                    if ($item->quantity > $left) {
                        throw new PaymentException('Your reservation expired and those tickets are no longer available.');
                    }
                }
            }

            $locked->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'payment_reference' => $reference,
            ]);

            foreach ($locked->items as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    Ticket::create([
                        'order_id' => $locked->id,
                        'ticket_type_id' => $item->ticket_type_id,
                        'event_id' => $locked->event_id,
                        'user_id' => $locked->user_id,
                        'code' => Ticket::generateCode(),
                    ]);
                }
            }

            $justPaid = true;

            return $locked;
        });

        if ($justPaid) {
            // Delivered by the queue worker so the checkout response stays fast.
            Mail::to($paid->user)->queue(new OrderConfirmed($paid->id));
        }

        return $paid;
    }
}
