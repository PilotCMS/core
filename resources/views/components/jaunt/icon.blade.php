{{--
    jaunt.icon — thin wrapper around Lucide (see docs/01-tokens.md, Iconography).
    Renders a data-lucide placeholder; a global `lucide.createIcons()` pass
    (see src/js/main.js) swaps every placeholder for inline SVG. In production,
    swap this implementation for `lucide-react`-equivalent tree-shaken SVGs
    (e.g. blade-ui-kit/blade-icons + a Lucide icon set) so unused icons don't
    ship — the `name`/`size`/`class` contract stays identical either way.
--}}
@props([
    'name',
    'size' => 'md', // xs(14) sm(16) md(20) lg(24) — see docs/01-tokens.md Iconography
])
@php
$px = ['xs' => '14', 'sm' => '16', 'md' => '20', 'lg' => '24'][$size] ?? '20';
@endphp
<i
    data-lucide="{{ $name }}"
    {{ $attributes->merge(['class' => 'inline-block shrink-0']) }}
    style="width:{{ $px }}px;height:{{ $px }}px;stroke-width:1.5"
    aria-hidden="true"
></i>
