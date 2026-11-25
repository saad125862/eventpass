<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;

class CheckInService
{
    /**
     * Validate a scanned code for an event and mark the ticket as used.
     *
     * The status flip is a single conditional UPDATE (… WHERE status = 'valid'), so if two
     * staff devices scan the same ticket at the same instant only one of them wins.
     *
     * @return array{result: 'ok'|'used'|'invalid'|'wrong_event'|'void', message: string, detail: string, counts: array{checked_in: int, total: int}}
     */
    public function check(Event $event, string $code, User $staff): array
    {
        $code = strtolower(trim($code));
        $ticket = $code === '' ? null : Ticket::query()->with(['ticketType', 'user'])->where('code', $code)->first();

        if (! $ticket) {
            return $this->response($event, 'invalid', 'Ticket not found', 'This code is not an EventPass ticket.');
        }

        if ($ticket->event_id !== $event->id) {
            return $this->response($event, 'wrong_event', 'Wrong event', 'This ticket is for a different event.');
        }

        if ($ticket->status === TicketStatus::Void) {
            return $this->response($event, 'void', 'Ticket voided', 'This ticket is no longer valid.');
        }

        $updated = Ticket::query()
            ->whereKey($ticket->id)
            ->where('status', TicketStatus::Valid->value)
            ->update([
                'status' => TicketStatus::Used->value,
                'checked_in_at' => now(),
                'checked_in_by' => $staff->id,
            ]);

        $label = $ticket->ticketType->name.' · '.$ticket->user->name;

        if ($updated === 1) {
            return $this->response($event, 'ok', 'Welcome in!', $label);
        }

        $ticket->refresh();

        return $this->response(
            $event,
            'used',
            'Already checked in',
            $label.' · scanned '.($ticket->checked_in_at?->diffForHumans() ?? 'earlier'),
        );
    }

    /** @return array{checked_in: int, total: int} */
    public function counts(Event $event): array
    {
        $base = Ticket::query()->where('event_id', $event->id)->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value]);

        return [
            'checked_in' => (clone $base)->where('status', TicketStatus::Used->value)->count(),
            'total' => $base->count(),
        ];
    }

    private function response(Event $event, string $result, string $message, string $detail): array
    {
        return [
            'result' => $result,
            'message' => $message,
            'detail' => $detail,
            'counts' => $this->counts($event),
        ];
    }
}
