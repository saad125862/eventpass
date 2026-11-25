@props(['event'])
@use('App\Support\Money')
@php
    $min = $event->minPriceCents();
    $capacity = $event->capacity();
    $left = $event->remaining();
    $soldOut = $capacity > 0 && $left === 0;
    $almost = ! $soldOut && $capacity > 0 && ($left / $capacity) <= 0.2;
@endphp
<a href="{{ route('events.show', $event) }}" data-tilt="4" class="group card block overflow-hidden transition-colors hover:border-white/25">
    <span class="glare z-10"></span>
    <div class="banner theme-{{ $event->theme }} flex h-44 flex-col justify-between p-4">
        <div class="relative z-10 flex items-start justify-between">
            <span class="badge bg-black/30 text-white ring-white/20 backdrop-blur">{{ $event->category }}</span>
            <div class="rounded-xl bg-white/90 px-3 py-1.5 text-center text-ink-900 shadow-lg">
                <div class="text-[10px] leading-none font-bold tracking-widest uppercase">{{ $event->starts_at->format('M') }}</div>
                <div class="font-display text-xl leading-tight font-bold">{{ $event->starts_at->format('d') }}</div>
            </div>
        </div>
        <div class="relative z-10 flex gap-2">
            @if ($soldOut)
                <span class="badge bg-black/40 text-white ring-white/25 backdrop-blur">Sold out</span>
            @elseif ($almost)
                <span class="badge bg-sun/90 text-ink-900 ring-sun">Almost gone · {{ $left }} left</span>
            @endif
        </div>
    </div>
    <div class="p-5">
        <h3 class="line-clamp-2 text-lg leading-snug font-bold text-white transition group-hover:text-brand-300">{{ $event->title }}</h3>
        <div class="mt-3 space-y-1.5 text-sm text-ink-300">
            <p class="flex items-center gap-2"><x-icon name="calendar" class="size-4 text-ink-400" /> {{ $event->starts_at->format('D, M j · g:i A') }}</p>
            <p class="flex items-center gap-2"><x-icon name="pin" class="size-4 text-ink-400" /> <span class="truncate">{{ $event->venue }}, {{ $event->city }}</span></p>
        </div>
        <div class="mt-5 flex items-center justify-between border-t border-white/8 pt-4">
            <span class="text-sm text-ink-300">{{ $min === null ? 'No tickets yet' : ($min === 0 ? '' : 'From') }} <strong class="font-display text-base text-white">{{ $min === null ? '' : Money::format($min) }}</strong></span>
            <span class="flex size-8 items-center justify-center rounded-full bg-white/8 text-white transition group-hover:bg-brand-500 group-hover:translate-x-0.5"><x-icon name="arrow" class="size-4" /></span>
        </div>
    </div>
</a>
