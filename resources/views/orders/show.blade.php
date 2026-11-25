@use('App\Support\Money')
<x-layouts.app :title="$order->isPaid() ? 'Your tickets' : 'Checkout'">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">

        {{-- ================= PAID ================= --}}
        @if ($order->isPaid())
            <div data-confetti class="relative overflow-hidden rounded-3xl border border-white/10 bg-ink-800/60 px-6 py-12 text-center">
                <div class="aurora" aria-hidden="true"><i class="-top-24 left-1/4 size-80 bg-mint/40"></i><i class="-bottom-20 right-1/4 size-72 bg-brand-500/50" style="animation-delay:-6s"></i></div>
                <div class="relative">
                    <span class="mx-auto grid size-16 animate-pop place-items-center rounded-full bg-mint/20 text-mint ring-8 ring-mint/10"><x-icon name="check" class="size-8" /></span>
                    <h1 data-reveal class="mt-6 text-4xl font-bold text-white sm:text-5xl">You're going!</h1>
                    <p data-reveal data-delay="0.08" class="mx-auto mt-3 max-w-lg text-ink-200">{{ $order->tickets->count() }} {{ Str::plural('ticket', $order->tickets->count()) }} for <strong class="text-white">{{ $order->event->title }}</strong>. A confirmation is on its way to your inbox.</p>
                    <div data-reveal data-delay="0.14" class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('orders.pdf', $order) }}" class="btn btn-primary"><x-icon name="download" class="size-4" /> Download PDF</a>
                        <a href="{{ route('tickets.index') }}" class="btn btn-ghost">All my tickets</a>
                    </div>
                </div>
            </div>

            <div data-stagger class="mt-10 grid gap-6 md:grid-cols-2">
                @foreach ($order->tickets as $ticket)
                    @php $qrSvg = app(\App\Services\QrCodeService::class)->svg($ticket, 200); @endphp
                    <a href="{{ route('tickets.show', $ticket) }}" data-tilt="5" class="ticket block overflow-hidden transition hover:border-white/25">
                        <span class="glare z-10"></span>
                        <div class="banner theme-{{ $order->event->theme }} px-6 pt-5 pb-9">
                            <p class="relative z-10 text-[10px] font-bold tracking-[0.25em] text-white/85 uppercase">{{ $ticket->ticketType->name }}</p>
                            <p class="relative z-10 mt-1 font-display text-xl leading-tight font-bold text-white">{{ $order->event->title }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-6 py-5">
                            <div class="space-y-3 text-sm">
                                <div><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">When</p><p class="font-semibold text-white">{{ $order->event->starts_at->format('M j · g:i A') }}</p></div>
                                <div><p class="text-[10px] font-semibold tracking-widest text-ink-300 uppercase">Ticket</p><p class="font-mono font-semibold tracking-wider text-white">{{ $ticket->shortCode() }}</p></div>
                            </div>
                            <div class="qr size-28 shrink-0 rounded-xl bg-white p-2">{!! $qrSvg !!}</div>
                        </div>
                    </a>
                @endforeach
            </div>

        {{-- ================= PENDING / CHECKOUT ================= --}}
        @elseif ($order->holdIsActive())
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Secure checkout</p>
                    <h1 class="mt-2 text-4xl font-bold text-white">Complete your order</h1>
                </div>
                <div x-data="countdown(@js($order->expires_at->toIso8601String()), @js(route('orders.show', $order)))" class="glass w-64 rounded-2xl p-4" :class="urgent && 'border-flame/50'">
                    <div class="flex items-center justify-between text-xs font-semibold tracking-wide uppercase" :class="urgent ? 'text-flame' : 'text-ink-300'">
                        <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-4" /> Seats held for</span>
                        <span class="font-display text-xl tracking-normal normal-case tabular-nums text-white" x-text="`${minutes}:${seconds}`"></span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full transition-all duration-1000 ease-linear" :class="urgent ? 'bg-flame' : 'bg-linear-to-r from-brand-400 to-mint'" :style="`width:${progress}%`"></div></div>
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-[1fr_24rem]">
                {{-- Payment --}}
                <form method="POST" action="{{ route('orders.pay', $order) }}" x-data="cardForm()" @submit="busy = true" class="card p-6 sm:p-8">
                    @csrf
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white">Payment details</h2>
                        <span class="badge bg-sun/15 text-sun ring-sun/30"><x-icon name="shield" class="size-3.5" /> Test mode</span>
                    </div>

                    {{-- Live card preview --}}
                    <div class="mx-auto mb-8 h-48 w-full max-w-sm [perspective:1000px]" aria-hidden="true">
                        <div class="relative size-full transition-transform duration-700 [transform-style:preserve-3d]" :class="flipped && '[transform:rotateY(180deg)]'">
                            <div class="banner theme-0 absolute inset-0 flex flex-col justify-between rounded-2xl p-5 shadow-2xl shadow-brand-500/20 [backface-visibility:hidden]">
                                <div class="relative z-10 flex items-center justify-between"><x-logo :text="false" class="[&>span]:size-8" /><span class="text-xs font-semibold tracking-widest text-white/80 uppercase">Test card</span></div>
                                <div class="relative z-10">
                                    <p class="font-mono text-xl tracking-[0.18em] text-white" x-text="display"></p>
                                    <div class="mt-3 flex justify-between text-xs text-white/85"><span class="max-w-40 truncate uppercase" x-text="name || 'YOUR NAME'"></span><span x-text="expiry || 'MM/YY'"></span></div>
                                </div>
                            </div>
                            <div class="banner theme-1 absolute inset-0 rounded-2xl [backface-visibility:hidden] [transform:rotateY(180deg)]">
                                <div class="relative z-10 mt-6 h-10 bg-black/70"></div>
                                <div class="relative z-10 mt-5 mr-5 ml-5 flex items-center justify-end rounded bg-white px-3 py-2 font-mono text-sm text-ink-900"><span x-text="cvc || '•••'"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="name" class="label">Name on card</label>
                            <input id="name" name="name" x-model="name" value="{{ old('name', auth()->user()->name) }}" x-init="name = $el.value" required autocomplete="cc-name" class="input">
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="number" class="label">Card number</label>
                            <input id="number" name="number" x-model="number" @input="formatNumber()" inputmode="numeric" required autocomplete="cc-number" class="input font-mono tracking-wider" placeholder="4242 4242 4242 4242">
                            @error('number')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label for="expiry" class="label">Expiry</label>
                                <input id="expiry" name="expiry" x-model="expiry" @input="formatExpiry()" inputmode="numeric" required autocomplete="cc-exp" class="input font-mono" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div>
                                <label for="cvc" class="label">CVC</label>
                                <input id="cvc" name="cvc" x-model="cvc" @focus="flipped = true" @blur="flipped = false" inputmode="numeric" required autocomplete="cc-csc" class="input font-mono" placeholder="123" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg mt-8 w-full" :disabled="busy">
                        <span x-show="!busy" class="flex items-center gap-2"><x-icon name="lock" class="size-4" /> Pay {{ Money::format($order->total_cents) }}</span>
                        <span x-cloak x-show="busy" class="flex items-center gap-2"><svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg> Processing…</span>
                    </button>
                    <p class="mt-4 text-center text-xs text-ink-300">No real money moves. Use <button type="button" class="font-mono text-brand-300 hover:text-white" @click="number = '4242 4242 4242 4242'; expiry = '12/34'; cvc = '123'">4242 4242 4242 4242</button> to succeed or <span class="font-mono">4000 0000 0000 0002</span> to see a decline.</p>
                </form>

                {{-- Summary --}}
                <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                    <div class="card overflow-hidden">
                        <div class="banner theme-{{ $order->event->theme }} px-5 py-5">
                            <p class="relative z-10 font-display text-lg leading-tight font-bold text-white">{{ $order->event->title }}</p>
                            <p class="relative z-10 mt-1 text-sm text-white/85">{{ $order->event->starts_at->format('D, M j · g:i A') }}</p>
                        </div>
                        <ul class="divide-y divide-white/8 px-5">
                            @foreach ($order->items as $item)
                                <li class="flex items-center justify-between py-3 text-sm"><span class="text-ink-200">{{ $item->quantity }} × {{ $item->ticketType->name }}</span><span class="font-semibold text-white">{{ Money::format($item->subtotalCents(), false) }}</span></li>
                            @endforeach
                        </ul>
                        <div class="flex items-center justify-between border-t border-white/8 bg-ink-900/50 px-5 py-4"><span class="text-sm text-ink-300">Total</span><span class="font-display text-2xl font-bold text-white">{{ Money::format($order->total_cents) }}</span></div>
                    </div>
                    <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Release your reserved seats?')">@csrf<button class="btn btn-ghost w-full text-ink-300">Cancel reservation</button></form>
                </aside>
            </div>

        {{-- ================= EXPIRED / CANCELLED ================= --}}
        @else
            <div class="card mx-auto grid max-w-xl place-items-center px-6 py-20 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-white/5 text-ink-300"><x-icon name="clock" class="size-7" /></span>
                <h1 class="mt-5 text-3xl font-bold text-white">{{ $order->status->value === 'cancelled' ? 'Reservation cancelled' : 'Your hold expired' }}</h1>
                <p class="mt-2 max-w-sm text-sm text-ink-300">The seats went back on sale so other people can book them. You can pick your tickets again if there is still availability.</p>
                <a href="{{ route('events.show', $order->event) }}" class="btn btn-primary mt-7">Back to {{ Str::limit($order->event->title, 28) }}</a>
            </div>
        @endif
    </div>
</x-layouts.app>
