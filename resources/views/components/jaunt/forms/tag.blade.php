{{-- jaunt.forms.tag — see components-source/components/forms/Tag.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'variant' => 'default', // default | outline | accent | success | warning | danger | info | ai
    'dot' => false,
    'onRemove' => null, // pass a string of Alpine/Livewire JS, e.g. "removeTag(id)" — rendered as @click
])

@php
$variants = [
    'default' => 'bg-sunken text-secondary',
    'outline' => 'bg-transparent border-[color:var(--border-default)] text-secondary',
    'accent'  => 'bg-accent-subtle text-accent-text',
    'success' => 'bg-success-subtle text-success',
    'warning' => 'bg-warning-subtle text-warning',
    'danger'  => 'bg-danger-subtle text-danger',
    'info'    => 'bg-info-subtle text-info',
    'ai'      => 'bg-ai-subtle text-ai-text border-[color:var(--ai-border)]',
];
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-flex items-center gap-[5px] h-[22px] px-2 rounded-xs text-xs font-medium border border-transparent whitespace-nowrap max-w-full {$variants[$variant]}",
    ]) }}
>
    @if ($dot)
        <span class="w-[7px] h-[7px] rounded-full flex-none bg-current" aria-hidden="true"></span>
    @endif
    <span class="overflow-hidden text-ellipsis">{{ $slot }}</span>
    @if ($onRemove)
        <button
            type="button"
            @click="{{ $onRemove }}"
            aria-label="Remove"
            class="inline-flex -mr-[3px] p-px border-none bg-transparent text-current cursor-pointer rounded-xs opacity-70 hover:opacity-100 hover:bg-hover transition-colors duration-instant ease-standard"
        >
            <x-jaunt.icon name="x" size="xs" />
        </button>
    @endif
</span>
</content>
