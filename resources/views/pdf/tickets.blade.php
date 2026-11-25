<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; color: #11111d; }
    .ticket { border: 2px solid #11111d; border-radius: 14px; margin-bottom: 26px; page-break-inside: avoid; overflow: hidden; }
    .head { background: #6340f0; color: #fff; padding: 18px 22px; }
    .head small { letter-spacing: 3px; font-size: 9px; text-transform: uppercase; opacity: .85; }
    .head h1 { margin: 6px 0 0; font-size: 22px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 16px 22px; vertical-align: top; font-size: 12px; }
    .label { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: #6b6b88; margin-bottom: 2px; }
    .value { font-size: 13px; font-weight: bold; margin-bottom: 10px; }
    .qr { width: 150px; height: 150px; }
    .code { font-family: DejaVu Sans Mono, monospace; letter-spacing: 3px; font-size: 14px; font-weight: bold; text-align: center; }
    .foot { border-top: 2px dashed #b9b9d1; padding: 10px 22px; font-size: 9px; color: #6b6b88; }
</style>
</head>
<body>
@foreach ($order->tickets as $ticket)
    <div class="ticket">
        <div class="head">
            <small>{{ $ticket->ticketType->name }} · Admit one</small>
            <h1>{{ $order->event->title }}</h1>
        </div>
        <table>
            <tr>
                <td style="width:58%">
                    <div class="label">Date</div><div class="value">{{ $order->event->starts_at->format('l, F j, Y') }}</div>
                    <div class="label">Time</div><div class="value">{{ $order->event->starts_at->format('g:i A') }}</div>
                    <div class="label">Venue</div><div class="value">{{ $order->event->venue }}, {{ $order->event->city }}</div>
                    <div class="label">Holder</div><div class="value">{{ $order->user->name }}</div>
                </td>
                <td style="text-align:center">
                    <img class="qr" src="{{ $qr->dataUri($ticket, 300) }}">
                    <div class="code">{{ $ticket->shortCode() }}</div>
                </td>
            </tr>
        </table>
        <div class="foot">Present this QR code at the entrance. Each ticket can be scanned once. Order {{ $order->code }}</div>
    </div>
@endforeach
</body>
</html>
