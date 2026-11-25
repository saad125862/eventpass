<?php

namespace App\Support;

final class Money
{
    /** Format integer cents as a USD string, e.g. 2500 => "$25.00" (free when zero). */
    public static function format(int $cents, bool $freeLabel = true): string
    {
        if ($cents === 0 && $freeLabel) {
            return 'Free';
        }

        return '$'.number_format($cents / 100, 2);
    }
}
