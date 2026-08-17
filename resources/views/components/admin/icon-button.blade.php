@props([
    'iconOnly' => false,
    'variant' => 'ghost', // ghost | primary | danger | ghost-danger
    'size' => 'md',       // sm | md
])

@php
    $href = $attributes->get('href');
    $tag = $href ? 'a' : 'button';
    $baseClasses = 'group relative isolate inline-flex items-center justify-center gap-x-2 rounded-lg border text-base/6 font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 cursor-default transition-colors';
    $sizeClasses = match (true) {
        $iconOnly && $size === 'sm' => 'p-[calc(theme(spacing.1.5)-1px)] [&_[data-slot=icon]]:size-4',
        $iconOnly && $size === 'md' => 'p-[calc(theme(spacing.2)-1px)] [&_[data-slot=icon]]:size-5',
        $size === 'sm' => 'px-[calc(theme(spacing.2.5)-1px)] py-[calc(theme(spacing.1.5)-1px)] text-sm/6 [&_[data-slot=icon]]:size-4',
        default => 'px-[calc(theme(spacing.3.5)-1px)] py-[calc(theme(spacing.2.5)-1px)] sm:px-[calc(theme(spacing.3)-1px)] sm:py-[calc(theme(spacing.1.5)-1px)] sm:text-sm/6 [&_[data-slot=icon]]:size-5 sm:[&_[data-slot=icon]]:size-4',
    };
    $iconSlotClasses = '[&_[data-slot=icon]]:-mx-0.5 [&_[data-slot=icon]]:my-0.5 sm:[&_[data-slot=icon]]:my-1 [&_[data-slot=icon]]:shrink-0 [&_[data-slot=icon]]:self-center';
    $variantClasses = match ($variant) {
        'primary' => 'border-transparent bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 [&_[data-slot=icon]]:text-zinc-400 [&_[data-slot=icon]]:group-hover:text-zinc-300',
        'danger' => 'border-transparent bg-red-600 text-white hover:bg-red-500 [&_[data-slot=icon]]:text-red-200 [&_[data-slot=icon]]:group-hover:text-red-100',
        'ghost-danger' => 'border-transparent bg-transparent text-zinc-700 hover:bg-zinc-100 hover:text-red-600 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-red-400 [&_[data-slot=icon]]:text-zinc-500 [&_[data-slot=icon]]:group-hover:text-red-600 dark:[&_[data-slot=icon]]:text-zinc-400 dark:[&_[data-slot=icon]]:group-hover:text-red-400',
        default => 'border-transparent bg-transparent text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 [&_[data-slot=icon]]:text-zinc-500 [&_[data-slot=icon]]:group-hover:text-zinc-600 dark:[&_[data-slot=icon]]:text-zinc-400 dark:[&_[data-slot=icon]]:group-hover:text-zinc-300',
    };
@endphp

<{{ $tag }}
    @if($tag === 'button')
        type="button"
    @else
        href="{{ $href }}"
    @endif
    {{ $attributes->except('href')->merge(['class' => $baseClasses . ' ' . $sizeClasses . ' ' . $iconSlotClasses . ' ' . $variantClasses]) }}
>
    {{-- Touch target for mobile (minimum 44px) --}}
    <span class="absolute top-1/2 left-1/2 size-[max(100%,2.75rem)] -translate-x-1/2 -translate-y-1/2 pointer-fine:hidden" aria-hidden="true"></span>
    @if(isset($icon))
        <span data-slot="icon">{{ $icon }}</span>
    @endif
    @if(!$iconOnly && trim((string) $slot) !== '')
        <span>{{ $slot }}</span>
    @endif
</{{ $tag }}>
