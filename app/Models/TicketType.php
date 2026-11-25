<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $description
 * @property int $price_cents
 * @property int $quantity
 * @property int $max_per_order
 * @property Carbon|null $sale_starts_at
 * @property Carbon|null $sale_ends_at
 * @property int $sort
 * @property int|string|null $reserved_count Present when loaded through withReserved()
 * @property-read Event $event
 */
class TicketType extends Model
{
    protected $fillable = [
        'event_id', 'name', 'description', 'price_cents', 'quantity', 'max_per_order',
        'sale_starts_at', 'sale_ends_at', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Adds `reserved_count`: units in paid orders plus units held by unexpired pending orders.
     *
     * @param  Builder<TicketType>  $query
     * @return Builder<TicketType>
     */
    public function scopeWithReserved(Builder $query): Builder
    {
        return $query->select('ticket_types.*')->selectSub(
            DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereColumn('order_items.ticket_type_id', 'ticket_types.id')
                ->where(function ($q) {
                    $q->where('orders.status', OrderStatus::Paid->value)
                        ->orWhere(function ($q) {
                            $q->where('orders.status', OrderStatus::Pending->value)
                                ->where('orders.expires_at', '>', now());
                        });
                })
                ->selectRaw('coalesce(sum(order_items.quantity), 0)'),
            'reserved_count'
        );
    }

    /** Remaining units. Requires the withReserved() scope (falls back to a query). */
    public function available(): int
    {
        $reserved = $this->getAttribute('reserved_count');

        if ($reserved === null) {
            $reserved = (int) static::query()->withReserved()->whereKey($this->getKey())->value('reserved_count');
        }

        return max(0, $this->quantity - (int) $reserved);
    }

    public function isOnSale(): bool
    {
        $now = now();

        return ($this->sale_starts_at === null || $this->sale_starts_at->lte($now))
            && ($this->sale_ends_at === null || $this->sale_ends_at->gte($now));
    }

    /** 0-100 percentage of stock already taken. */
    public function soldPercent(): int
    {
        if ($this->quantity === 0) {
            return 100;
        }

        return (int) round((($this->quantity - $this->available()) / $this->quantity) * 100);
    }
}
