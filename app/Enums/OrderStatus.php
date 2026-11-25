<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Tailwind classes for the status badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-sun/15 text-sun ring-sun/30',
            self::Paid => 'bg-mint/15 text-mint ring-mint/30',
            self::Cancelled, self::Expired => 'bg-white/5 text-ink-300 ring-white/10',
        };
    }
}
