@use('App\Support\Money')
<x-layouts.app title="My tickets">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">Wallet</p><h1 class="mt-2 text-4xl font-bold text-white">My tickets</h1></div>
            <a href="{{ route('events.index') }}" class="btn btn-ghost">Find more events</a>
        </div>

        @foreach ($pending as $order)
            <a href="{{ route('orders.show', $order) }}" class="mt-8 flex items-center justify-between gap-4 rounded-2xl border border-sun/30 bg-sun/10 p-4 transition hover:bg-sun/15">
                <span class="flex items-center gap-3 text-sm text-ink-100"><x-icon name="clock" class="size-5 text-sun" /> Reservation for <strong class="text-white">{{ $order->event->title }}</strong> is waiting for payment.</span>
                <span class="btn btn-primary !py-2">Finish checkout</span>
            </a>
        @endforeach

        @forelse ($groups as $eventId => $tickets)
            @php $event = $tickets->first()->event; $past = $event->starts_at->isPast(); @endphp
            <section class="mt-12">
                <div data-reveal class="flex flex-wrap items-center justify-between gap-3 border-b border-white/8 pb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white"><a href="{{ route('events.show', $event) }}" class="transition hover:text-brand-300">{{ $event->title }}</a></h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 text-sm text-ink-300"><span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ $event->starts_at->format('D, M j · g:i A') }}</span><span class="flex items-center gap-1.5"><x-icon name="pin" class="size-4" /> {{ $event->venue }}</span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($past)<span class="badge bg-white/5 text-ink-300 ring-white/10">Past event</span>@endif
                        <a href="{{ route('orders.pdf', $tickets->first()->order) }}" class="btn btn-ghost !py-2"><x-icon name="download" class="size-4" /> PDF</a>
                    </div>
                </div>

                <div data-stagger class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" data-tilt="5" class="ticket block overflow-hidden transition hover:border-white/25 {{ $past || $ticket->status->value === 'used' ? 'opacity-75' : '' }}">
                            <span class="glare z-10"></span>
                            <div class="banner theme-{{ $event->theme }} px-5 pt-4 pb-8">
                                <div class="relative z-10 flex items-center justify-between"><p class="text-[10px] font-bold tracking-[0.25em] text-white/85 uppercase">{{ $ticket->ticketType->name }}</p>
                                    <span class="badge {{ $ticket->status->badge() }} backdrop-blur">{{ $ticket->status->label() }}</span></div>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Ticket</p><p class="font-mono text-lg font-semibold tracking-wider text-white">{{ $ticket->shortCode() }}</p>
                                    <p class="mt-2 text-xs text-brand-300">Open pass →</p></div>
                                <div class="qr size-24 shrink-0 rounded-xl bg-white p-1.5">{!! $qr->svg($ticket, 160) !!}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <div data-reveal class="card mt-10 grid place-items-center px-6 py-24 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-500/15 text-brand-300"><x-icon name="ticket" class="size-7" /></span>
                <h2 class="mt-5 text-2xl font-bold text-white">No tickets yet</h2>
                <p class="mt-2 max-w-sm text-sm text-ink-300">When you book an event, your QR passes will live here, ready to scan at the door.</p>
                <a href="{{ route('events.index') }}" class="btn btn-primary mt-7">Browse events</a>
            </div>
        @endforelse
    </div>
</x-layouts.app>
