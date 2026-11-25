@props(['text' => true])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="relative grid size-9 place-items-center rounded-xl bg-linear-to-br from-brand-500 to-flame shadow-lg shadow-brand-500/30">
        <x-icon name="ticket" class="size-5 text-white" />
        <span class="absolute inset-0 rounded-xl ring-1 ring-white/25 ring-inset"></span>
    </span>
    @if ($text)
        <span class="font-display text-lg font-bold tracking-tight text-white">Event<span class="text-gradient">Pass</span></span>
    @endif
</span>
