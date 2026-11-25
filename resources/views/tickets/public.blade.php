<x-layouts.app title="Ticket">
    <div class="mx-auto grid min-h-[60vh] max-w-md place-items-center px-4 py-16 text-center">
        @if ($ticket)
            <div>
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-brand-500/15 text-brand-300"><x-icon name="ticket" class="size-8" /></span>
                <h1 class="mt-6 text-3xl font-bold text-white">{{ $ticket->event->title }}</h1>
                <p class="mt-2 text-ink-300">{{ $ticket->event->starts_at->format('l, F j · g:i A') }}</p>
                <p class="mt-6 text-sm text-ink-200">This is an EventPass ticket. It is validated by staff at the entrance. Keep this QR code to yourself.</p>
                <a href="{{ route('events.show', $ticket->event) }}" class="btn btn-ghost mt-8">View event</a>
            </div>
        @else
            <div>
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-flame/15 text-flame"><x-icon name="x" class="size-8" /></span>
                <h1 class="mt-6 text-3xl font-bold text-white">Not a valid ticket</h1>
                <p class="mt-2 text-sm text-ink-300">We could not find a ticket for this code.</p>
                <a href="{{ route('home') }}" class="btn btn-ghost mt-8">Go home</a>
            </div>
        @endif
    </div>
</x-layouts.app>
