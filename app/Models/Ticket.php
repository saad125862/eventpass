<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $order_id
 * @property int $ticket_type_id
 * @property int $event_id
 * @property int $user_id
 * @property string $code
 * @property TicketStatus $status
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property-read Order $order
 * @property-read TicketType $ticketType
 * @property-read Event $event
 * @property-read User $user
 */
class Ticket extends Model
{
    protected $fillable = [
        'order_id', 'ticket_type_id', 'event_id', 'user_id', 'code', 'status',
        'checked_in_at', 'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'checked_in_at' => 'datetime',
        ];
    }

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

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /** Unguessable 32-char token; this is what the QR code carries. */
    public static function generateCode(): string
    {
        return Str::lower(Str::random(32));
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** Short, human-friendly reference printed under the QR code. */
    public function shortCode(): string
    {
        return strtoupper(substr($this->code, 0, 4).'-'.substr($this->code, 4, 4));
    }
}
