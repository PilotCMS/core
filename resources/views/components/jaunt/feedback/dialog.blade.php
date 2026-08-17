{{--
    jaunt.feedback.dialog — modal for focused decisions and confirmations.
    Closes on Esc, scrim click, or the close X. Ported from Dialog.jsx,
    following the scrim+panel Alpine pattern established in
    components/navigation/command-palette.blade.php.

    Open state: unlike CommandPalette (which is a single global instance
    driven by Alpine.store('commandPalette')), a page can have several
    distinct Dialogs (delete-confirm, invite-user, ...), so each instance
    owns its own local x-data boolean seeded from the `open` prop rather
    than a shared store. Toggle it from the outside via x-on/wire:click by
    targeting the same x-data scope, or simply re-render the component with
    a different `open` value from Livewire.
--}}
@props([
    'open' => false,
    'title' => null,
    'description' => null,
    'icon' => null, // lucide icon name, e.g. "trash-2"
    'variant' => 'default', // default | danger
    'size' => 'md', // sm | md | lg
])

@php
$sizes = [
    'sm' => 'max-w-[400px]',
    'md' => 'max-w-[440px]',
    'lg' => 'max-w-[640px]',
][$size];

$headIconCls = $variant === 'danger'
    ? 'bg-danger-subtle text-danger'
    : 'bg-sunken text-secondary';
@endphp

<div
    x-data="{ open: {{ $open ? 'true' : 'false' }} }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    @mousedown="if ($event.target === $event.currentTarget) open = false"
    class="fixed inset-0 z-overlay grid place-items-center p-6 bg-overlay backdrop-blur-[2px]"
    x-transition:enter="transition ease-out duration-base" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-instant" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    {{ $attributes }}
>
    <div
        role="dialog" aria-modal="true" aria-label="{{ $title }}"
        class="relative z-dialog w-full {{ $sizes }} max-h-[calc(100vh-48px)] overflow-auto bg-raised border-[color:var(--border-default)] border shadow-xl rounded-2xl"
        x-transition:enter="transition ease-out duration-slow"
        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    >
        @if ($title || $description || $icon)
            <div class="flex items-start gap-3 px-5 pt-5">
                @if ($icon)
                    <span class="flex-none grid place-items-center w-[34px] h-[34px] rounded-md {{ $headIconCls }}">
                        <x-jaunt.icon :name="$icon" size="sm" />
                    </span>
                @endif
                <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                    @if ($title)
                        <div class="text-lg font-semibold text-primary">{{ $title }}</div>
                    @endif
                    @if ($description)
                        <div class="text-sm text-secondary">{{ $description }}</div>
                    @endif
                </div>
                <x-jaunt.forms.icon-button
                    label="Close"
                    size="sm"
                    class="-mt-1 -mr-1"
                    @click="open = false"
                >
                    <x-jaunt.icon name="x" size="xs" />
                </x-jaunt.forms.icon-button>
            </div>
        @endif

        @if ($slot->isNotEmpty())
            <div class="px-5 pt-3.5 pb-1 text-sm text-secondary">
                {{ $slot }}
            </div>
        @endif

        @isset($footer)
            <div class="flex justify-end gap-2 px-5 pt-4 pb-5">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
