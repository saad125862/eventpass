<x-layouts.app title="Browse events">
    <section class="relative overflow-hidden border-b border-white/8">
        <div class="aurora" aria-hidden="true"><i class="-top-32 left-1/4 size-[26rem] bg-brand-500/70"></i><i class="-top-10 right-0 size-80 bg-flame/50" style="animation-delay:-5s"></i></div>
        <div class="relative mx-auto max-w-7xl px-4 pt-14 pb-10 sm:px-6 lg:px-8">
            <p data-reveal class="eyebrow">Discover</p>
            <h1 data-reveal data-delay="0.05" class="mt-2 text-4xl font-bold text-white sm:text-5xl">Find your next night out</h1>

            <form method="GET" action="{{ route('events.index') }}" data-reveal data-delay="0.1" class="mt-8 flex max-w-2xl gap-3">
                @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-ink-400" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search by event, venue or city" class="input !py-3.5 !pl-12" aria-label="Search events">
                </div>
                <button class="btn btn-primary">Search</button>
            </form>

            <div data-reveal data-delay="0.15" class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0" role="list" aria-label="Categories">
                <a href="{{ route('events.index', array_filter(['q' => $search])) }}" role="listitem"
                   class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition {{ $category === null ? 'border-brand-400 bg-brand-500/20 text-white' : 'border-white/10 bg-white/5 text-ink-200 hover:bg-white/10' }}">All</a>
                @foreach ($categories as $c)
                    <a href="{{ route('events.index', array_filter(['q' => $search, 'category' => $c])) }}" role="listitem"
                       class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition {{ $category === $c ? 'border-brand-400 bg-brand-500/20 text-white' : 'border-white/10 bg-white/5 text-ink-200 hover:bg-white/10' }}">{{ $c }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        <p class="text-sm text-ink-300">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}@if ($search !== '') for “{{ $search }}”@endif @if ($category) in {{ $category }}@endif</p>

        @if ($events->isNotEmpty())
            <div data-stagger class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>
            <x-pager :paginator="$events" />
        @else
            <div class="card mt-8 grid place-items-center px-6 py-24 text-center">
                <x-icon name="search" class="size-10 text-ink-400" />
                <h2 class="mt-4 text-xl font-bold text-white">Nothing matches that yet</h2>
                <p class="mt-2 max-w-sm text-sm text-ink-300">Try a different search or category, or clear the filters to see everything that is coming up.</p>
                <a href="{{ route('events.index') }}" class="btn btn-ghost mt-6">Clear filters</a>
            </div>
        @endif
    </section>
</x-layouts.app>
