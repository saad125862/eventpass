@props(['title' => null, 'description' => 'Discover events, book in seconds and check in with a QR code.', 'bare' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#06060c">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%237c5cff'/%3E%3Cpath d='M7 12a2 2 0 0 1 0 4v3h18v-3a2 2 0 0 1 0-4V9H7z' fill='white'/%3E%3C/svg%3E">
    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-lg focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    @unless ($bare)
        <header x-data="{ open: false, user: false }" class="sticky top-0 z-50 border-b border-white/8 bg-ink-950/70 backdrop-blur-xl">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Main">
                <a href="{{ url('/') }}" class="shrink-0" aria-label="EventPass home"><x-logo /></a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ url('/events') }}" class="nav-link" @if (request()->is('events*')) aria-current="page" @endif>Events</a>
                    @auth
                        <a href="{{ url('/tickets') }}" class="nav-link" @if (request()->is('tickets*', 'orders*')) aria-current="page" @endif>My tickets</a>
                        @if (auth()->user()->canOrganize())
                            <a href="{{ url('/organizer') }}" class="nav-link" @if (request()->is('organizer*')) aria-current="page" @endif>Organizer</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ url('/admin') }}" class="nav-link" @if (request()->is('admin*')) aria-current="page" @endif>Admin</a>
                        @endif
                    @endauth
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <div class="relative" @click.outside="user = false" @keydown.escape.window="user = false">
                            <button type="button" @click="user = !user" class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 py-1 pr-3 pl-1 transition hover:bg-white/10" :aria-expanded="user">
                                <span class="grid size-8 place-items-center rounded-full bg-linear-to-br from-brand-500 to-flame text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
                                <span class="max-w-28 truncate text-sm font-medium">{{ auth()->user()->name }}</span>
                            </button>
                            <div x-cloak x-show="user" x-transition.origin.top.right class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-white/10 bg-ink-800 shadow-2xl shadow-black/50">
                                <div class="border-b border-white/8 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-ink-300">{{ auth()->user()->email }}</p>
                                    <span class="badge mt-2 bg-brand-500/15 text-brand-300 ring-brand-400/30">{{ auth()->user()->role->label() }}</span>
                                </div>
                                <form method="POST" action="{{ url('/logout') }}" class="p-1.5">
                                    @csrf
                                    <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-ink-100 transition hover:bg-white/8">
                                        <x-icon name="logout" class="size-4" /> Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ url('/login') }}" class="nav-link">Sign in</a>
                        <a href="{{ url('/register') }}" class="btn btn-primary !py-2">Get started</a>
                    @endauth
                </div>

                <button type="button" class="grid size-10 place-items-center rounded-xl border border-white/10 bg-white/5 md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Toggle menu">
                    <x-icon name="menu" x-show="!open" /><x-icon name="x" x-cloak x-show="open" />
                </button>
            </nav>

            <div x-cloak x-show="open" x-transition.opacity class="border-t border-white/8 bg-ink-900/95 md:hidden">
                <div class="space-y-1 px-4 py-3">
                    <a href="{{ url('/events') }}" class="nav-link block">Events</a>
                    @auth
                        <a href="{{ url('/tickets') }}" class="nav-link block">My tickets</a>
                        @if (auth()->user()->canOrganize())<a href="{{ url('/organizer') }}" class="nav-link block">Organizer</a>@endif
                        @if (auth()->user()->isAdmin())<a href="{{ url('/admin') }}" class="nav-link block">Admin</a>@endif
                        <form method="POST" action="{{ url('/logout') }}">@csrf<button class="nav-link block w-full text-left">Sign out ({{ auth()->user()->name }})</button></form>
                    @else
                        <a href="{{ url('/login') }}" class="nav-link block">Sign in</a>
                        <a href="{{ url('/register') }}" class="btn btn-primary mt-2 w-full">Get started</a>
                    @endauth
                </div>
            </div>
        </header>
    @endunless

    <main id="main" class="relative">
        {{ $slot }}
    </main>

    @unless ($bare)
        <footer class="relative mt-28 border-t border-white/8 bg-ink-900/60">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
                <div class="md:col-span-2">
                    <x-logo />
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-300">Concurrency-safe ticketing with instant QR passes and a fast check-in scanner. A portfolio project built to show Laravel and DevOps end to end.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Explore</h3>
                    <ul class="mt-4 space-y-2 text-sm text-ink-300">
                        <li><a class="transition hover:text-white" href="{{ url('/events') }}">Browse events</a></li>
                        <li><a class="transition hover:text-white" href="{{ url('/register') }}">Create an account</a></li>
                        <li><a class="transition hover:text-white" href="{{ url('/health') }}">System status</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Built with</h3>
                    <ul class="mt-4 space-y-2 text-sm text-ink-300">
                        <li>Laravel 13 · PostgreSQL</li>
                        <li>Docker · GitHub Actions</li>
                        <li>Render · Neon</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/8 py-5 text-center text-xs text-ink-400">© {{ date('Y') }} EventPass · Payments run in test mode, no real money moves.</div>
        </footer>
    @endunless

    {{-- Toasts --}}
    <div class="pointer-events-none fixed top-20 right-4 z-[90] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-3" aria-live="polite">
        @foreach (['status' => 'mint', 'error' => 'flame'] as $key => $tone)
            @if (session($key))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5500)" x-show="show" x-transition:enter="transition duration-300" x-transition:enter-start="translate-x-6 opacity-0" x-transition:leave="transition duration-300" x-transition:leave-end="translate-x-6 opacity-0"
                     class="pointer-events-auto flex items-start gap-3 rounded-2xl border border-white/10 bg-ink-800/95 p-4 shadow-2xl shadow-black/40 backdrop-blur-xl">
                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full {{ $tone === 'mint' ? 'bg-mint/20 text-mint' : 'bg-flame/20 text-flame' }}"><x-icon :name="$tone === 'mint' ? 'check' : 'x'" class="size-3.5" /></span>
                    <p class="flex-1 text-sm text-ink-100">{{ session($key) }}</p>
                    <button type="button" @click="show = false" class="text-ink-300 hover:text-white" aria-label="Dismiss"><x-icon name="x" class="size-4" /></button>
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
