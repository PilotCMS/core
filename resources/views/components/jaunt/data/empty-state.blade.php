{{--
    jaunt.data.empty-state — zero state for empty lists/tables/search results.
    Ported from EmptyState.jsx. Jaunt's voice: an invitation with a clear next
    step, never an apology.

    <x-jaunt.data.empty-state
        icon="calendar"
        title="Nothing scheduled"
        description="Import a calendar or add your first event to get started.">
        <x-slot:actions>
            <x-jaunt.forms.button variant="primary">Add event</x-jaunt.forms.button>
            <x-jaunt.forms.button variant="secondary">Import calendar</x-jaunt.forms.button>
        </x-slot:actions>
    </x-jaunt.data.empty-state>

    <x-jaunt.data.empty-state variant="ai" icon="sparkles" title="Let Jaunt draft it"
        description="Generate a first pass from your listing details.">
        <x-slot:actions><x-jaunt.forms.button variant="ai">Generate</x-jaunt.forms.button></x-slot:actions>
    </x-jaunt.data.empty-state>
--}}
@props([
    'icon' => null,
    'title' => null,
    'description' => null,
    'variant' => 'default', // default | ai
])

@php
$iconWrapCls = $variant === 'ai'
    ? 'bg-ai-subtle text-ai-text border-ai-border'
    : 'bg-sunken text-tertiary border-subtle';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center px-6 py-11 gap-1']) }}>
    @if ($icon)
        <div class="grid place-items-center w-12 h-12 mb-2.5 rounded-lg border {{ $iconWrapCls }}">
            <x-jaunt.icon :name="$icon" size="lg" />
        </div>
    @endif

    @if ($title)
        <div class="text-lg font-semibold text-primary">{{ $title }}</div>
    @endif

    @if ($description)
        <div class="text-sm text-secondary max-w-[340px] mt-0.5">{{ $description }}</div>
    @endif

    @isset($actions)
        <div class="flex items-center gap-2 mt-3.5">
            {{ $actions }}
        </div>
    @endisset
</div>
