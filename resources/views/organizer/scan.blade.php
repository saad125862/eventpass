<x-layouts.app :title="'Scanner · '.$event->title">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8"
         x-data="scanner(@js(route('organizer.events.verify', $event)), @js(csrf_token()))"
         data-checked-in="{{ $counts['checked_in'] }}" data-total="{{ $counts['total'] }}">
        <a href="{{ route('organizer.dashboard') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-ink-300 transition hover:text-white"><x-icon name="arrow" class="size-4 rotate-180" /> Dashboard</a>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">Check-in</p><h1 class="mt-2 text-3xl font-bold text-white sm:text-4xl">{{ $event->title }}</h1></div>
            <div class="glass rounded-2xl px-5 py-3 text-center"><p class="font-display text-3xl font-bold text-white"><span x-text="counts.checked_in"></span><span class="text-ink-400"> / <span x-text="counts.total"></span></span></p><p class="text-xs tracking-wider text-ink-300 uppercase">Checked in</p></div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-5">
                {{-- Camera --}}
                <div class="card relative overflow-hidden p-4">
                    <div class="relative mx-auto aspect-square w-full max-w-md overflow-hidden rounded-2xl bg-ink-900">
                        <div id="qr-reader" class="size-full [&_video]:size-full [&_video]:object-cover"></div>
                        <div x-show="status !== 'scanning'" class="absolute inset-0 grid place-items-center bg-ink-900/90 text-center">
                            <div><x-icon name="qr" class="mx-auto size-14 text-ink-400" /><p class="mt-3 text-sm text-ink-300">Camera is off</p></div>
                        </div>
                        <div x-cloak x-show="status === 'scanning'" class="pointer-events-none absolute inset-6 rounded-2xl border-2 border-brand-400/70">
                            <span class="absolute inset-x-0 top-0 h-0.5 animate-scan bg-linear-to-r from-transparent via-brand-300 to-transparent shadow-[0_0_20px_4px] shadow-brand-400/60"></span>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <button type="button" @click="start()" x-show="!reader || !reader.isScanning" class="btn btn-primary flex-1"><x-icon name="scan" class="size-4" /> Start camera</button>
                        <button type="button" @click="stop()" x-cloak x-show="reader && reader.isScanning" class="btn btn-ghost flex-1">Stop camera</button>
                    </div>
                </div>

                {{-- Manual --}}
                <form @submit.prevent="submit(manual)" class="card flex gap-3 p-4">
                    <input x-model="manual" class="input font-mono" placeholder="Enter ticket code manually" aria-label="Ticket code" autocomplete="off" autocapitalize="off">
                    <button class="btn btn-ghost" :disabled="!manual">Check</button>
                </form>
            </div>

            <div class="space-y-5">
                {{-- Result --}}
                <div class="rounded-2xl border p-6 text-center transition-colors duration-300" role="status" aria-live="assertive"
                     :class="{ 'border-mint/40 bg-mint/10': status === 'ok', 'border-sun/40 bg-sun/10': status === 'warn', 'border-flame/40 bg-flame/10': status === 'bad', 'border-white/10 bg-ink-800/70': ['idle','scanning'].includes(status) }">
                    <div :key="message + status" class="animate-pop">
                        <span class="mx-auto grid size-14 place-items-center rounded-full" :class="{ 'bg-mint/20 text-mint': status === 'ok', 'bg-sun/20 text-sun': status === 'warn', 'bg-flame/20 text-flame': status === 'bad', 'bg-white/8 text-ink-300': ['idle','scanning'].includes(status) }">
                            <svg x-show="status === 'ok'" class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <svg x-cloak x-show="status === 'warn'" class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                            <svg x-cloak x-show="status === 'bad'" class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            <svg x-cloak x-show="['idle','scanning'].includes(status)" class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/></svg>
                        </span>
                        <p class="mt-4 font-display text-2xl font-bold text-white" x-text="message"></p>
                        <p class="mt-1 text-sm text-ink-200" x-text="detail"></p>
                    </div>
                </div>

                {{-- History --}}
                <div class="card p-5">
                    <h2 class="text-sm font-bold text-white">Recent scans</h2>
                    <ul class="mt-3 space-y-2.5">
                        <template x-for="(h, i) in history" :key="h.at + i">
                            <li class="flex items-center gap-3 text-sm">
                                <span class="size-2 shrink-0 rounded-full" :class="{ 'bg-mint': h.result === 'ok', 'bg-sun': h.result === 'used', 'bg-flame': !['ok','used'].includes(h.result) }"></span>
                                <span class="min-w-0 flex-1 truncate text-ink-100" x-text="h.message"></span>
                                <span class="text-xs text-ink-400" x-text="h.at"></span>
                            </li>
                        </template>
                        <li x-show="history.length === 0" class="text-sm text-ink-300">Nothing scanned yet.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
