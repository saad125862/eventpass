<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function issueTicket($event): Ticket
    {
        $order = app(BookingService::class)->reserve($this->user(), $event, [$event->ticketTypes->first()->id => 1]);
        app(PaymentService::class)->markPaid($order, 'ref_'.bin2hex(random_bytes(6)));

        return $order->tickets()->firstOrFail();
    }

    public function test_ticket_scans_once_then_reports_already_used(): void
    {
        $organizer = $this->user(Role::Organizer);
        $event = $this->event($organizer);
        $ticket = $this->issueTicket($event);
        $url = route('organizer.events.verify', $event);

        $this->actingAs($organizer)->postJson($url, ['code' => $ticket->code])
            ->assertOk()->assertJson(['result' => 'ok', 'counts' => ['checked_in' => 1, 'total' => 1]]);

        $this->actingAs($organizer)->postJson($url, ['code' => $ticket->code])
            ->assertOk()->assertJson(['result' => 'used', 'message' => 'Already checked in']);

        $this->assertSame(TicketStatus::Used, $ticket->fresh()->status);
        $this->assertSame($organizer->id, $ticket->fresh()->checked_in_by);
    }

    public function test_scanner_accepts_the_full_qr_url_payload(): void
    {
        $organizer = $this->user(Role::Organizer);
        $event = $this->event($organizer);
        $ticket = $this->issueTicket($event);

        // The browser extracts the token from the QR URL; uppercase/whitespace is tolerated too.
        $this->actingAs($organizer)->postJson(route('organizer.events.verify', $event), ['code' => '  '.strtoupper($ticket->code).' '])
            ->assertJson(['result' => 'ok']);
    }

    public function test_invalid_wrong_event_and_void_tickets_are_rejected(): void
    {
        $organizer = $this->user(Role::Organizer);
        $event = $this->event($organizer);
        $other = $this->event($organizer);
        $url = route('organizer.events.verify', $event);

        $this->actingAs($organizer)->postJson($url, ['code' => 'nope'])->assertJson(['result' => 'invalid']);

        $foreign = $this->issueTicket($other);
        $this->actingAs($organizer)->postJson($url, ['code' => $foreign->code])->assertJson(['result' => 'wrong_event']);

        $voided = $this->issueTicket($event);
        $voided->update(['status' => TicketStatus::Void]);
        $this->actingAs($organizer)->postJson($url, ['code' => $voided->code])->assertJson(['result' => 'void']);
    }

    public function test_only_the_event_organizer_can_scan(): void
    {
        $event = $this->event();
        $ticket = $this->issueTicket($event);
        $url = route('organizer.events.verify', $event);

        $this->actingAs($this->user(Role::Attendee))->postJson($url, ['code' => $ticket->code])->assertForbidden();
        $this->actingAs($this->user(Role::Organizer))->postJson($url, ['code' => $ticket->code])->assertForbidden();
        $this->assertSame(TicketStatus::Valid, $ticket->fresh()->status);

        $this->actingAs($this->user(Role::Admin))->postJson($url, ['code' => $ticket->code])->assertJson(['result' => 'ok']);
    }

    public function test_attendee_export_is_csv_and_neutralises_formulas(): void
    {
        $organizer = $this->user(Role::Organizer);
        $event = $this->event($organizer);
        $order = app(BookingService::class)->reserve($this->user(attrs: ['name' => '=HYPERLINK("http://evil")']), $event, [$event->ticketTypes->first()->id => 1]);
        app(PaymentService::class)->markPaid($order, 'ref');

        $csv = $this->actingAs($organizer)->get(route('organizer.events.export', $event))->assertOk()->streamedContent();

        $this->assertStringContainsString('Ticket,Name,Email', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
