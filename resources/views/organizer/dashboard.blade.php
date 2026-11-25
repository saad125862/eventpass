@use('App\Support\Money')
@php
    $max = max(1, $series->max('value'));
    $points = $series->values()->map(fn ($p, $i) => [round($i * (400 / 13), 1), round(88 - ($p['value'] / $max) * 76, 1)]);
    $line = $points->map(fn ($p) => $p[0].','.$p[1])->implode(' ');
    $area = '0,100 '.$line.' 400,100';
@endphp
<x-layouts.app title="Organizer dashboard">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">Organizer</p><h1 class="mt-2 text-4xl font-bold text-white">Dashboard</h1></div>
            <a href="{{ route('organizer.events.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Create event</a>
        </div>

        {{-- Stat cards --}}
        <div data-stagger class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Revenue', $stats['revenue'] / 100, 'chart', 'text-mint bg-mint/15', '$', ''],
                ['Tickets sold', $stats['sold'], 'ticket', 'text-brand-300 bg-brand-500/15', '', ''],
                ['Events', $stats['events'], 'calendar', 'text-sun bg-sun/15', '', ''],
                ['Check-in rate', $stats['checkin_rate'], 'scan', 'text-flame bg-flame/15', '', '%'],
            ] as [$label, $value, $icon, $tone, $prefix, $suffix])
                <div class="card p-5">
                    <div class="flex items-center justify-between"><p class="text-sm text-ink-300">{{ $label }}</p><span class="grid size-9 place-items-center rounded-xl {{ $tone }}"><x-icon :name="$icon" class="size-4.5" /></span></div>
                    <p class="mt-3 font-display text-3xl font-bold text-white"><span data-count-to="{{ round($value) }}" data-prefix="{{ $prefix }}" data-suffix="{{ $suffix }}">{{ $prefix }}{{ round($value) }}{{ $suffix }}</span></p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Sparkline --}}
            <div data-reveal class="card p-6 lg:col-span-2">
                <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-white">Revenue, last 14 days</h2><span class="text-sm text-ink-300">{{ Money::format($series->sum('value'), false) }}</span></div>
                <svg viewBox="0 0 400 100" class="mt-4 h-44 w-full" preserveAspectRatio="none" role="img" aria-label="Revenue over the last 14 days">
                    <defs><linearGradient id="g" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#7c5cff" stop-opacity=".45"/><stop offset="1" stop-color="#7c5cff" stop-opacity="0"/></linearGradient></defs>
                    <polygon points="{{ $area }}" fill="url(#g)"/>
                    <polyline points="{{ $line }}" fill="none" stroke="#9a82ff" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                    @foreach ($points as $p)<circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="2" fill="#ff4d8d" vector-effect="non-scaling-stroke"/>@endforeach
                </svg>
                <div class="mt-2 flex justify-between text-xs text-ink-400"><span>{{ $series->first()['label'] }}</span><span>{{ $series->last()['label'] }}</span></div>
            </div>

            {{-- Recent orders --}}
            <div data-reveal data-delay="0.08" class="card p-6">
                <h2 class="text-lg font-bold text-white">Recent orders</h2>
                <ul class="mt-4 space-y-4">
                    @forelse ($recent as $order)
                        <li class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-linear-to-br from-brand-500 to-flame text-xs font-bold text-white">{{ $order->user->initials() }}</span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-white">{{ $order->user->name }}</p><p class="truncate text-xs text-ink-300">{{ $order->event->title }}</p></div>
                            <span class="text-sm font-semibold text-mint">{{ Money::format($order->total_cents) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-300">No paid orders yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Events --}}
        <h2 data-reveal class="mt-12 text-2xl font-bold text-white">Your events</h2>
        <div data-stagger class="mt-5 space-y-4">
            @forelse ($events as $event)
                @php $cap = $event->capacity(); $sold = $cap - $event->remaining(); $pct = $cap ? (int) round($sold / $cap * 100) : 0; @endphp
                <div class="card flex flex-wrap items-center gap-5 p-5">
                    <div class="banner theme-{{ $event->theme }} hidden size-16 shrink-0 rounded-xl sm:block"></div>
                    <div class="min-w-0 flex-1 basis-64">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-lg font-bold text-white">{{ $event->title }}</h3>
                            <span class="badge {{ $event->isPublished() ? 'bg-mint/15 text-mint ring-mint/30' : 'bg-white/5 text-ink-300 ring-white/10' }}">{{ $event->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-ink-300">{{ $event->starts_at->format('D, M j · g:i A') }} · {{ $event->city }}</p>
                        <div class="mt-3 flex items-center gap-3"><div class="h-1.5 max-w-xs flex-1 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-linear-to-r from-brand-400 to-flame" style="width: {{ $pct }}%"></div></div><span class="text-xs text-ink-300">{{ $sold }} / {{ $cap }} sold</span></div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('organizer.events.edit', $event) }}" class="btn btn-ghost !py-2"><x-icon name="edit" class="size-4" /> Edit</a>
                        <a href="{{ route('organizer.events.attendees', $event) }}" class="btn btn-ghost !py-2"><x-icon name="users" class="size-4" /> Attendees</a>
                        <a href="{{ route('organizer.events.scan', $event) }}" class="btn btn-primary !py-2"><x-icon name="scan" class="size-4" /> Scan</a>
                    </div>
                </div>
            @empty
                <div class="card grid place-items-center px-6 py-20 text-center">
                    <x-icon name="calendar" class="size-10 text-ink-400" />
                    <h3 class="mt-4 text-xl font-bold text-white">Create your first event</h3>
                    <p class="mt-2 max-w-sm text-sm text-ink-300">Add the details and ticket tiers, then publish when you are ready.</p>
                    <a href="{{ route('organizer.events.create') }}" class="btn btn-primary mt-6">Create event</a>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
