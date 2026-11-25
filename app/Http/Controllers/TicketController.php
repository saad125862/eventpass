<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(private readonly QrCodeService $qr) {}

    /** The attendee's wallet: every ticket they own, upcoming first. */
    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->with(['event', 'ticketType', 'order'])
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid->value))
            ->get()
            ->sortBy(fn (Ticket $t) => $t->event->starts_at)
            ->groupBy('event_id');

        $pending = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('status', OrderStatus::Pending->value)
            ->where('expires_at', '>', now())
            ->with('event')
            ->get();

        return view('tickets.index', [
            'groups' => $tickets,
            'pending' => $pending,
            'qr' => $this->qr,
        ]);
    }

    public function pdf(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id && $order->isPaid(), 404);

        $order->load(['event', 'tickets.ticketType', 'user']);

        $pdf = Pdf::loadView('pdf.tickets', ['order' => $order, 'qr' => $this->qr])
            ->setPaper('a4');

        return $pdf->download('eventpass-'.$order->event->slug.'.pdf');
    }

    /** Organizers/admins can also open a ticket from the attendee list. */
    public function show(Request $request, Ticket $ticket)
    {
        $ticket->load(['event', 'ticketType', 'user', 'order']);

        abort_unless(
            $ticket->user_id === $request->user()->id || Gate::allows('manage', $ticket->event),
            404,
        );

        return view('tickets.show', ['ticket' => $ticket, 'qr' => $this->qr]);
    }

    /** What anyone sees when they scan a QR with a regular phone camera: no personal data. */
    public function publicLanding(string $code)
    {
        $ticket = Ticket::query()->with('event')->where('code', strtolower($code))->first();

        return view('tickets.public', ['ticket' => $ticket]);
    }
}
