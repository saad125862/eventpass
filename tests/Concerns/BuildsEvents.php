<?php

namespace Tests\Concerns;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;

trait BuildsEvents
{
    protected function user(Role $role = Role::Attendee, array $attrs = []): User
    {
        return User::factory()->create(['role' => $role, ...$attrs]);
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: int}>  $types  [name, price in dollars, quantity]
     */
    protected function event(?User $organizer = null, array $types = [['General', 20, 10]], bool $published = true, array $attrs = []): Event
    {
        $organizer ??= $this->user(Role::Organizer);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => $attrs['title'] ?? 'Test Event '.fake()->unique()->numberBetween(1, 99999),
            'slug' => $attrs['slug'] ?? 'test-event-'.fake()->unique()->numberBetween(1, 99999),
            'category' => 'Tech',
            'description' => 'A test event.',
            'venue' => 'Test Hall',
            'city' => 'Dhaka',
            'starts_at' => now()->addDays(10),
            'theme' => 1,
            'status' => $published ? EventStatus::Published : EventStatus::Draft,
            ...$attrs,
        ]);

        foreach ($types as $sort => [$name, $price, $qty]) {
            TicketType::create([
                'event_id' => $event->id,
                'name' => $name,
                'price_cents' => $price * 100,
                'quantity' => $qty,
                'max_per_order' => 6,
                'sort' => $sort,
            ]);
        }

        return $event->load('ticketTypes');
    }
}
