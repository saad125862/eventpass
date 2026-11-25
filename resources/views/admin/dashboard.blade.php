@use('App\Support\Money')
<x-layouts.app title="Admin">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div data-reveal><p class="eyebrow">Admin</p><h1 class="mt-2 text-4xl font-bold text-white">Platform overview</h1></div>

        <div data-stagger class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Users', $stats['users'], '', ''], ['Events', $stats['events'], '', ''], ['Tickets', $stats['tickets'], '', ''], ['Revenue', round($stats['revenue'] / 100), '$', '']] as [$label, $value, $prefix, $suffix])
                <div class="card p-5"><p class="text-sm text-ink-300">{{ $label }}</p><p class="mt-2 font-display text-3xl font-bold text-white"><span data-count-to="{{ $value }}" data-prefix="{{ $prefix }}">{{ $prefix }}{{ $value }}</span></p></div>
            @endforeach
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <section class="card overflow-hidden">
                <h2 class="border-b border-white/8 px-5 py-4 text-lg font-bold text-white">Users</h2>
                <ul class="divide-y divide-white/8">
                    @foreach ($users as $u)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-linear-to-br from-brand-500 to-flame text-xs font-bold text-white">{{ $u->initials() }}</span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-white">{{ $u->name }}</p><p class="truncate text-xs text-ink-300">{{ $u->email }}</p></div>
                            <form method="POST" action="{{ route('admin.users.role', $u) }}">@csrf @method('PATCH')
                                <select name="role" onchange="this.form.submit()" @disabled($u->is(auth()->user())) class="rounded-lg border border-white/10 bg-ink-900 px-2.5 py-1.5 text-xs font-semibold text-ink-100 disabled:opacity-50" aria-label="Role for {{ $u->name }}">
                                    @foreach ($roles as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>@endforeach
                                </select>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-white/8 px-5 py-4 text-lg font-bold text-white">Events</h2>
                <ul class="divide-y divide-white/8">
                    @foreach ($events as $e)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <div class="banner theme-{{ $e->theme }} size-9 shrink-0 rounded-lg"></div>
                            <div class="min-w-0 flex-1"><a href="{{ route('events.show', $e) }}" class="block truncate text-sm font-semibold text-white hover:text-brand-300">{{ $e->title }}</a><p class="truncate text-xs text-ink-300">{{ $e->organizer->name }} · {{ $e->starts_at->format('M j') }}</p></div>
                            <form method="POST" action="{{ route('admin.events.toggle', $e) }}">@csrf
                                <button class="badge cursor-pointer {{ $e->isPublished() ? 'bg-mint/15 text-mint ring-mint/30' : 'bg-white/5 text-ink-300 ring-white/10' }}">{{ $e->isPublished() ? 'Live · unpublish' : 'Draft · publish' }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts.app>
