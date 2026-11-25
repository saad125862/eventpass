<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class PaymentAndTicketTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    private function pendingOrder(int $qty = 2, ?array $types = null): Order
    {
        $event = $this->event(types: $types ?? [['General', 20, 10]]);

        return app(BookingService::class)->reserve($this->user(), $event, [$event->ticketTypes->first()->id => $qty]);
    }

    private function card(string $number = '4242 4242 4242 4242'): array
    {
        return ['name' => 'Test User', 'number' => $number, 'expiry' => '12/34', 'cvc' => '123'];
    }

    public function test_successful_payment_issues_one_ticket_per_seat_and_queues_email(): void
    {
        Mail::fake();
        $order = $this->pendingOrder(3);

        $this->actingAs($order->user)->post(route('orders.pay', $order), $this->card())->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(3, $order->tickets()->count());
        $this->assertSame(3, $order->tickets()->distinct()->count('code'));
        Mail::assertQueued(OrderConfirmed::class, 1);
    }

    public function test_declined_card_leaves_the_order_pending(): void
    {
        $order = $this->pendingOrder();

        $this->actingAs($order->user)->post(route('orders.pay', $order), $this->card('4000 0000 0000 0002'))
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, Ticket::count());
    }

    public function test_paying_twice_never_duplicates_tickets(): void
    {
        $order = $this->pendingOrder(2);
        $payments = app(PaymentService::class);

        $payments->markPaid($order, 'ref_1');
        $payments->markPaid($order, 'ref_2');

        $this->assertSame(2, Ticket::count());
        $this->assertSame('ref_1', $order->fresh()->payment_reference);
    }

    public function test_expired_hold_cannot_be_paid_through_checkout(): void
    {
        $order = $this->pendingOrder();
        $this->travel(11)->minutes();

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->pay($order, $this->card());
    }

    public function test_late_confirmation_fails_if_stock_was_taken_by_someone_else(): void
    {
        $event = $this->event(types: [['General', 20, 1]]);
        $typeId = $event->ticketTypes->first()->id;
        $late = app(BookingService::class)->reserve($this->user(), $event, [$typeId => 1]);

        $this->travel(11)->minutes();
        $winner = app(BookingService::class)->reserve($this->user(), $event, [$typeId => 1]);
        app(PaymentService::class)->markPaid($winner, 'ref_winner');

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->markPaid($late, 'ref_late');
    }

    public function test_webhook_requires_valid_signature_and_is_idempotent(): void
    {
        $order = $this->pendingOrder();
        $body = json_encode(['type' => 'payment.succeeded', 'order' => $order->code, 'reference' => 'wh_1']);
        $sign = fn (string $b) => 'sha256='.hash_hmac('sha256', $b, config('eventpass.webhook_secret'));
        $post = fn (string $signature) => $this->call('POST', '/webhooks/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $signature,
        ], $body);

        $post('sha256=bad')->assertUnauthorized();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $post($sign($body))->assertOk()->assertJson(['ok' => true, 'status' => 'paid']);
        $post($sign($body))->assertOk(); // provider retry
        $this->assertSame(2, Ticket::count());
    }

    public function test_ticket_pages_pdf_and_privacy(): void
    {
        $order = $this->pendingOrder(1);
        app(PaymentService::class)->markPaid($order, 'ref');
        $ticket = $order->tickets()->first();

        $this->actingAs($order->user)->get(route('tickets.index'))->assertOk()->assertSee($ticket->shortCode());
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('<svg', false);

        $pdf = $this->get(route('orders.pdf', $order));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        // Strangers cannot open someone else's ticket or PDF.
        $stranger = $this->user();
        $this->actingAs($stranger)->get(route('tickets.show', $ticket))->assertNotFound();
        $this->actingAs($stranger)->get(route('orders.pdf', $order))->assertNotFound();

        // The public QR landing page leaks no personal data.
        $this->post('/logout');
        $this->get('/t/'.$ticket->code)->assertOk()->assertDontSee($order->user->email);
    }
}
