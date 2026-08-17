{{--
    jaunt.ai.confidence-badge — a small meter showing how sure Jaunt is about a
    suggestion or insight, so users can calibrate trust-and-go vs. review
    closely. Ported from ConfidenceBadge.jsx. Honesty over bravado: levels map
    to semantic status color, not the AI iris hue, except "medium" which stays
    iris-neutral (see docs — low = amber/review, high = green/safe, medium = iris).
--}}
@props([
    'level' => 'medium', // low | medium | high
    'showLabel' => true,
])

@php
$bars = ['low' => 1, 'medium' => 2, 'high' => 3][$level] ?? 2;
$labels = ['low' => 'Low confidence', 'medium' => 'Medium confidence', 'high' => 'High confidence'];
$labelText = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'][$level] ?? 'Medium';

$variants = [
    'low'    => 'text-warning border-warning-border bg-warning-subtle',
    'medium' => 'text-ai-text border-ai-border bg-ai-subtle',
    'high'   => 'text-success border-success-border bg-success-subtle',
][$level] ?? 'text-ai-text border-ai-border bg-ai-subtle';
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-flex items-center gap-1.5 h-5 px-2 rounded-full border text-2xs font-semibold whitespace-nowrap {$variants}",
    ]) }}
    title="{{ $labels[$level] ?? $labels['medium'] }}"
>
    <span class="inline-flex items-end gap-[2px]" aria-hidden="true">
        @for ($i = 0; $i < 3; $i++)
            <i class="block w-[3px] h-[9px] rounded-[1px] bg-current {{ $i < $bars ? 'opacity-100' : 'opacity-25' }}"></i>
        @endfor
    </span>
    @if ($showLabel)
        {{ $labelText }}
    @endif
</span>
