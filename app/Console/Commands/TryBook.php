<?php

namespace App\Console\Commands;

use App\Exceptions\BookingException;
use App\Models\Event;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Console\Command;

/**
 * Load-test helper: attempt one booking and print OK or SOLDOUT.
 * The concurrency test launches many of these at once against a real Postgres database.
 */
class TryBook extends Command
{
    protected $signature = 'eventpass:try-book {event} {ticketType} {user} {qty=1}';

    protected $description = 'Attempt a single booking (used by the concurrency test / load tests)';

    protected $hidden = true;

    public function handle(BookingService $booking): int
    {
        try {
            $order = $booking->reserve(
                User::query()->findOrFail($this->argument('user')),
                Event::query()->findOrFail($this->argument('event')),
                [(int) $this->argument('ticketType') => (int) $this->argument('qty')],
            );
            $this->line('OK '.$order->code);
        } catch (BookingException $e) {
            $this->line('SOLDOUT '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
