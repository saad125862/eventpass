<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDemoData extends Command
{
    protected $signature = 'eventpass:seed-demo';

    protected $description = 'Create demo accounts, events and sample sales (safe to run repeatedly)';

    public function handle(BookingService $booking, PaymentService $payments): int
    {
        $password = (string) config('eventpass.demo_password');

        // Admin password comes from ADMIN_PASSWORD; otherwise a random one nobody knows.
        $adminPassword = config('eventpass.admin_password') ?: Str::random(32);

        $admin = $this->user('Site Admin', 'admin@eventpass.test', $adminPassword, Role::Admin);
        $organizer = $this->user('Olivia Organizer', 'organizer@eventpass.test', $password, Role::Organizer);
        $attendees = collect([
            ['Alex Attendee', 'attendee@eventpass.test'],
            ['Sam Rivera', 'sam@eventpass.test'],
            ['Priya Nair', 'priya@eventpass.test'],
            ['Jordan Lee', 'jordan@eventpass.test'],
            ['Mina Kwon', 'mina@eventpass.test'],
        ])->map(fn ($a) => $this->user($a[0], $a[1], $password, Role::Attendee));

        $events = collect($this->catalog())->map(fn (array $def) => $this->event($organizer, $def));

        if (Order::query()->doesntExist()) {
            $this->sampleSales($events, $attendees, $booking, $payments);
        }

        $this->info('Demo data ready: '.$events->count().' events, '.($attendees->count() + 2).' users. Admin: '.$admin->email);

        return self::SUCCESS;
    }

    private function user(string $name, string $email, string $password, Role $role): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role' => $role],
        );
    }

    /** Create (or refresh the date of) a demo event so the site never looks empty or stale. */
    private function event(User $organizer, array $def): Event
    {
        $start = now()->addDays($def['in'])->setTime($def['hour'], 0);

        $event = Event::query()->firstOrCreate(['slug' => $def['slug']], [
            'organizer_id' => $organizer->id,
            'title' => $def['title'],
            'category' => $def['category'],
            'description' => $def['description'],
            'venue' => $def['venue'],
            'city' => $def['city'],
            'starts_at' => $start,
            'ends_at' => (clone $start)->addHours($def['hours']),
            'theme' => $def['theme'],
            'status' => EventStatus::Published,
        ]);

        if ($event->wasRecentlyCreated) {
            foreach ($def['tickets'] as $sort => [$name, $desc, $price, $qty]) {
                TicketType::query()->create([
                    'event_id' => $event->id,
                    'name' => $name,
                    'description' => $desc,
                    'price_cents' => $price * 100,
                    'quantity' => $qty,
                    'max_per_order' => 6,
                    'sort' => $sort,
                ]);
            }
        } elseif ($event->starts_at->isPast()) {
            $event->update(['starts_at' => $start, 'ends_at' => (clone $start)->addHours($def['hours'])]);
        }

        return $event;
    }

    private function sampleSales($events, $attendees, BookingService $booking, PaymentService $payments): void
    {
        $plan = [
            [0, 0, [2, 1]], [0, 1, [1, 0]], [0, 2, [3, 0]],
            [1, 3, [2, 0]], [1, 4, [1, 1]],
            [2, 0, [2, 0]], [3, 1, [4, 0]], [4, 2, [1, 0]],
            [5, 3, [2, 0]], [6, 4, [1, 0]], [2, 4, [2, 0]], [3, 2, [1, 0]],
        ];

        foreach ($plan as $i => [$eventIdx, $userIdx, $qtys]) {
            $event = $events[$eventIdx]->load('ticketTypes');
            $quantities = [];

            foreach ($event->ticketTypes->values() as $j => $type) {
                if (($qtys[$j] ?? 0) > 0) {
                    $quantities[$type->id] = $qtys[$j];
                }
            }

            try {
                $order = $booking->reserve($attendees[$userIdx], $event, $quantities);
                $order = $payments->markPaid($order, 'demo_'.Str::lower(Str::random(10)));
                // Spread the sales over the last two weeks so the dashboard chart has a shape.
                $order->forceFill(['paid_at' => now()->subDays(13 - ($i % 13))->subMinutes($i * 7)])->save();
            } catch (\Throwable $e) {
                $this->warn('Skipped a sample order: '.$e->getMessage());
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function catalog(): array
    {
        return [
            ['slug' => 'neon-nights-festival', 'title' => 'Neon Nights Festival', 'category' => 'Music', 'theme' => 0, 'in' => 6, 'hour' => 20, 'hours' => 6, 'venue' => 'Riverside Arena', 'city' => 'Dhaka',
                'description' => "Two stages, one unforgettable night. Neon Nights brings together the region's best electronic and indie acts for six hours of music under a canopy of lights.\n\nExpect immersive visuals, street food from forty local vendors and a silent-disco tent that stays open until sunrise.",
                'tickets' => [['General admission', 'Standing access to both stages', 25, 400], ['VIP', 'Fast-lane entry, lounge and two drinks', 80, 60]]],
            ['slug' => 'future-stack-summit', 'title' => 'Future Stack Summit', 'category' => 'Tech', 'theme' => 1, 'in' => 11, 'hour' => 9, 'hours' => 8, 'venue' => 'Innovation Hub', 'city' => 'Chattogram',
                'description' => "A one-day conference for engineers who ship. Talks cover platform engineering, cloud-native Laravel, observability and building reliable CI/CD pipelines.\n\nIncludes lunch, a hands-on workshop track and a career fair with twenty hiring teams.",
                'tickets' => [['Early bird', 'Limited discounted seats', 15, 80], ['Standard', 'Full-day access and lunch', 30, 250], ['Workshop pass', 'Standard plus the hands-on track', 55, 40]]],
            ['slug' => 'sunset-rooftop-sessions', 'title' => 'Sunset Rooftop Sessions', 'category' => 'Music', 'theme' => 2, 'in' => 14, 'hour' => 17, 'hours' => 5, 'venue' => 'Skyline Terrace', 'city' => 'Dhaka',
                'description' => "Golden hour, live acoustic sets and a skyline view. A small, intimate rooftop evening limited to 150 guests.\n\nDoors open at 5 PM, first set at sunset.",
                'tickets' => [['Entry', 'Rooftop access', 18, 120], ['Table for four', 'Reserved table and a bottle of sparkling', 90, 8]]],
            ['slug' => 'street-food-carnival', 'title' => 'Street Food Carnival', 'category' => 'Food', 'theme' => 3, 'in' => 19, 'hour' => 12, 'hours' => 9, 'venue' => 'Central Park Grounds', 'city' => 'Sylhet',
                'description' => "Sixty vendors, one giant lawn. Taste your way across the country, from smoky grills to dessert bars, with live cooking demos all afternoon.\n\nEntry includes five tasting tokens.",
                'tickets' => [['Taster', 'Entry plus 5 tokens', 8, 500], ['Foodie', 'Entry plus 15 tokens and a tote bag', 20, 150]]],
            ['slug' => 'city-marathon-5k', 'title' => 'City Marathon 5K & 10K', 'category' => 'Sports', 'theme' => 4, 'in' => 24, 'hour' => 6, 'hours' => 4, 'venue' => 'Lakeside Promenade', 'city' => 'Dhaka',
                'description' => "Run the loop around the lake at sunrise. Chip timing, hydration stations and a finisher medal for everyone who crosses the line.\n\nAll fitness levels welcome. Walkers have their own start wave.",
                'tickets' => [['5K', 'Chip timing and medal', 12, 600], ['10K', 'Chip timing, medal and race tee', 20, 300]]],
            ['slug' => 'indie-comedy-night', 'title' => 'Indie Comedy Night', 'category' => 'Comedy', 'theme' => 5, 'in' => 29, 'hour' => 19, 'hours' => 3, 'venue' => 'The Basement Club', 'city' => 'Dhaka',
                'description' => "Five up-and-coming comics, one headliner and zero filters. A cosy 120-seat room where every seat is a good seat.\n\nAges 16 and over.",
                'tickets' => [['Standard seat', 'Unreserved seating', 10, 90], ['Front row', 'Best seats in the house', 22, 20]]],
            ['slug' => 'gallery-after-dark', 'title' => 'Gallery After Dark', 'category' => 'Art', 'theme' => 0, 'in' => 37, 'hour' => 18, 'hours' => 4, 'venue' => 'Modern Art Museum', 'city' => 'Dhaka',
                'description' => 'Wander the new contemporary wing after closing time with live painters, a pop-up DJ and curator-led micro tours every half hour.',
                'tickets' => [['Evening pass', 'Gallery access and welcome drink', 14, 200]]],
            ['slug' => 'founders-meetup', 'title' => 'Founders & Builders Meetup', 'category' => 'Business', 'theme' => 1, 'in' => 44, 'hour' => 18, 'hours' => 3, 'venue' => 'Co-Work Loft', 'city' => 'Dhaka',
                'description' => "An informal evening for founders, indie hackers and early engineers. Lightning talks, honest war stories and a lot of coffee.\n\nFree to attend. Seats are limited so please reserve.",
                'tickets' => [['Free entry', 'Reserve your seat', 0, 80]]],
        ];
    }
}
