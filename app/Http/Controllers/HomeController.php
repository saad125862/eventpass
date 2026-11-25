<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Event::query()
            ->published()
            ->upcoming()
            ->with(['ticketTypes' => fn ($q) => $q->withReserved()])
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        return view('home', [
            'featured' => $featured,
            'stats' => [
                'events' => Event::query()->published()->count(),
                'tickets' => Ticket::query()->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])->count(),
                'organizers' => User::query()->where('role', Role::Organizer->value)->count(),
            ],
            'categories' => Event::CATEGORIES,
        ]);
    }
}
