<x-layouts.app title="Events, tickets and instant check-in">
    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="aurora" aria-hidden="true">
            <i class="-top-40 left-[8%] size-[34rem] bg-brand-500"></i>
            <i class="top-10 right-[2%] size-[28rem] bg-flame/80" style="animation-delay:-6s"></i>
            <i class="bottom-[-8rem] left-[42%] size-[24rem] bg-sun/60" style="animation-delay:-11s"></i>
        </div>
        <div class="bg-grid absolute inset-0" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-16 px-4 pt-16 pb-28 sm:px-6 sm:pt-24 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
            <div>
                <span data-reveal class="glass inline-flex items-center gap-2 rounded-full py-1.5 pr-4 pl-2 text-xs font-medium text-ink-100">
                    <span class="grid size-5 place-items-center rounded-full bg-brand-500 text-white"><x-icon name="sparkles" class="size-3" /></span>
                    QR check-in in under two seconds
                </span>

                <h1 data-reveal data-delay="0.08" class="mt-6 text-5xl leading-[1.02] font-bold text-white sm:text-6xl lg:text-7xl">
                    Every great night starts with a <span class="text-gradient">ticket</span>.
                </h1>

                <p data-reveal data-delay="0.16" class="mt-6 max-w-xl text-lg leading-relaxed text-ink-200">
                    Discover events, grab your seats before they are gone, and walk straight in with a scannable pass. Built so the last ticket is never sold twice.
                </p>

                <div data-reveal data-delay="0.24" class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ route('events.index') }}" class="btn btn-primary btn-lg">Browse events <x-icon name="arrow" class="size-4" /></a>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-ghost btn-lg">Host an event</a>
                    @else
                        @if (auth()->user()->canOrganize())
                            <a href="{{ route('organizer.dashboard') }}" class="btn btn-ghost btn-lg">Organizer dashboard</a>
                        @else
                            <a href="{{ route('tickets.index') }}" class="btn btn-ghost btn-lg">My tickets</a>
                        @endif
                    @endguest
                </div>

                <dl data-reveal data-delay="0.32" class="mt-14 grid max-w-lg grid-cols-3 gap-6">
                    @foreach ([['events', 'Live events'], ['tickets', 'Tickets issued'], ['organizers', 'Organizers']] as [$key, $label])
                        <div>
                            <dd class="font-display text-3xl font-bold text-white"><span data-count-to="{{ $stats[$key] }}" data-suffix="{{ $stats[$key] >= 100 ? '+' : '' }}">{{ $stats[$key] }}</span></dd>
                            <dt class="mt-1 text-xs tracking-wide text-ink-300 uppercase">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Floating 3D ticket stack --}}
            <div class="relative mx-auto h-[26rem] w-full max-w-md sm:h-[32rem]" aria-hidden="true">
                @php
                    $tickets = [
                        ['Neon Nights Festival', 'Sat · 9:00 PM', 'theme-0', 'top-0 left-0 w-[86%]', '-6deg', 'animate-float', 11],
                        ['Future Stack Summit', 'Thu · 10:00 AM', 'theme-1', 'top-36 right-0 w-[86%]', '5deg', 'animate-float-slow', 29],
                        ['Sunset Rooftop Sessions', 'Fri · 7:30 PM', 'theme-2', 'bottom-0 left-4 w-[82%]', '-2deg', 'animate-float', 53],
                    ];
                @endphp
                @foreach ($tickets as $i => [$name, $when, $theme, $pos, $rot, $anim, $seed])
                    <div class="absolute {{ $pos }} {{ $anim }}" style="--r: {{ $rot }}; animation-delay: -{{ $i * 2.3 }}s; z-index: {{ 10 + $i }}">
                        <div data-tilt="9" class="ticket overflow-hidden shadow-2xl shadow-black/50">
                            <span class="glare z-10"></span>
                            <div class="banner {{ $theme }} px-5 pt-4 pb-8">
                                <p class="relative z-10 text-[10px] font-bold tracking-[0.25em] text-white/80 uppercase">Admit one</p>
                                <p class="relative z-10 mt-1 font-display text-xl leading-tight font-bold text-white">{{ $name }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Date</p>
                                    <p class="text-sm font-semibold text-white">{{ $when }}</p>
                                    <p class="mt-2 text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Gate</p>
                                    <p class="text-sm font-semibold text-white">A · Row {{ 4 + $i * 3 }}</p>
                                </div>
                                <div class="size-20 rounded-lg bg-white p-1.5"><x-qr-mock :seed="$seed" /></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CATEGORY MARQUEE ============ --}}
    <section class="border-y border-white/8 bg-ink-900/50 py-5" aria-label="Event categories">
        <div class="overflow-hidden [mask-image:linear-gradient(90deg,transparent,#000_12%,#000_88%,transparent)]">
            <div class="flex w-max animate-marquee gap-4 hover:[animation-play-state:paused]">
                @foreach (range(1, 2) as $_)
                    @foreach ($categories as $category)
                        <a href="{{ route('events.index', ['category' => $category]) }}" class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-2 text-sm font-medium text-ink-100 transition hover:border-brand-400/50 hover:bg-brand-500/10">
                            <span class="size-1.5 rounded-full bg-linear-to-r from-brand-400 to-flame"></span>{{ $category }}
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ FEATURED EVENTS ============ --}}
    <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 lg:px-8">
        <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Happening soon</p>
                <h2 class="section-title mt-2">Upcoming events</h2>
            </div>
            <a href="{{ route('events.index') }}" class="btn btn-ghost">View all <x-icon name="arrow" class="size-4" /></a>
        </div>

        @if ($featured->isNotEmpty())
            <div data-stagger class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>
        @else
            <div data-reveal class="card mt-10 grid place-items-center px-6 py-20 text-center">
                <x-icon name="calendar" class="size-10 text-ink-400" />
                <h3 class="mt-4 text-xl font-bold text-white">No events yet</h3>
                <p class="mt-2 max-w-sm text-sm text-ink-300">Be the first to publish one. Organizers can create an event and open ticket sales in minutes.</p>
                <a href="{{ route('register') }}" class="btn btn-primary mt-6">Create an organizer account</a>
            </div>
        @endif
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="mx-auto max-w-7xl px-4 pt-28 sm:px-6 lg:px-8">
        <div data-reveal class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">How it works</p>
            <h2 class="section-title mt-2">From browsing to the door in three steps</h2>
        </div>
        <div data-stagger class="mt-14 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['search', 'Find your event', 'Filter by category and city, then pick the ticket tier that suits you.'],
                ['lock', 'Hold and pay', 'Your seats are reserved for ten minutes while you check out, so nobody can snatch them.'],
                ['qr', 'Scan and go', 'Your QR pass is instant. Staff scan it once at the gate and you are in.'],
            ] as $i => [$icon, $title, $copy])
                <div class="card group relative overflow-hidden p-7 transition hover:border-brand-400/40">
                    <span class="absolute -top-4 right-4 font-display text-8xl font-bold text-white/[0.04] transition group-hover:text-brand-400/10">{{ $i + 1 }}</span>
                    <span class="grid size-12 place-items-center rounded-2xl bg-linear-to-br from-brand-500/25 to-flame/20 text-brand-300 ring-1 ring-white/10"><x-icon :name="$icon" class="size-6" /></span>
                    <h3 class="mt-5 text-xl font-bold text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-300">{{ $copy }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FEATURE BENTO ============ --}}
    <section class="mx-auto max-w-7xl px-4 pt-28 sm:px-6 lg:px-8">
        <div data-reveal class="max-w-2xl">
            <p class="eyebrow">Under the hood</p>
            <h2 class="section-title mt-2">Engineered so the basics never break</h2>
        </div>
        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            {{-- Oversell-proof --}}
            <div data-reveal class="card relative overflow-hidden p-8 lg:col-span-2">
                <div class="aurora" aria-hidden="true"><i class="-right-10 -bottom-20 size-72 bg-brand-500/60"></i></div>
                <div class="relative">
                    <span class="badge bg-mint/15 text-mint ring-mint/30"><x-icon name="shield" class="size-3.5" /> Concurrency-safe</span>
                    <h3 class="mt-4 text-2xl font-bold text-white">The last ticket is sold exactly once</h3>
                    <p class="mt-3 max-w-lg text-sm leading-relaxed text-ink-200">Bookings run in database transactions with row-level locks and idempotency keys. Fifty people can hit buy on ten remaining seats and exactly ten succeed, verified by a parallel load test in CI.</p>
                    <div class="mt-8 max-w-md space-y-3" x-data="{ n: 10, tick() { this.n = this.n > 0 ? this.n - 1 : 10 } }" x-init="setInterval(() => tick(), 900)">
                        <div class="flex items-center justify-between text-xs font-semibold text-ink-200"><span>VIP seats left</span><span class="tabular-nums" x-text="n + ' / 10'"></span></div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-linear-to-r from-brand-400 to-flame transition-all duration-700" :style="`width:${n * 10}%`"></div></div>
                    </div>
                </div>
            </div>
            <div data-reveal data-delay="0.08" class="card p-8">
                <span class="grid size-12 place-items-center rounded-2xl bg-sun/15 text-sun ring-1 ring-sun/20"><x-icon name="qr" class="size-6" /></span>
                <h3 class="mt-5 text-xl font-bold text-white">Instant QR passes</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-300">Every ticket carries an unguessable token. Download a PDF or show it from your phone.</p>
            </div>
            <div data-reveal class="card p-8">
                <span class="grid size-12 place-items-center rounded-2xl bg-brand-500/15 text-brand-300 ring-1 ring-brand-400/20"><x-icon name="scan" class="size-6" /></span>
                <h3 class="mt-5 text-xl font-bold text-white">Live check-in</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-300">A camera scanner that blocks double entry and keeps a running attendance count.</p>
            </div>
            <div data-reveal data-delay="0.08" class="card relative overflow-hidden p-8 lg:col-span-2">
                <span class="badge bg-brand-500/15 text-brand-300 ring-brand-400/30"><x-icon name="bolt" class="size-3.5" /> CI/CD</span>
                <h3 class="mt-4 text-2xl font-bold text-white">Ships itself on every push</h3>
                <p class="mt-3 max-w-lg text-sm leading-relaxed text-ink-200">GitHub Actions lints, tests and builds the Docker image, then Render deploys only when checks pass. Migrations run automatically and a health endpoint verifies each release.</p>
                <div class="mt-6 flex flex-wrap items-center gap-2 text-xs font-semibold">
                    @foreach (['Pint', 'Tests', 'Docker build', 'Deploy', 'Health check'] as $step)
                        <span class="badge bg-white/5 text-ink-100 ring-white/10">{{ $step }}</span>@unless ($loop->last)<x-icon name="arrow" class="size-3.5 text-ink-400" />@endunless
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="mx-auto max-w-7xl px-4 pt-28 sm:px-6 lg:px-8">
        <div data-reveal class="banner theme-0 rounded-3xl px-6 py-16 text-center sm:px-16">
            <div class="relative z-10">
                <h2 class="font-display text-3xl font-bold text-white sm:text-5xl">Ready to run your own event?</h2>
                <p class="mx-auto mt-4 max-w-xl text-white/80">Create an event, set up ticket tiers, and share a link. Check-in is a phone camera away.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn btn-lg bg-white text-ink-900 hover:bg-white/90">Get started free</a>
                    <a href="{{ route('events.index') }}" class="btn btn-lg border border-white/30 bg-black/20 text-white hover:bg-black/30">See events</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
