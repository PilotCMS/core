{{--
    jaunt.feedback.alert — inline, persistent contextual message tied to a
    place on the page (validation summary, feature notice, AI heads-up).
    For transient confirmations use jaunt.feedback.toast instead.
    Ported from Alert.jsx. Static markup — the only interactive bit is the
    optional dismiss button, and even that is a plain click handler the
    caller wires up (Livewire wire:click, Alpine, or a page script) since
    Alert itself carries no state of its own (no onClose prop equivalent in
    Blade — pass slot content / attributes to control visibility from the
    outside, e.g. wrap in x-show or an @if in the parent).
--}}
@props([
    'variant' => 'neutral', // neutral | info | success | warning | danger | ai
    'title' => null,
    'dismissible' => false,
])

@php
$variants = [
    'neutral' => ['wrap' => 'bg-card border-[color:var(--border-default)]', 'icon' => 'text-secondary'],
    'info'    => ['wrap' => 'bg-info-subtle border-info-border', 'icon' => 'text-info'],
    'success' => ['wrap' => 'bg-success-subtle border-success-border', 'icon' => 'text-success'],
    'warning' => ['wrap' => 'bg-warning-subtle border-warning-border', 'icon' => 'text-warning'],
    'danger'  => ['wrap' => 'bg-danger-subtle border-danger-border', 'icon' => 'text-danger'],
    'ai'      => ['wrap' => 'bg-ai-subtle border-ai-border', 'icon' => 'text-ai'],
][$variant];

$icons = [
    'neutral' => 'info',
    'info'    => 'info',
    'success' => 'check',
    'warning' => 'triangle-alert',
    'danger'  => 'triangle-alert',
    'ai'      => 'sparkles',
][$variant] ?? 'info';

$role = $variant === 'danger' ? 'alert' : 'status';
@endphp

<div
    {{ $attributes->merge([
        'class' => "flex gap-2.5 p-3.5 rounded-md border {$variants['wrap']}",
    ]) }}
    role="{{ $role }}"
    @if ($dismissible) x-data="{ dismissed: false }" x-show="!dismissed" @endif
>
    <span class="flex-none mt-px {{ $variants['icon'] }}" aria-hidden="true">
        <x-jaunt.icon :name="$icons" size="sm" />
    </span>

    <div class="flex-1 min-w-0 flex flex-col gap-0.5">
        @if ($title)
            <div class="text-sm font-medium text-primary">{{ $title }}</div>
        @endif
        @if ($slot->isNotEmpty())
            <div class="text-sm text-secondary">{{ $slot }}</div>
        @endif
        @isset($actions)
            <div class="flex gap-2 mt-2">{{ $actions }}</div>
        @endisset
    </div>

    @if ($dismissible)
        <x-jaunt.forms.icon-button
            label="Dismiss"
            size="sm"
            class="-mt-0.5 -mr-1 ml-auto"
            @click="dismissed = true"
        >
            <x-jaunt.icon name="x" size="xs" />
        </x-jaunt.forms.icon-button>
    @endif
</div>
