<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /** The signed-in user's paid tickets. `qr` is exactly what the QR code on the website encodes. */
    public function index(Request $request, QrCodeService $qr): JsonResponse
    {
        $tickets = Ticket::query()
            ->where('user_id', $request->user()?->id)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid->value))
            ->with(['event', 'ticketType'])
            ->get()
            ->sortBy(fn (Ticket $t) => $t->event->starts_at)
            ->values();

        return response()->json(['data' => $tickets->map(fn (Ticket $t) => [
            'code' => $t->code,
            'short_code' => $t->shortCode(),
            'status' => $t->status->value,
            'type' => $t->ticketType->name,
            'qr' => $qr->payload($t),
            'checked_in_at' => $t->checked_in_at?->toIso8601String(),
            'event' => [
                'slug' => $t->event->slug,
                'title' => $t->event->title,
                'venue' => $t->event->venue,
                'city' => $t->event->city,
                'starts_at' => $t->event->starts_at->toIso8601String(),
                'theme' => $t->event->theme,
            ],
        ])]);
    }
}
