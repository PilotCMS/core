{{-- jaunt.feedback.badge — status pill / count --}}
@props([
    'variant' => 'neutral', // neutral | accent | success | warning | danger | info | ai | count
    'dot' => false,
])

@php
$variants = [
    'neutral' => 'bg-sunken text-secondary border-transparent',
    'accent'  => 'bg-accent-subtle text-accent-text border-transparent',
    'success' => 'bg-success-subtle text-success border-transparent',
    'warning' => 'bg-warning-subtle text-warning border-transparent',
    'danger'  => 'bg-danger-subtle text-danger border-transparent',
    'info'    => 'bg-info-subtle text-info border-transparent',
    'ai'      => 'bg-ai-subtle text-ai-text border-ai-border',
    'count'   => 'bg-accent text-on-accent border-transparent justify-center min-w-[18px] h-[18px] px-[5px] tabular-nums',
];
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-flex items-center gap-1 h-5 px-[7px] rounded-full text-xs font-medium
            border whitespace-nowrap {$variants[$variant]}",
    ]) }}
>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span>
    @endif
    {{ $slot }}
</span>
