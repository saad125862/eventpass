<x-auth-shell title="Create your account" subtitle="Book tickets in seconds, or host your own events.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <fieldset>
            <legend class="label">I want to</legend>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['attendee' => ['ticket', 'Attend events'], 'organizer' => ['calendar', 'Host events']] as $value => [$icon, $label])
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}" class="peer sr-only" @checked(old('role', 'attendee') === $value)>
                        <span class="flex items-center gap-3 rounded-xl border border-white/10 bg-ink-900 px-4 py-3 text-sm font-semibold text-ink-200 transition peer-checked:border-brand-400 peer-checked:bg-brand-500/10 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/30">
                            <x-icon :name="$icon" class="size-5" /> {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('role')<p class="field-error">{{ $message }}</p>@enderror
        </fieldset>
        <div>
            <label for="name" class="label">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input" placeholder="Ada Lovelace">
            @error('name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input" placeholder="you@example.com">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="input" placeholder="8+ characters">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input" placeholder="Repeat it">
            </div>
        </div>
        <button class="btn btn-primary w-full !py-3.5" :disabled="busy">
            <span x-show="!busy">Create account</span><span x-cloak x-show="busy">Creating…</span>
        </button>
    </form>

    <x-slot:footer>Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-300 hover:text-white">Sign in</a></x-slot:footer>
</x-auth-shell>
