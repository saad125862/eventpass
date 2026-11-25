<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\EventReminder;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class OrganizerFlowsTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function sell(Event $event, int $qty = 1): Order
    {
        $order = app(BookingService::class)->reserve($this->user(), $event, [$event->ticketTypes->first()->id => $qty]);

        return app(PaymentService::class)->markPaid($order, 'ref_'.bin2hex(random_bytes(5)));
    }

    private function form(Event $event, array $tickets): array
    {
        return [
            'title' => $event->title, 'category' => 'Tech', 'description' => 'Updated.', 'venue' => 'Hall',
            'city' => 'Dhaka', 'starts_at' => now()->addDays(15)->format('Y-m-d\TH:i'), 'theme' => 3,
            'tickets' => $tickets,
        ];
    }

    public function test_editing_keeps_sold_ticket_types_and_never_drops_quantity_below_sales(): void
    {
        $owner = $this->user(Role::Organizer);
        $event = $this->event($owner, [['General', 20, 10], ['VIP', 50, 5]]);
        $general = $event->ticketTypes[0];
        $this->sell($event, 4);

        $response = $this->actingAs($owner)->put("/organizer/events/{$event->slug}", $this->form($event, [
            // Try to shrink General below the 4 already sold, and drop VIP (unsold) entirely.
            ['id' => $general->id, 'name' => 'General', 'description' => '', 'price' => '22', 'quantity' => 2, 'max_per_order' => 6],
            ['id' => null, 'name' => 'Early bird', 'description' => 'Cheap', 'price' => '9.99', 'quantity' => 30, 'max_per_order' => 4],
        ]));

        $response->assertRedirect()->assertSessionHas('status');
        $event->refresh()->load('ticketTypes');

        $this->assertSame(4, $event->ticketTypes->firstWhere('name', 'General')->quantity, 'quantity must not drop below units sold');
        $this->assertSame(2200, $event->ticketTypes->firstWhere('name', 'General')->price_cents);
        $this->assertNull($event->ticketTypes->firstWhere('name', 'VIP'), 'unsold type can be removed');
        $this->assertSame(999, $event->ticketTypes->firstWhere('name', 'Early bird')->price_cents);
    }

    public function test_event_with_paid_orders_cannot_be_deleted_but_empty_one_can(): void
    {
        $owner = $this->user(Role::Organizer);
        $sold = $this->event($owner);
        $this->sell($sold);
        $empty = $this->event($owner);

        $this->actingAs($owner)->delete("/organizer/events/{$sold->slug}")->assertSessionHas('error');
        $this->assertDatabaseHas('events', ['id' => $sold->id]);

        $this->actingAs($owner)->delete("/organizer/events/{$empty->slug}")->assertRedirect(route('organizer.dashboard'));
        $this->assertDatabaseMissing('events', ['id' => $empty->id]);
    }

    public function test_dashboard_attendees_and_scanner_pages_render_with_real_data(): void
    {
        $owner = $this->user(Role::Organizer);
        $event = $this->event($owner);
        $this->sell($event, 2);

        $this->actingAs($owner)->get('/organizer')->assertOk()->assertSee($event->title);
        $this->actingAs($owner)->get("/organizer/events/{$event->slug}/attendees")->assertOk()->assertSee('Checked in');
        $this->actingAs($owner)->get("/organizer/events/{$event->slug}/scan")->assertOk()->assertSee('Start camera');
        $this->actingAs($owner)->get('/organizer/events/create')->assertOk();
        $this->actingAs($owner)->get("/organizer/events/{$event->slug}/edit")->assertOk()->assertSee('Ticket types');
    }

    public function test_reminder_command_emails_once_for_events_starting_within_a_day(): void
    {
        Mail::fake();
        $soon = $this->event(attrs: ['starts_at' => now()->addHours(10)]);
        $later = $this->event(attrs: ['starts_at' => now()->addDays(5)]);
        $this->sell($soon);
        $this->sell($later);

        $this->artisan('eventpass:send-reminders')->assertSuccessful();
        $this->artisan('eventpass:send-reminders')->assertSuccessful(); // second run must not resend

        Mail::assertQueued(EventReminder::class, 1);
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->artisan('eventpass:seed-demo')->assertSuccessful();
        $events = Event::count();
        $orders = Order::count();
        $users = User::count();

        $this->artisan('eventpass:seed-demo')->assertSuccessful();

        $this->assertGreaterThan(5, $events);
        $this->assertGreaterThan(0, $orders);
        $this->assertSame([$events, $orders, $users], [Event::count(), Order::count(), User::count()]);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
