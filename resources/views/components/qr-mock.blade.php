@props(['seed' => 1, 'size' => 9])
{{-- Decorative QR-style pattern (not scannable); real tickets use bacon-qr-code. --}}
@php
    $cell = fn ($x, $y) => ((($x * 73856093) ^ ($y * 19349663) ^ ($seed * 83492791)) % 7) < 3;
    $finder = fn ($x, $y) => ($x < 3 && $y < 3) || ($x >= $size - 3 && $y < 3) || ($x < 3 && $y >= $size - 3);
@endphp
<svg viewBox="0 0 {{ $size }} {{ $size }}" {{ $attributes->merge(['class' => 'size-full']) }} shape-rendering="crispEdges" aria-hidden="true">
    <rect width="{{ $size }}" height="{{ $size }}" fill="#fff" rx=".5"/>
    @for ($y = 0; $y < $size; $y++)
        @for ($x = 0; $x < $size; $x++)
            @if ($finder($x, $y) ? ($x % ($size - 3) === 0 || $y % ($size - 3) === 0 || ($x % ($size - 3) === 1 && $y % ($size - 3) === 1)) : $cell($x, $y))
                <rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#0b0b14"/>
            @endif
        @endfor
    @endfor
</svg>
