@props(['title', 'subtitle'])
<x-layouts.app :title="$title" :bare="true">
    <div class="grid min-h-dvh lg:grid-cols-[1fr_1.05fr]">
        {{-- Form side --}}
        <div class="relative flex flex-col px-6 py-8 sm:px-12">
            <a href="{{ route('home') }}" aria-label="EventPass home" class="w-fit"><x-logo /></a>
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
                <h1 data-reveal class="text-4xl font-bold text-white">{{ $title }}</h1>
                <p data-reveal data-delay="0.06" class="mt-2 text-ink-300">{{ $subtitle }}</p>
                <div data-reveal data-delay="0.12" class="mt-8">
                    {{ $slot }}
                </div>
                <div data-reveal data-delay="0.18" class="mt-8 text-center text-sm text-ink-300">{{ $footer ?? '' }}</div>
            </div>
        </div>

        {{-- Showcase side --}}
        <div class="relative hidden overflow-hidden border-l border-white/8 bg-ink-900 lg:block" aria-hidden="true">
            <div class="aurora">
                <i class="-top-24 -left-10 size-[30rem] bg-brand-500"></i>
                <i class="right-0 bottom-0 size-[26rem] bg-flame/80" style="animation-delay:-7s"></i>
            </div>
            <div class="bg-grid absolute inset-0"></div>
            <div class="relative flex h-full flex-col items-center justify-center gap-10 p-12">
                <div class="animate-float" style="--r:-4deg">
                    <div data-tilt="8" class="ticket w-80 overflow-hidden shadow-2xl shadow-black/60">
                        <span class="glare z-10"></span>
                        <div class="banner theme-0 px-5 pt-4 pb-8">
                            <p class="relative z-10 text-[10px] font-bold tracking-[0.25em] text-white/80 uppercase">Admit one</p>
                            <p class="relative z-10 mt-1 font-display text-xl font-bold text-white">Neon Nights Festival</p>
                        </div>
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Status</p>
                                <p class="flex items-center gap-1.5 text-sm font-semibold text-mint"><span class="size-2 rounded-full bg-mint"></span> Valid</p>
                            </div>
                            <div class="size-20 rounded-lg bg-white p-1.5"><x-qr-mock :seed="7" /></div>
                        </div>
                    </div>
                </div>
                <ul class="max-w-sm space-y-4 text-sm text-ink-200">
                    @foreach (['Seats are held for you while you pay', 'Instant QR pass the moment you book', 'Organizers get live attendance and a scanner'] as $point)
                        <li class="flex items-start gap-3"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-mint/20 text-mint"><x-icon name="check" class="size-3" /></span>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
