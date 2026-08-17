{{-- jaunt.data.avatar — deterministic colored-initials fallback, from Avatar.jsx --}}
@props([
    'name' => '',
    'src' => null,
    'size' => 'md', // xs(20) sm(24) md(32) lg(40) xl(56)
    'square' => false,
    'status' => null, // online | away | offline
])

@php
$px = ['xs' => 20, 'sm' => 24, 'md' => 32, 'lg' => 40, 'xl' => 56][$size] ?? 32;
$fontPx = round($px * 0.4);

$initials = '?';
if (trim($name) !== '') {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
}

$palette = ['--viz-1', '--viz-2', '--viz-3', '--viz-4', '--viz-6', '--viz-7'];
$hash = 0;
foreach (str_split($name) as $char) {
    $hash = ord($char) + (($hash << 5) - $hash);
}
$vizVar = $palette[abs($hash) % count($palette)];

$statusColor = ['online' => 'bg-success', 'away' => 'bg-warning', 'offline' => 'bg-gray-400'][$status] ?? null;
$dotPx = max(7, round($px * 0.28));
@endphp

<span
    {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center flex-none overflow-hidden select-none font-semibold ' . ($square ? 'rounded-sm' : 'rounded-full')]) }}
    style="width:{{ $px }}px;height:{{ $px }}px;font-size:{{ $fontPx }}px;
        {{ $src ? '' : "background: color-mix(in oklab, var($vizVar) 18%, transparent); color: var($vizVar);" }}"
    @if ($name) aria-label="{{ $name }}" @endif
>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" />
    @else
        {{ $initials }}
    @endif

    @if ($statusColor)
        <span
            class="absolute -right-px -bottom-px rounded-full border-2 border-card {{ $statusColor }}"
            style="width:{{ $dotPx }}px;height:{{ $dotPx }}px"
        ></span>
    @endif
</span>
