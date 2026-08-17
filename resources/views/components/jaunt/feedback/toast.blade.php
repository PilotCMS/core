{{--
    jaunt.feedback.toast — a single transient notification. Ported from
    Toast.jsx. Not usually rendered directly by a page author (see
    jaunt.feedback.toast-viewport, which drives the shared queue) — but the
    template is kept here as its own component so a caller who wants a
    one-off, non-queued toast markup (rare) can still `<x-jaunt.feedback.toast>`
    it directly with plain onclose wiring instead of the store.

    Design decision: the viewport owns the auto-dismiss timer and renders
    this same markup inline via x-for + Alpine.store('toasts') rather than
    nesting `<x-jaunt.feedback.toast>` per item, because Blade components
    render server-side once and can't be instantiated dynamically from a
    client-side Alpine loop. toast-viewport.blade.php therefore duplicates
    this component's markup/classes inline — keep the two in sync if you
    change styling here.
--}}
@props([
    'variant' => 'info', // neutral | success | danger | info | ai
    'title' => null,
    'actionLabel' => null,
    'dismissible' => true,
])

@php
$icons = [
    'success' => 'check',
    'danger'  => 'triangle-alert',
    'info'    => 'info',
    'ai'      => 'sparkles',
];
$iconColor = [
    'success' => 'text-success',
    'danger'  => 'text-danger',
    'info'    => 'text-info',
    'ai'      => 'text-ai',
][$variant] ?? '';
$showIcon = $variant !== 'neutral';
@endphp

<div
    {{ $attributes->merge([
        'class' => 'flex items-start gap-2.5 w-[340px] max-w-[calc(100vw-32px)] p-3 pl-3.5 bg-raised text-primary border-[color:var(--border-default)] border rounded-md shadow-lg',
    ]) }}
    role="status"
    aria-live="{{ $variant === 'danger' ? 'assertive' : 'polite' }}"
>
    @if ($showIcon)
        <span class="flex-none mt-px {{ $iconColor }}" aria-hidden="true">
            <x-jaunt.icon :name="$icons[$variant] ?? 'info'" size="sm" />
        </span>
    @endif

    <div class="flex-1 min-w-0 flex flex-col gap-0.5">
        @if ($title)
            <div class="text-sm font-medium">{{ $title }}</div>
        @endif
        @if ($slot->isNotEmpty())
            <div class="text-xs text-secondary">{{ $slot }}</div>
        @endif
        @if ($actionLabel)
            <button type="button" class="self-start mt-1.5 text-xs font-semibold text-accent-text">
                {{ $actionLabel }}
            </button>
        @endif
    </div>

    @if ($dismissible)
        <x-jaunt.forms.icon-button label="Dismiss" size="sm" class="-mt-0.5 -mr-0.5 ml-auto">
            <x-jaunt.icon name="x" size="xs" />
        </x-jaunt.forms.icon-button>
    @endif
</div>
