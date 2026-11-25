<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int $event_id
 * @property OrderStatus $status
 * @property int $total_cents
 * @property Carbon|null $expires_at
 * @property string|null $idempotency_key
 * @property string|null $payment_reference
 * @property Carbon|null $paid_at
 * @property Carbon|null $reminder_sent_at
 * @property-read User $user
 * @property-read Event $event
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, Ticket> $tickets
 */
class Order extends Model
{
    use HasUlids;

    protected $fillable = [
        'code', 'user_id', 'event_id', 'status', 'total_cents', 'expires_at',
        'idempotency_key', 'payment_reference', 'paid_at', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * The public reference is the ULID column, not the integer primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['code'];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    /** True while a pending order's stock hold is still valid. */
    public function holdIsActive(): bool
    {
        return $this->isPending() && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function quantity(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
