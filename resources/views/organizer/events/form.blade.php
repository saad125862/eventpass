@php
    $editing = $event->exists;
    $themes = range(0, 5);
    $initial = old('tickets', $tickets);
@endphp
<x-layouts.app :title="$editing ? 'Edit event' : 'Create event'">
    <form method="POST" action="{{ $editing ? route('organizer.events.update', $event) : route('organizer.events.store') }}" class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8"
          x-data="{ title: @js(old('title', $event->title)), theme: {{ (int) old('theme', $event->theme) }}, category: @js(old('category', $event->category ?? 'Music')),
                    tickets: @js($initial),
                    add() { if (this.tickets.length < 8) this.tickets.push({ id: null, name: '', description: '', price: '0', quantity: 50, max_per_order: 6 }) },
                    remove(i) { this.tickets.splice(i, 1) } }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><a href="{{ route('organizer.dashboard') }}" class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-ink-300 transition hover:text-white"><x-icon name="arrow" class="size-4 rotate-180" /> Dashboard</a>
                <h1 class="text-4xl font-bold text-white">{{ $editing ? 'Edit event' : 'Create event' }}</h1></div>
            @if ($editing)
                <span class="badge {{ $event->isPublished() ? 'bg-mint/15 text-mint ring-mint/30' : 'bg-white/5 text-ink-300 ring-white/10' }}">{{ $event->status->label() }}</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-flame/30 bg-flame/10 p-4 text-sm text-flame" role="alert">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 list-inside list-disc">@foreach (collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-8">
                {{-- Details --}}
                <section class="card space-y-5 p-6">
                    <h2 class="text-lg font-bold text-white">Details</h2>
                    <div><label class="label" for="title">Title</label><input id="title" name="title" x-model="title" required maxlength="120" class="input" placeholder="Neon Nights Festival"></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="label" for="category">Category</label>
                            <select id="category" name="category" x-model="category" class="input">@foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
                        <div><label class="label" for="city">City</label><input id="city" name="city" value="{{ old('city', $event->city) }}" required class="input" placeholder="Dhaka"></div>
                    </div>
                    <div><label class="label" for="venue">Venue</label><input id="venue" name="venue" value="{{ old('venue', $event->venue) }}" required class="input" placeholder="Hall name or address"></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="label" for="starts_at">Starts</label><input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required class="input"></div>
                        <div><label class="label" for="ends_at">Ends (optional)</label><input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" class="input"></div>
                    </div>
                    <div><label class="label" for="description">Description</label><textarea id="description" name="description" rows="6" required maxlength="5000" class="input" placeholder="What should people expect?">{{ old('description', $event->description) }}</textarea></div>
                </section>

                {{-- Tickets --}}
                <section class="card p-6">
                    <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-white">Ticket types</h2><button type="button" @click="add()" class="btn btn-ghost !py-2" :disabled="tickets.length >= 8"><x-icon name="plus" class="size-4" /> Add type</button></div>
                    <div class="mt-5 space-y-4">
                        <template x-for="(t, i) in tickets" :key="i">
                            <div class="rounded-2xl border border-white/10 bg-ink-900/60 p-4" x-transition>
                                <input type="hidden" :name="`tickets[${i}][id]`" :value="t.id">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2"><label class="label">Name</label><input :name="`tickets[${i}][name]`" x-model="t.name" required class="input" placeholder="VIP"></div>
                                    <div class="sm:col-span-2"><label class="label">Short description</label><input :name="`tickets[${i}][description]`" x-model="t.description" class="input" placeholder="Front row + welcome drink"></div>
                                    <div><label class="label">Price (USD)</label><input :name="`tickets[${i}][price]`" x-model="t.price" type="number" min="0" step="0.01" required class="input"></div>
                                    <div><label class="label">Quantity</label><input :name="`tickets[${i}][quantity]`" x-model="t.quantity" type="number" min="1" required class="input"></div>
                                    <div><label class="label">Max per order</label><input :name="`tickets[${i}][max_per_order]`" x-model="t.max_per_order" type="number" min="1" max="10" required class="input"></div>
                                    <div class="flex items-end justify-between gap-3">
                                        <p class="text-xs text-ink-300" x-show="t.sold" x-text="`${t.sold} sold or held`"></p>
                                        <button type="button" @click="remove(i)" :disabled="tickets.length === 1" class="btn btn-danger ml-auto !py-2"><x-icon name="trash" class="size-4" /> Remove</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="mt-4 text-xs text-ink-300">Set the price to 0 for a free ticket. Types that already have sales can't be deleted.</p>
                </section>
            </div>

            {{-- Sidebar: theme + live preview + actions --}}
            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <section class="card overflow-hidden">
                    <div class="banner flex h-40 flex-col justify-end p-4" :class="`theme-${theme}`">
                        <span class="badge relative z-10 mb-2 w-fit bg-black/30 text-white ring-white/20 backdrop-blur" x-text="category"></span>
                        <p class="relative z-10 line-clamp-2 font-display text-xl leading-tight font-bold text-white" x-text="title || 'Your event title'"></p>
                    </div>
                    <div class="p-4">
                        <p class="label">Artwork</p>
                        <input type="hidden" name="theme" :value="theme">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($themes as $t)
                                <button type="button" @click="theme = {{ $t }}" class="banner theme-{{ $t }} size-9 rounded-xl ring-2 transition" :class="theme === {{ $t }} ? 'ring-white scale-110' : 'ring-transparent opacity-70 hover:opacity-100'" aria-label="Artwork {{ $t + 1 }}"></button>
                            @endforeach
                        </div>
                    </div>
                </section>

                <div class="space-y-3">
                    <button class="btn btn-primary w-full !py-3.5">{{ $editing ? 'Save changes' : 'Create draft' }}</button>
                </div>

                @if ($editing)
                    <div class="card space-y-3 p-4">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost w-full"><x-icon name="eye" class="size-4" /> {{ $event->isPublished() ? 'View public page' : 'Preview draft' }}</a>
                        <button type="submit" form="publish-form" class="btn w-full {{ $event->isPublished() ? 'btn-ghost' : 'btn-primary' }}">{{ $event->isPublished() ? 'Unpublish' : 'Publish event' }}</button>
                        <button type="submit" form="delete-form" class="btn btn-danger w-full" onclick="return confirm('Delete this event permanently?')"><x-icon name="trash" class="size-4" /> Delete</button>
                    </div>
                @endif
            </aside>
        </div>
    </form>

    @if ($editing)
        <form id="publish-form" method="POST" action="{{ route('organizer.events.publish', $event) }}">@csrf</form>
        <form id="delete-form" method="POST" action="{{ route('organizer.events.destroy', $event) }}">@csrf @method('DELETE')</form>
    @endif
</x-layouts.app>
