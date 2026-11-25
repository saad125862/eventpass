@use('App\Support\Money')
<x-layouts.app :title="$event->title" :description="Str::limit(strip_tags($event->description), 150)">
    {{-- Hero --}}
    <section class="banner theme-{{ $event->theme }} border-b border-white/10">
        <div class="relative z-10 mx-auto max-w-7xl px-4 pt-14 pb-16 sm:px-6 lg:px-8">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white/80 transition hover:text-white"><x-icon name="arrow" class="size-4 rotate-180" /> All events</a>
            <div data-reveal class="mt-6 flex flex-wrap items-center gap-2">
                <span class="badge bg-black/30 text-white ring-white/25 backdrop-blur">{{ $event->category }}</span>
                @if ($isPreview)<span class="badge bg-sun text-ink-900 ring-sun">Draft preview · not public</span>@endif
            </div>
            <h1 data-reveal data-delay="0.06" class="mt-4 max-w-4xl text-4xl leading-[1.05] font-bold text-white sm:text-6xl">{{ $event->title }}</h1>
            <div data-reveal data-delay="0.12" class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-white/90">
                <span class="flex items-center gap-2"><x-icon name="calendar" class="size-5" /> {{ $event->starts_at->format('l, F j, Y · g:i A') }}</span>
                <span class="flex items-center gap-2"><x-icon name="pin" class="size-5" /> {{ $event->venue }}, {{ $event->city }}</span>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 pt-12 sm:px-6 lg:grid-cols-[1fr_24rem] lg:px-8">
        {{-- Details --}}
        <div class="space-y-10">
            <section data-reveal>
                <h2 class="text-2xl font-bold text-white">About this event</h2>
                <div class="mt-4 space-y-4 leading-relaxed text-ink-200">
                    @foreach (preg_split("/\n{2,}/", trim($event->description)) as $para)
                        <p>{!! nl2br(e($para)) !!}</p>
                    @endforeach
                </div>
            </section>

            <section data-reveal class="grid gap-4 sm:grid-cols-2">
                <div class="card flex items-start gap-4 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-500/15 text-brand-300"><x-icon name="calendar" /></span>
                    <div><p class="text-xs font-semibold tracking-wider text-ink-300 uppercase">Date and time</p>
                        <p class="mt-1 font-semibold text-white">{{ $event->starts_at->format('D, M j, Y') }}</p>
                        <p class="text-sm text-ink-300">{{ $event->starts_at->format('g:i A') }}@if ($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif</p></div>
                </div>
                <div class="card flex items-start gap-4 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-flame/15 text-flame"><x-icon name="pin" /></span>
                    <div><p class="text-xs font-semibold tracking-wider text-ink-300 uppercase">Location</p>
                        <p class="mt-1 font-semibold text-white">{{ $event->venue }}</p>
                        <p class="text-sm text-ink-300">{{ $event->city }}</p></div>
                </div>
            </section>

            <section data-reveal class="card flex items-center gap-4 p-5">
                <span class="grid size-12 place-items-center rounded-full bg-linear-to-br from-brand-500 to-flame font-bold text-white">{{ $event->organizer->initials() }}</span>
                <div><p class="text-xs font-semibold tracking-wider text-ink-300 uppercase">Hosted by</p><p class="font-semibold text-white">{{ $event->organizer->name }}</p></div>
            </section>
        </div>

        {{-- Booking card --}}
        <aside class="lg:sticky lg:top-24 lg:self-start" data-reveal data-delay="0.1">
            <form method="POST" action="{{ route('orders.store', $event) }}" class="card overflow-hidden"
                  x-data="ticketPicker(@js($pickerTypes), {{ config('eventpass.max_tickets_per_order') }})" @submit="$el.querySelector('[type=submit]').disabled = true">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                <div class="border-b border-white/8 p-5">
                    <h2 class="text-lg font-bold text-white">Tickets</h2>
                    <p class="mt-0.5 text-sm text-ink-300">Seats are held for {{ config('eventpass.hold_minutes') }} minutes at checkout.</p>
                </div>

                <div class="divide-y divide-white/8">
                    <template x-for="t in types" :key="t.id">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-white" x-text="t.name"></p>
                                    <p class="mt-0.5 text-xs text-ink-300" x-show="t.description" x-text="t.description"></p>
                                </div>
                                <p class="font-display text-lg font-bold text-white" x-text="money(t.price_cents)"></p>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <template x-if="t.available > 0 && t.on_sale">
                                    <div class="flex-1">
                                        <div class="h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-linear-to-r from-brand-400 to-flame transition-all duration-700" :style="`width:${t.sold_percent}%`"></div></div>
                                        <p class="mt-1.5 text-xs" :class="t.available <= 10 ? 'font-semibold text-sun' : 'text-ink-300'" x-text="t.available <= 10 ? `Only ${t.available} left` : `${t.available} available`"></p>
                                    </div>
                                </template>
                                <template x-if="t.available > 0 && t.on_sale">
                                    <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-ink-900 p-1">
                                        <button type="button" @click="dec(t)" :disabled="qty[t.id] === 0" class="grid size-8 place-items-center rounded-lg text-white transition hover:bg-white/10 disabled:opacity-30" :aria-label="`Remove one ${t.name}`"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                        <span class="w-7 text-center font-semibold text-white tabular-nums" x-text="qty[t.id]" aria-live="polite"></span>
                                        <button type="button" @click="inc(t)" :disabled="!canInc(t)" class="grid size-8 place-items-center rounded-lg bg-brand-500 text-white transition hover:bg-brand-400 disabled:opacity-30" :aria-label="`Add one ${t.name}`"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>
                                    </div>
                                </template>
                                <template x-if="t.available === 0">
                                    <span class="badge bg-white/5 text-ink-300 ring-white/10">Sold out</span>
                                </template>
                                <template x-if="t.available > 0 && !t.on_sale">
                                    <span class="badge bg-white/5 text-ink-300 ring-white/10">Not on sale</span>
                                </template>
                            </div>
                            <input type="hidden" :name="`items[${t.id}]`" :value="qty[t.id]">
                        </div>
                    </template>
                    @if ($pickerTypes->isEmpty())
                        <p class="p-5 text-sm text-ink-300">Tickets are not available yet.</p>
                    @endif
                </div>

                <div class="space-y-4 border-t border-white/8 bg-ink-900/50 p-5">
                    <div class="flex items-center justify-between text-sm"><span class="text-ink-300"><span x-text="count"></span> ticket<span x-show="count !== 1">s</span></span>
                        <span class="font-display text-2xl font-bold text-white" x-text="count ? money(total) : '—'"></span></div>

                    @if ($isPreview)
                        <button type="button" class="btn btn-ghost w-full" disabled>Publish to enable booking</button>
                    @else
                        @auth
                            <button type="submit" class="btn btn-primary w-full !py-3.5" :disabled="count === 0"><span x-text="count ? 'Reserve tickets' : 'Select tickets'"></span><x-icon name="arrow" class="size-4" /></button>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary w-full !py-3.5">Sign in to book <x-icon name="arrow" class="size-4" /></a>
                            <p class="text-center text-xs text-ink-300">No account? <a href="{{ route('register') }}" class="font-semibold text-brand-300 hover:text-white">Create one in 20 seconds</a></p>
                        @endauth
                    @endif
                </div>
            </form>
        </aside>
    </div>
</x-layouts.app>
