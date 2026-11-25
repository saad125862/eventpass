<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function envelope(): Envelope
    {
        $order = Order::with('event')->findOrFail($this->orderId);

        return new Envelope(subject: "Tomorrow: {$order->event->title}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-reminder',
            with: ['order' => Order::with(['event', 'tickets', 'user'])->findOrFail($this->orderId)],
        );
    }
}
