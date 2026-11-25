<x-auth-shell title="Welcome back" subtitle="Sign in to see your tickets and manage events.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input" placeholder="you@example.com">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div x-data="{ show: false }">
            <label for="password" class="label">Password</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password" class="input pr-16" placeholder="••••••••">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 my-auto h-fit text-xs font-semibold text-ink-300 hover:text-white" x-text="show ? 'Hide' : 'Show'"></button>
            </div>
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2.5 text-sm text-ink-200">
            <input type="checkbox" name="remember" class="size-4 rounded border-white/20 bg-ink-900 accent-brand-500"> Keep me signed in
        </label>
        <button class="btn btn-primary w-full !py-3.5" :disabled="busy">
            <span x-show="!busy">Sign in</span><span x-cloak x-show="busy">Signing in…</span>
        </button>
    </form>

    @if (config('eventpass.demo_login'))
        <div class="mt-8" x-data="{ busy: null }">
            <div class="flex items-center gap-4 text-xs font-semibold tracking-wider text-ink-400 uppercase">
                <span class="h-px flex-1 bg-white/10"></span> Or try the demo <span class="h-px flex-1 bg-white/10"></span>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'attendee' => ['ticket', 'Demo attendee', 'Browse, book and pay'],
                    'organizer' => ['calendar', 'Demo organizer', 'Events, sales and scanner'],
                ] as $role => [$icon, $label, $hint])
                    <form method="POST" action="{{ route('login.demo', $role) }}" @submit="busy = '{{ $role }}'">
                        @csrf
                        <button class="group flex w-full items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-left transition hover:border-brand-400/50 hover:bg-brand-500/10 active:scale-[0.98] disabled:opacity-60" :disabled="busy !== null">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-linear-to-br from-brand-500/30 to-flame/20 text-brand-300 ring-1 ring-white/10 transition group-hover:scale-105"><x-icon :name="$icon" class="size-5" /></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-white" x-text="busy === '{{ $role }}' ? 'Signing in…' : '{{ $label }}'">{{ $label }}</span>
                                <span class="block truncate text-xs text-ink-300">{{ $hint }}</span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
            <p class="mt-3 text-center text-xs text-ink-400">One click, no password. Payments are test mode.</p>
        </div>
    @endif

    <x-slot:footer>New here? <a href="{{ route('register') }}" class="font-semibold text-brand-300 hover:text-white">Create an account</a></x-slot:footer>
</x-auth-shell>
