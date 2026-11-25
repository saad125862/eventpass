<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ReleaseExpiredOrders extends Command
{
    protected $signature = 'eventpass:release-expired';

    protected $description = 'Release seats held by pending orders whose checkout window has expired';

    public function handle(BookingService $booking): int
    {
        $released = $booking->releaseExpired();

        $this->info("Released {$released} expired ".($released === 1 ? 'order' : 'orders').'.');

        return self::SUCCESS;
    }
}
