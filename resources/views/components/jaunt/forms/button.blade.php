{{-- jaunt.forms.button — see components-source/components/forms/Button.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'variant' => 'primary', // primary | secondary | ghost | danger | ai
    'size' => 'md',         // sm (30px) | md (36px) | lg (44px)
    'as' => 'button',       // button | a
    'block' => false,
    'pill' => false,
    'selected' => false,
    'loading' => false,
    'iconLeft' => null,
    'iconRight' => null,
])

@php
$variants = [
    'primary'   => 'bg-accent text-on-accent hover:bg-accent-hover active:bg-accent-active',
    'secondary' => 'bg-sunken text-primary hover:bg-gray-200 active:bg-gray-300 dark:bg-active dark:hover:bg-[rgba(255,255,255,0.14)] dark:active:bg-[rgba(255,255,255,0.18)]',
    'ghost'     => 'text-secondary hover:bg-hover hover:text-primary active:bg-active',
    'danger'    => 'bg-danger !text-white hover:bg-[color-mix(in_oklab,var(--danger),#000_12%)] active:bg-[color-mix(in_oklab,var(--danger),#000_20%)] focus-visible:shadow-ring-danger',
    'ai'        => 'bg-ai-subtle text-ai-text border-ai-border hover:border-ai focus-visible:shadow-ring-ai',
];
$sizes = [
    'sm' => 'h-control-sm text-xs font-medium',
    'md' => 'h-control text-sm leading-[var(--lh-snug)] font-medium',
    'lg' => 'h-control-lg text-base font-medium',
];
$paddings = ['sm' => 'px-2.5', 'md' => 'px-3.5', 'lg' => 'px-[18px]'];
$shape = $pill ? 'rounded-full px-5' : 'rounded-sm '.$paddings[$size];
$tag = $as === 'a' ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center border border-transparent
            whitespace-nowrap select-none cursor-pointer gap-inline tracking-[var(--ls-snug)]
            transition-[color,background-color,border-color,box-shadow,transform] duration-instant ease-standard active:scale-[0.98]
            focus-visible:outline-none focus-visible:shadow-ring
            disabled:opacity-50 disabled:pointer-events-none disabled:active:scale-100
            [&_svg]:size-icon-sm [&_svg]:flex-none
            aria-pressed:bg-card aria-pressed:text-primary aria-pressed:font-medium
            aria-pressed:shadow-[inset_0_0_0_1.5px_var(--border-selected)] dark:aria-pressed:bg-active
            {$variants[$variant]} {$sizes[$size]} {$shape}" . ($block ? ' flex w-full' : ''),
    ]) }}
    @if ($tag === 'button' && !isset($attributes['type'])) type="button" @endif
    @if ($loading) disabled aria-busy="true" @endif
    @if ($selected) aria-pressed="true" @endif
>
    @if ($loading)
        <span class="size-4 rounded-full border-2 border-current border-t-transparent animate-spin [animation-duration:0.6s] motion-reduce:[animation-duration:1.2s]" aria-hidden="true"></span>
    @else
        {{ $iconLeft }}
    @endif
    {{ $slot }}
    @unless ($loading)
        {{ $iconRight }}
    @endunless
</{{ $tag }}>
