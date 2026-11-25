<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $eventIds = Event::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->pluck('id');

        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->with(['ticketTypes' => fn ($q) => $q->withReserved()])
            ->orderByDesc('starts_at')
            ->get();

        $paid = Order::query()->whereIn('event_id', $eventIds)->where('status', OrderStatus::Paid->value);

        $revenue = (int) (clone $paid)->sum('total_cents');
        $sold = Ticket::query()->whereIn('event_id', $eventIds)->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])->count();
        $checkedIn = Ticket::query()->whereIn('event_id', $eventIds)->where('status', TicketStatus::Used->value)->count();

        // Revenue per day for the last 14 days (grouped in PHP so it works on any database).
        $since = now()->subDays(13)->startOfDay();
        $byDay = (clone $paid)->where('paid_at', '>=', $since)->get(['paid_at', 'total_cents'])
            ->groupBy(fn ($o) => $o->paid_at->format('Y-m-d'))
            ->map(fn ($g) => (int) $g->sum('total_cents'));

        $series = collect(range(0, 13))->map(function ($i) use ($since, $byDay) {
            $day = $since->copy()->addDays($i);

            return ['label' => $day->format('M j'), 'value' => $byDay[$day->format('Y-m-d')] ?? 0];
        });

        $recent = Order::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', OrderStatus::Paid->value)
            ->with(['user', 'event'])
            ->latest('paid_at')
            ->limit(6)
            ->get();

        return view('organizer.dashboard', [
            'events' => $events,
            'stats' => [
                'revenue' => $revenue,
                'sold' => $sold,
                'events' => $events->count(),
                'checkin_rate' => $sold > 0 ? (int) round($checkedIn / $sold * 100) : 0,
            ],
            'series' => $series,
            'recent' => $recent,
        ]);
    }
}
