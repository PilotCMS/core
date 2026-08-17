{{--
    jaunt.feedback.skeleton — shimmering placeholder that mirrors the shape
    of loading content. Prefer over Spinner for structured content (tables,
    cards) since it reduces perceived latency and avoids layout shift.
    Ported from Skeleton.jsx.

    The shimmer sweep is a scoped <style> block (same rationale as
    jaunt.feedback.progress — no token-driven keyframe exists for a
    translateX sweep), using the --dur-* / --ease-standard tokens so it
    collapses to instant/none automatically under prefers-reduced-motion
    (see tokens/motion.css).
--}}
@props([
    'variant' => 'rect', // rect | text | circle
    'width' => null,
    'height' => null,
    'lines' => 1, // for variant="text": number of lines (last is shorter)
    'radius' => null,
])

@php
$radiusCls = match ($variant) {
    'text' => 'rounded-full',
    'circle' => 'rounded-full',
    default => is_null($radius) ? 'rounded-xs' : '',
};

$style = function ($w, $h) use ($radius) {
    $parts = [];
    if ($w) $parts[] = 'width:' . (is_numeric($w) ? $w . 'px' : $w);
    if ($h) $parts[] = 'height:' . (is_numeric($h) ? $h . 'px' : $h);
    if ($radius) $parts[] = 'border-radius:' . (is_numeric($radius) ? $radius . 'px' : $radius);
    return implode(';', $parts);
};
@endphp

@if ($variant === 'text' && $lines > 1)
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }} aria-hidden="true">
        @for ($i = 0; $i < $lines; $i++)
            <span
                class="j-skel block rounded-full h-[0.72em] my-[0.14em]"
                style="width: {{ $i === $lines - 1 ? '60%' : '100%' }}"
            ></span>
        @endfor
    </div>
@else
    <span
        {{ $attributes->merge(['class' => "j-skel block {$radiusCls}"]) }}
        style="{{ $style($width, $height) }}"
        aria-hidden="true"
    ></span>
@endif

@once
    <style>
        .j-skel {
            position: relative;
            overflow: hidden;
            background: var(--surface-sunken);
        }
        .j-skel::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, var(--surface-hover), transparent);
            transform: translateX(-100%);
            animation: j-skel-shimmer 1.4s var(--ease-standard) infinite;
        }
        @keyframes j-skel-shimmer {
            100% { transform: translateX(100%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .j-skel::after { animation: none; }
        }
    </style>
@endonce
