<?php

use Illuminate\Support\Facades\Schedule;

// Give seats back when a checkout hold runs out (checkout also releases lazily on page load).
Schedule::command('eventpass:release-expired')->everyMinute()->withoutOverlapping();

// Email attendees the day before their event.
Schedule::command('eventpass:send-reminders')->hourly()->withoutOverlapping();

// Keep the queue table and stale sessions tidy.
Schedule::command('queue:prune-failed --hours=168')->daily();
