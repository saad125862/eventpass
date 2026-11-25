<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

/**
 * The headline guarantee: N buyers race for M < N tickets and exactly M succeed.
 *
 * This needs real row locks and several OS processes, so it only runs against PostgreSQL
 * (it is skipped on the default in-memory SQLite). CI runs it against a Postgres service.
 */
class ConcurrentBookingTest extends TestCase
{
    use BuildsEvents, DatabaseMigrations;

    public function test_fifty_buyers_racing_for_ten_tickets_sell_exactly_ten(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Concurrency test needs PostgreSQL (row-level locks).');
        }

        $buyers = 50;
        $stock = 10;

        $event = $this->event(types: [['Last chance', 10, $stock]]);
        $type = $event->ticketTypes->first();
        $users = collect(range(1, $buyers))->map(fn () => $this->user());

        $db = config('database.connections.pgsql');
        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => $db['database'], 'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password'],
            'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
        ];

        // Start every process first, then wait, so the bookings genuinely overlap.
        $processes = $users->map(function ($user) use ($event, $type, $env) {
            $process = new Process([PHP_BINARY, base_path('artisan'), 'eventpass:try-book', $event->id, $type->id, $user->id, 1], base_path(), $env);
            $process->start();

            return $process;
        });

        $outputs = $processes->map(function (Process $p) {
            $p->wait();

            return trim($p->getOutput());
        });

        $this->assertSame($stock, $outputs->filter(fn ($o) => str_starts_with($o, 'OK'))->count(), 'Exactly the available stock must sell. Output: '.$outputs->implode(' | '));
        $this->assertSame($buyers - $stock, $outputs->filter(fn ($o) => str_starts_with($o, 'SOLDOUT'))->count());

        $this->assertSame($stock, Order::where('status', OrderStatus::Pending->value)->count());
        $this->assertSame(0, TicketType::query()->withReserved()->find($type->id)->available());
    }
}
