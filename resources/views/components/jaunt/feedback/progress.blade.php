{{--
    jaunt.feedback.progress — linear progress bar (determinate or
    indeterminate). Ported from Progress.jsx. For the circular spinner
    counterpart see jaunt.feedback.spinner (Progress.jsx exports both from
    one file; Blade components are one-per-file, so they're split here).

    Indeterminate sliding-bar animation and reduced-motion handling can't be
    expressed as Tailwind utilities (no token-driven keyframe exists for
    "slide 40%-wide bar left to right"), so it's a small scoped <style>
    block, matching the source bundle's own self-contained CSS-in-JS
    convention (see the `CSS` template literal in Progress.jsx) translated
    to a `.j-progress` prefixed class per docs/09-engineering-alignment.md
    bespoke-CSS convention.
--}}
@props([
    'value' => null, // 0-100, or null for indeterminate
    'label' => null,
    'variant' => 'default', // default | ai | success
    'showValue' => false,
])

@php
$indeterminate = is_null($value);
$pct = $indeterminate ? 0 : max(0, min(100, (int) $value));

$barColor = [
    'default' => 'bg-accent',
    'ai'      => 'bg-ai',
    'success' => 'bg-success',
][$variant] ?? 'bg-accent';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label || $showValue)
        <div class="flex justify-between text-xs text-secondary">
            <span>{{ $label }}</span>
            @if ($showValue && !$indeterminate)
                <span class="tabular-nums">{{ $pct }}%</span>
            @endif
        </div>
    @endif

    <div
        class="h-1.5 rounded-full bg-sunken overflow-hidden relative"
        role="progressbar"
        @if (!$indeterminate)
            aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
        @endif
    >
        @if ($indeterminate)
            <div class="j-progress-bar-indeterminate h-full rounded-full {{ $barColor }}"></div>
        @else
            <div
                class="h-full rounded-full {{ $barColor }} transition-[width] duration-slow ease-out"
                style="width: {{ $pct }}%"
            ></div>
        @endif
    </div>
</div>

@once
    <style>
        .j-progress-bar-indeterminate {
            width: 40%;
            animation: j-progress-slide 1.1s var(--ease-standard) infinite;
        }
        @keyframes j-progress-slide {
            0%   { transform: translateX(-120%); }
            100% { transform: translateX(320%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .j-progress-bar-indeterminate { animation-duration: 2.4s; }
        }
    </style>
@endonce
