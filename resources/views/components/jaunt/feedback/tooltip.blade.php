{{-- jaunt.feedback.tooltip — wrap any element; shows on hover/focus. Alpine owns the show state (purely visual). --}}
@props([
    'label',
    'kbd' => null,
    'side' => 'top', // top | bottom | bottom-end | right
    'id' => null,
])

@php
$position = [
    'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-1.5',
    'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-1.5',
    'bottom-end' => 'top-full right-0 mt-1.5',
    'right' => 'left-full top-1/2 -translate-y-1/2 ml-1.5',
][$side];
@endphp

<span
    x-data="{ show: false }"
    @mouseenter="show = true" @mouseleave="show = false"
    @focusin="show = true" @focusout="show = false"
    class="relative inline-flex"
>
    {{ $slot }}
    <span
        @if($id) id="{{ $id }}" @endif
        x-show="show"
        x-transition:enter="transition ease-out duration-fast"
        x-transition:enter-start="opacity-0 translate-y-0.5"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
        role="tooltip"
        class="absolute z-dropdown pointer-events-none whitespace-nowrap rounded-sm px-2 py-1 text-xs shadow-md bg-gray-900 text-gray-25 {{ $position }}"
    >
        {{ $label }}
        @if ($kbd)
            <span class="ml-1.5 opacity-70 font-mono">{{ $kbd }}</span>
        @endif
    </span>
</span>
