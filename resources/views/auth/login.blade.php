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

    @if (app()->environment('local') === false)
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs leading-relaxed text-ink-300">
            <p class="font-semibold text-ink-100">Demo accounts</p>
            <p class="mt-1">Organizer: <code class="text-brand-300">organizer@eventpass.test</code> · Attendee: <code class="text-brand-300">attendee@eventpass.test</code><br>Password: <code class="text-brand-300">{{ config('eventpass.demo_password') }}</code></p>
        </div>
    @endif

    <x-slot:footer>New here? <a href="{{ route('register') }}" class="font-semibold text-brand-300 hover:text-white">Create an account</a></x-slot:footer>
</x-auth-shell>
