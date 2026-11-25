<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\BookingException;
use App\Models\Order;
use App\Models\TicketType;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function book($user, $event, array $qty, ?string $key = null): Order
    {
        return app(BookingService::class)->reserve($user, $event, $qty, $key);
    }

    public function test_reserving_holds_stock_and_prices_come_from_the_database(): void
    {
        $event = $this->event(types: [['General', 20, 10]]);
        $type = $event->ticketTypes->first();

        $order = $this->book($this->user(), $event, [$type->id => 3]);

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(6000, $order->total_cents);
        $this->assertTrue($order->holdIsActive());
        $this->assertSame(7, TicketType::query()->withReserved()->find($type->id)->available());
    }

    public function test_cannot_book_more_than_remaining(): void
    {
        $event = $this->event(types: [['General', 20, 5]]);
        $type = $event->ticketTypes->first();
        $this->book($this->user(), $event, [$type->id => 4]);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('Only 1');
        $this->book($this->user(), $event, [$type->id => 2]);
    }

    public function test_sold_out_message_and_max_per_order(): void
    {
        $event = $this->event(types: [['General', 20, 3]]);
        $type = $event->ticketTypes->first();
        $this->book($this->user(), $event, [$type->id => 3]);

        try {
            $this->book($this->user(), $event, [$type->id => 1]);
            $this->fail('Expected sold out');
        } catch (BookingException $e) {
            $this->assertStringContainsString('sold out', $e->getMessage());
        }

        $big = $this->event(types: [['Big', 10, 100]]);
        $this->expectException(BookingException::class);
        $this->book($this->user(), $big, [$big->ticketTypes->first()->id => 7]); // max_per_order = 6
    }

    public function test_expired_holds_release_stock(): void
    {
        $event = $this->event(types: [['General', 20, 2]]);
        $type = $event->ticketTypes->first();
        $order = $this->book($this->user(), $event, [$type->id => 2]);

        $this->travel(config('eventpass.hold_minutes') + 1)->minutes();

        // Capacity is available again immediately, before any cleanup job runs...
        $this->assertSame(2, TicketType::query()->withReserved()->find($type->id)->available());
        $second = $this->book($this->user(), $event, [$type->id => 2]);
        $this->assertNotSame($order->id, $second->id);

        // ...and the scheduled command tidies the old order's status.
        $this->artisan('eventpass:release-expired')->assertSuccessful();
        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
    }

    public function test_idempotency_key_returns_the_same_order(): void
    {
        $event = $this->event();
        $type = $event->ticketTypes->first();
        $user = $this->user();

        $a = $this->book($user, $event, [$type->id => 1], 'key-123');
        $b = $this->book($user, $event, [$type->id => 1], 'key-123');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Order::count());
    }

    public function test_unpublished_and_past_events_cannot_be_booked(): void
    {
        $draft = $this->event(published: false);
        $this->expectException(BookingException::class);
        $this->book($this->user(), $draft, [$draft->ticketTypes->first()->id => 1]);
    }

    public function test_booking_over_http_redirects_to_checkout_and_guests_must_log_in(): void
    {
        $event = $this->event();
        $type = $event->ticketTypes->first();

        $this->post("/events/{$event->slug}/orders", ['items' => [$type->id => 2]])->assertRedirect(route('login'));

        $response = $this->actingAs($this->user())->post("/events/{$event->slug}/orders", ['items' => [$type->id => 2]]);
        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Complete your order');
    }

    public function test_free_tickets_skip_checkout(): void
    {
        $event = $this->event(types: [['Free entry', 0, 5]]);
        $type = $event->ticketTypes->first();
        $user = $this->user();

        $this->actingAs($user)->post("/events/{$event->slug}/orders", ['items' => [$type->id => 2]]);

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(2, $order->tickets()->count());
    }

    public function test_orders_are_private_to_their_owner(): void
    {
        $event = $this->event();
        $order = $this->book($this->user(), $event, [$event->ticketTypes->first()->id => 1]);

        $this->actingAs($this->user())->get(route('orders.show', $order))->assertNotFound();
    }

    public function test_cancel_releases_the_hold(): void
    {
        $event = $this->event(types: [['General', 20, 2]]);
        $type = $event->ticketTypes->first();
        $user = $this->user();
        $order = $this->book($user, $event, [$type->id => 2]);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(2, TicketType::query()->withReserved()->find($type->id)->available());
    }
}
