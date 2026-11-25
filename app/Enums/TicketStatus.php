<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Valid = 'valid';
    case Used = 'used';
    case Void = 'void';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Valid => 'bg-mint/15 text-mint ring-mint/30',
            self::Used => 'bg-brand-500/15 text-brand-300 ring-brand-400/30',
            self::Void => 'bg-flame/15 text-flame ring-flame/30',
        };
    }
}
