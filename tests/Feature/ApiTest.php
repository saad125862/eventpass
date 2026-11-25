<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function ticketFor(User $buyer, $event)
    {
        $order = app(BookingService::class)->reserve($buyer, $event, [$event->ticketTypes->first()->id => 1]);
        app(PaymentService::class)->markPaid($order, 'ref_'.bin2hex(random_bytes(5)));

        return $order->tickets()->firstOrFail();
    }

    public function test_login_returns_a_token_and_rejects_bad_credentials(): void
    {
        $this->user(attrs: ['email' => 'a@example.com', 'password' => 'secret-pass-1']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertStatus(422);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'secret-pass-1', 'device_name' => 'pixel'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'can_organize']])
            ->assertJsonPath('user.role', 'attendee');
    }

    public function test_demo_login_is_flag_gated_and_never_admin(): void
    {
        $this->user(attrs: ['email' => 'attendee@eventpass.test']);
        $this->user(Role::Admin, ['email' => 'admin@eventpass.test']);

        config(['eventpass.demo_login' => false]);
        $this->postJson('/api/login/demo/attendee')->assertNotFound();

        config(['eventpass.demo_login' => true]);
        $this->postJson('/api/login/demo/attendee')->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/login/demo/admin')->assertNotFound();
    }

    public function test_protected_routes_require_a_token_and_logout_revokes_it(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/tickets')->assertUnauthorized();

        $user = $this->user();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.email', $user->email);
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_events_are_public_and_drafts_stay_hidden(): void
    {
        $live = $this->event(attrs: ['title' => 'Public Gig', 'slug' => 'public-gig']);
        $this->event(published: false, attrs: ['slug' => 'hidden-draft']);

        $this->getJson('/api/events')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'public-gig');
        $this->getJson('/api/events/public-gig')->assertOk()->assertJsonPath('data.ticket_types.0.available', 10);
        $this->getJson('/api/events/hidden-draft')->assertNotFound();
        $this->getJson('/api/events?q=zzz-nothing')->assertOk()->assertJsonCount(0, 'data');

        $this->assertNotNull($live);
    }

    public function test_tickets_endpoint_returns_only_the_callers_paid_tickets(): void
    {
        $event = $this->event();
        $mine = $this->user();
        $theirs = $this->user();
        $ticket = $this->ticketFor($mine, $event);
        $this->ticketFor($theirs, $event);

        Sanctum::actingAs($mine);

        $this->getJson('/api/tickets')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $ticket->code)
            ->assertJsonPath('data.0.qr', url('/t/'.$ticket->code));
    }

    public function test_organizer_can_check_in_over_the_api_exactly_once(): void
    {
        $organizer = $this->user(Role::Organizer);
        $event = $this->event($organizer);
        $ticket = $this->ticketFor($this->user(), $event);

        Sanctum::actingAs($organizer);

        $this->getJson('/api/organizer/events')->assertOk()->assertJsonPath('data.0.slug', $event->slug);

        $url = "/api/organizer/events/{$event->slug}/checkin";
        $this->postJson($url, ['code' => $ticket->code])->assertOk()->assertJsonPath('result', 'ok');
        $this->postJson($url, ['code' => $ticket->code])->assertOk()->assertJsonPath('result', 'used');
    }

    public function test_attendees_and_other_organizers_cannot_check_in(): void
    {
        $event = $this->event();
        $ticket = $this->ticketFor($this->user(), $event);
        $url = "/api/organizer/events/{$event->slug}/checkin";

        Sanctum::actingAs($this->user(Role::Attendee));
        $this->postJson($url, ['code' => $ticket->code])->assertForbidden();

        Sanctum::actingAs($this->user(Role::Organizer));
        $this->postJson($url, ['code' => $ticket->code])->assertForbidden();
    }
}
