<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Mail\EventReminder;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'eventpass:send-reminders';

    protected $description = 'Email attendees whose event starts within the next 24 hours';

    public function handle(): int
    {
        $sent = 0;

        Order::query()
            ->where('status', OrderStatus::Paid->value)
            ->whereNull('reminder_sent_at')
            ->whereHas('event', fn ($q) => $q->whereBetween('starts_at', [now(), now()->addHours(24)]))
            ->with('user')
            ->chunkById(100, function ($orders) use (&$sent) {
                foreach ($orders as $order) {
                    Mail::to($order->user)->queue(new EventReminder($order->id));
                    $order->update(['reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Queued {$sent} reminder ".($sent === 1 ? 'email' : 'emails').'.');

        return self::SUCCESS;
    }
}
