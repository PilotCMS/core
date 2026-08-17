{{-- jaunt.forms.icon-button — square, icon-only control. Always pass a label. --}}
@props([
    'label',
    'size' => 'md', // sm(30) md(36) lg(44)
    'solid' => false,
    'active' => false,
])

@php
$sizes = [
    'sm' => 'w-control-sm h-control-sm [&_svg]:size-[15px]',
    'md' => 'w-control h-control [&_svg]:size-icon-md',
    'lg' => 'w-control-lg h-control-lg [&_svg]:size-icon-lg',
];
$solidCls = $solid ? 'bg-card shadow-xs' : '';
$activeCls = $active ? 'bg-accent-subtle text-accent-text' : '';
@endphp

<button
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center rounded-full cursor-pointer
            text-secondary transition-[color,background-color,box-shadow,transform] duration-instant ease-standard
            enabled:hover:bg-hover enabled:hover:text-primary
            enabled:active:bg-active enabled:active:scale-[0.94]
            focus-visible:outline-none focus-visible:shadow-ring
            disabled:opacity-40 disabled:cursor-not-allowed
            {$sizes[$size]} {$solidCls} {$activeCls}",
        'type' => 'button',
    ]) }}
    aria-label="{{ $label }}"
    title="{{ $label }}"
    @if ($active) aria-pressed="true" @endif
>
    {{ $slot }}
</button>
