@php $mine = auth()->id() === $ticket->user_id; @endphp
<x-layouts.app :title="'Pass · '.$ticket->event->title">
    <div class="relative overflow-hidden">
        <div class="aurora" aria-hidden="true"><i class="-top-24 left-1/3 size-96 bg-brand-500/50"></i><i class="bottom-0 -left-10 size-72 bg-flame/40" style="animation-delay:-7s"></i></div>
        <div class="relative mx-auto max-w-md px-4 py-12">
            <a href="{{ $mine ? route('tickets.index') : route('organizer.events.attendees', $ticket->event) }}" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-ink-300 transition hover:text-white"><x-icon name="arrow" class="size-4 rotate-180" /> Back</a>

            <div data-tilt="6" class="ticket overflow-hidden shadow-2xl shadow-black/50" style="--notch-y: 61%">
                <span class="glare z-10"></span>
                <div class="banner theme-{{ $ticket->event->theme }} px-6 pt-6 pb-10">
                    <div class="relative z-10 flex items-start justify-between gap-3">
                        <p class="text-[10px] font-bold tracking-[0.25em] text-white/85 uppercase">{{ $ticket->ticketType->name }}</p>
                        <span class="badge {{ $ticket->status->badge() }} backdrop-blur">{{ $ticket->status->label() }}</span>
                    </div>
                    <h1 class="relative z-10 mt-2 font-display text-3xl leading-tight font-bold text-white">{{ $ticket->event->title }}</h1>
                </div>

                <div class="grid grid-cols-2 gap-4 px-6 py-5 text-sm">
                    <div><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Date</p><p class="font-semibold text-white">{{ $ticket->event->starts_at->format('D, M j') }}</p></div>
                    <div><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Time</p><p class="font-semibold text-white">{{ $ticket->event->starts_at->format('g:i A') }}</p></div>
                    <div class="col-span-2"><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Venue</p><p class="font-semibold text-white">{{ $ticket->event->venue }}, {{ $ticket->event->city }}</p></div>
                    <div class="col-span-2"><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Holder</p><p class="font-semibold text-white">{{ $ticket->user->name }}</p></div>
                </div>

                <div class="tear mx-6"></div>

                <div class="px-6 pt-6 pb-7 text-center">
                    <div class="qr mx-auto w-56 rounded-2xl bg-white p-3 {{ $ticket->status->value !== 'valid' ? 'opacity-40' : '' }}">{!! $qr->svg($ticket, 300) !!}</div>
                    <p class="mt-4 font-mono text-xl font-semibold tracking-[0.3em] text-white">{{ $ticket->shortCode() }}</p>
                    @if ($ticket->status->value === 'used')
                        <p class="mt-2 text-sm text-brand-300">Checked in {{ $ticket->checked_in_at?->format('M j, g:i A') }}</p>
                    @else
                        <p class="mt-2 text-xs text-ink-300">Show this code at the entrance. Turn your screen brightness up for faster scanning.</p>
                    @endif
                </div>
            </div>

            @if ($mine)
                <a href="{{ route('orders.pdf', $ticket->order) }}" class="btn btn-ghost mt-6 w-full"><x-icon name="download" class="size-4" /> Download PDF of this order</a>
            @endif
        </div>
    </div>
</x-layouts.app>
