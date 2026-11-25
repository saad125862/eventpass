<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $ticket_type_id
 * @property int $quantity
 * @property int $unit_price_cents
 * @property-read Order $order
 * @property-read TicketType $ticketType
 */
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'ticket_type_id', 'quantity', 'unit_price_cents'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function subtotalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }
}
