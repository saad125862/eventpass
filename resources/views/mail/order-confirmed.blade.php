<!DOCTYPE html>
<html>
<body style="margin:0;background:#06060c;font-family:Arial,Helvetica,sans-serif;color:#e6e6f2;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#06060c;padding:32px 12px;">
<tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#11111d;border:1px solid #23233a;border-radius:20px;overflow:hidden;">
        <tr><td style="background:linear-gradient(135deg,#7c5cff,#ff4d8d);padding:28px 32px;">
            <p style="margin:0;font-size:12px;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.85);">You're going</p>
            <h1 style="margin:8px 0 0;font-size:26px;color:#fff;">{{ $order->event->title }}</h1>
        </td></tr>
        <tr><td style="padding:28px 32px;">
            <p style="margin:0 0 16px;font-size:15px;">Hi {{ explode(' ', $order->user->name)[0] }}, your payment went through and your tickets are ready.</p>
            <p style="margin:0;font-size:14px;color:#b9b9d1;">
                <strong style="color:#fff;">When:</strong> {{ $order->event->starts_at->format('l, F j · g:i A') }}<br>
                <strong style="color:#fff;">Where:</strong> {{ $order->event->venue }}, {{ $order->event->city }}<br>
                <strong style="color:#fff;">Tickets:</strong> {{ $order->tickets->count() }} ({{ $order->tickets->groupBy('ticket_type_id')->map(fn ($g) => $g->count().' × '.$g->first()->ticketType->name)->implode(', ') }})
            </p>
            <p style="margin:28px 0 0;"><a href="{{ url('/tickets') }}" style="display:inline-block;background:#7c5cff;color:#fff;text-decoration:none;font-weight:bold;padding:14px 26px;border-radius:12px;">Open my tickets</a></p>
            <p style="margin:24px 0 0;font-size:12px;color:#8d8dab;">Show the QR code on your phone at the entrance, or download a PDF from your tickets page.</p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
