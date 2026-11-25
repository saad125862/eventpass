@php $pct = $total ? (int) round($checkedIn / $total * 100) : 0; @endphp
<x-layouts.app :title="'Attendees · '.$event->title">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('organizer.dashboard') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-ink-300 transition hover:text-white"><x-icon name="arrow" class="size-4 rotate-180" /> Dashboard</a>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">Attendees</p><h1 class="mt-2 text-3xl font-bold text-white sm:text-4xl">{{ $event->title }}</h1></div>
            <div class="flex gap-2">
                <a href="{{ route('organizer.events.export', $event) }}" class="btn btn-ghost"><x-icon name="download" class="size-4" /> Export CSV</a>
                <a href="{{ route('organizer.events.scan', $event) }}" class="btn btn-primary"><x-icon name="scan" class="size-4" /> Open scanner</a>
            </div>
        </div>

        <div class="card mt-8 p-6">
            <div class="flex items-center justify-between text-sm"><span class="text-ink-300">Checked in</span><span class="font-semibold text-white"><span data-count-to="{{ $checkedIn }}">{{ $checkedIn }}</span> / {{ $total }}</span></div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-linear-to-r from-mint to-brand-400 transition-all duration-1000" style="width: {{ $pct }}%"></div></div>
        </div>

        <div class="card mt-6 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="border-b border-white/8 bg-ink-900/60 text-xs tracking-wider text-ink-300 uppercase">
                        <tr><th class="px-5 py-3 font-semibold">Ticket</th><th class="px-5 py-3 font-semibold">Attendee</th><th class="px-5 py-3 font-semibold">Type</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 font-semibold">Checked in</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/8">
                        @forelse ($tickets as $t)
                            <tr class="transition hover:bg-white/[0.03]">
                                <td class="px-5 py-3.5 font-mono text-xs font-semibold tracking-wider text-white"><a href="{{ route('tickets.show', $t) }}" class="hover:text-brand-300">{{ $t->shortCode() }}</a></td>
                                <td class="px-5 py-3.5"><p class="font-semibold text-white">{{ $t->user->name }}</p><p class="text-xs text-ink-300">{{ $t->user->email }}</p></td>
                                <td class="px-5 py-3.5 text-ink-200">{{ $t->ticketType->name }}</td>
                                <td class="px-5 py-3.5"><span class="badge {{ $t->status->badge() }}">{{ $t->status->label() }}</span></td>
                                <td class="px-5 py-3.5 text-ink-300">{{ $t->checked_in_at?->format('M j, g:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center text-ink-300">No tickets sold yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <x-pager :paginator="$tickets" />
    </div>
</x-layouts.app>
