<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class RolesAndEventsTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Laravel Live',
            'category' => 'Tech',
            'description' => 'Talks and workshops.',
            'venue' => 'Expo Hall',
            'city' => 'Dhaka',
            'starts_at' => now()->addDays(20)->format('Y-m-d\TH:i'),
            'theme' => 2,
            'tickets' => [
                ['id' => null, 'name' => 'Standard', 'description' => '', 'price' => '25.50', 'quantity' => 100, 'max_per_order' => 6],
                ['id' => null, 'name' => 'VIP', 'description' => 'Front row', 'price' => '80', 'quantity' => 10, 'max_per_order' => 2],
            ],
            ...$overrides,
        ];
    }

    public function test_role_gates(): void
    {
        $this->actingAs($this->user(Role::Attendee))->get('/organizer')->assertForbidden();
        $this->actingAs($this->user(Role::Attendee))->get('/admin')->assertForbidden();
        $this->actingAs($this->user(Role::Organizer))->get('/organizer')->assertOk();
        $this->actingAs($this->user(Role::Organizer))->get('/admin')->assertForbidden();
        $this->actingAs($this->user(Role::Admin))->get('/admin')->assertOk();
        $this->actingAs($this->user(Role::Admin))->get('/organizer')->assertOk();
    }

    public function test_organizer_creates_draft_event_with_ticket_types_priced_in_cents(): void
    {
        $organizer = $this->user(Role::Organizer);

        $this->actingAs($organizer)->post('/organizer/events', $this->payload())->assertRedirect();

        $event = Event::where('title', 'Laravel Live')->firstOrFail();
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame('laravel-live', $event->slug);
        $this->assertSame([2550, 8000], $event->ticketTypes->pluck('price_cents')->all());
    }

    public function test_drafts_are_hidden_from_the_public_but_previewable_by_owner(): void
    {
        $owner = $this->user(Role::Organizer);
        $event = $this->event($owner, published: false, attrs: ['slug' => 'secret-draft']);

        $this->get('/events/secret-draft')->assertNotFound();
        $this->actingAs($this->user(Role::Attendee))->get('/events/secret-draft')->assertNotFound();
        $this->actingAs($owner)->get('/events/secret-draft')->assertOk()->assertSee('Draft preview');
        $this->get('/events')->assertDontSee($event->title);
    }

    public function test_publish_requires_ticket_types_and_makes_event_public(): void
    {
        $owner = $this->user(Role::Organizer);
        $empty = $this->event($owner, types: [], published: false);
        $ready = $this->event($owner, published: false, attrs: ['slug' => 'ready-event']);

        $this->actingAs($owner)->post("/organizer/events/{$empty->slug}/publish")->assertSessionHas('error');
        $this->assertFalse($empty->fresh()->isPublished());

        $this->actingAs($owner)->post("/organizer/events/{$ready->slug}/publish")->assertSessionHas('status');
        $this->get('/events/ready-event')->assertOk();
    }

    public function test_other_organizers_cannot_manage_an_event(): void
    {
        $event = $this->event();
        $intruder = $this->user(Role::Organizer);

        $this->actingAs($intruder)->get("/organizer/events/{$event->slug}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/organizer/events/{$event->slug}", $this->payload())->assertForbidden();
        $this->actingAs($intruder)->get("/organizer/events/{$event->slug}/attendees")->assertForbidden();
        $this->actingAs($intruder)->post("/organizer/events/{$event->slug}/publish")->assertForbidden();
    }

    public function test_event_validation(): void
    {
        $this->actingAs($this->user(Role::Organizer))
            ->post('/organizer/events', $this->payload(['starts_at' => now()->subDay()->format('Y-m-d\TH:i'), 'tickets' => []]))
            ->assertSessionHasErrors(['starts_at', 'tickets']);
    }

    public function test_search_and_category_filters(): void
    {
        $owner = $this->user(Role::Organizer);
        $this->event($owner, attrs: ['title' => 'Jazz Under The Stars', 'slug' => 'jazz', 'category' => 'Music']);
        $this->event($owner, attrs: ['title' => 'Cloud Native Day', 'slug' => 'cloud', 'category' => 'Tech']);

        $this->get('/events?q=jazz')->assertSee('Jazz Under The Stars')->assertDontSee('Cloud Native Day');
        $this->get('/events?category=Tech')->assertSee('Cloud Native Day')->assertDontSee('Jazz Under The Stars');
        $this->get('/events?q=nothing-matches')->assertSee('Nothing matches');
    }

    public function test_admin_can_change_roles_but_not_their_own(): void
    {
        $admin = $this->user(Role::Admin);
        $target = $this->user(Role::Attendee);

        $this->actingAs($admin)->patch("/admin/users/{$target->id}/role", ['role' => 'organizer'])->assertSessionHas('status');
        $this->assertSame(Role::Organizer, $target->fresh()->role);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/role", ['role' => 'attendee'])->assertSessionHas('error');
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }
}
