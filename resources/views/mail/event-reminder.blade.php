<!DOCTYPE html>
<html>
<body style="margin:0;background:#06060c;font-family:Arial,Helvetica,sans-serif;color:#e6e6f2;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#06060c;padding:32px 12px;">
<tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#11111d;border:1px solid #23233a;border-radius:20px;overflow:hidden;">
        <tr><td style="background:linear-gradient(135deg,#06b6d4,#7c5cff);padding:28px 32px;">
            <p style="margin:0;font-size:12px;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.85);">Happening soon</p>
            <h1 style="margin:8px 0 0;font-size:26px;color:#fff;">{{ $order->event->title }}</h1>
        </td></tr>
        <tr><td style="padding:28px 32px;">
            <p style="margin:0 0 16px;font-size:15px;">Hi {{ explode(' ', $order->user->name)[0] }}, a quick reminder that your event starts {{ $order->event->starts_at->diffForHumans() }}.</p>
            <p style="margin:0;font-size:14px;color:#b9b9d1;">
                <strong style="color:#fff;">When:</strong> {{ $order->event->starts_at->format('l, F j · g:i A') }}<br>
                <strong style="color:#fff;">Where:</strong> {{ $order->event->venue }}, {{ $order->event->city }}
            </p>
            <p style="margin:28px 0 0;"><a href="{{ url('/tickets') }}" style="display:inline-block;background:#7c5cff;color:#fff;text-decoration:none;font-weight:bold;padding:14px 26px;border-radius:12px;">Open my tickets</a></p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
