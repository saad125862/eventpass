<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendeeController extends Controller
{
    public function index(Event $event)
    {
        Gate::authorize('manage', $event);

        $tickets = Ticket::query()
            ->where('event_id', $event->id)
            ->with(['user', 'ticketType'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        $valid = Ticket::query()->where('event_id', $event->id)->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value]);

        return view('organizer.attendees', [
            'event' => $event,
            'tickets' => $tickets,
            'total' => (clone $valid)->count(),
            'checkedIn' => (clone $valid)->where('status', TicketStatus::Used->value)->count(),
        ]);
    }

    public function export(Event $event): StreamedResponse
    {
        Gate::authorize('manage', $event);

        return response()->streamDownload(function () use ($event) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Name', 'Email', 'Type', 'Status', 'Checked in at']);

            Ticket::query()->where('event_id', $event->id)->with(['user', 'ticketType'])->orderBy('id')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $t) {
                        // Prefix risky leading characters so spreadsheet apps never run them as formulas.
                        $safe = fn (string $v) => preg_match('/^[=+\-@]/', $v) ? "'".$v : $v;

                        fputcsv($out, [
                            $t->shortCode(),
                            $safe($t->user->name),
                            $t->user->email,
                            $safe($t->ticketType->name),
                            $t->status->value,
                            $t->checked_in_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, 'attendees-'.$event->slug.'.csv', ['Content-Type' => 'text/csv']);
    }
}
