<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Exceptions\PaymentException;
use App\Models\Event;
use App\Models\Order;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $booking,
        private readonly PaymentService $payments,
    ) {}

    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $order = $this->booking->reserve(
                $request->user(),
                $event,
                $data['items'],
                $data['idempotency_key'] ?? null,
            );
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        // Free orders skip checkout entirely.
        if ($order->isPending() && $order->total_cents === 0) {
            $order = $this->payments->markPaid($order, 'free');
        }

        return redirect()->route('orders.show', $order);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        // Release the hold lazily so the UI is always truthful, even between scheduler runs.
        if ($order->isPending() && ! $order->holdIsActive()) {
            $order->update(['status' => 'expired']);
        }

        $order->load(['event', 'items.ticketType', 'tickets.ticketType']);

        return view('orders.show', ['order' => $order]);
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $card = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'number' => ['required', 'string', 'max:30'],
            'expiry' => ['required', 'string', 'max:7'],
            'cvc' => ['required', 'string', 'max:4'],
        ]);

        try {
            $order = $this->payments->pay($order, $card);
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage())->withInput($request->except(['number', 'cvc']));
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment received. Your tickets are ready!');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $this->booking->cancel($order);

        return redirect()->route('events.show', $order->event)->with('status', 'Reservation cancelled. Your seats were released.');
    }
}
