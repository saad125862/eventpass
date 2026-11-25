@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="mt-12 flex items-center justify-between gap-4" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-ghost pointer-events-none opacity-40">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
        @endif
        <span class="text-sm text-ink-300">Page <strong class="text-white">{{ $paginator->currentPage() }}</strong> of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-ghost">Next</a>
        @else
            <span class="btn btn-ghost pointer-events-none opacity-40">Next</span>
        @endif
    </nav>
@endif
