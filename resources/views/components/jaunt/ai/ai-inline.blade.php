{{--
    jaunt.ai.ai-inline — the smallest AI touchpoint, lives *inside* a field so
    AI never feels like a separate app. Ported from AIInline.jsx.

    Two modes:
      - "ghost"   inline ghost-text completion + a Tab-to-accept chip
                   (pass `suggestion`, wire `onAccept` via attributes, e.g.
                   x-on:click / wire:click on the outer tag).
      - "trigger" a subtle "Autofill with AI" text button, e.g. dropped into
                   an Input's `suffix` slot.

    Signal: always iris + the Sparkles glyph — the one consistent "this is AI"
    mark. Keyboard: Tab accepts a ghost suggestion, Esc dismisses (wire the
    keydown handler at the field level via attributes/x-on since AIInline
    itself carries no state).
--}}
@props([
    'mode' => 'trigger', // ghost | trigger
    'suggestion' => null,
    'label' => 'Autofill with AI',
])

@if ($mode === 'ghost')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
        <span class="text-tertiary not-italic" aria-hidden="false">{{ $suggestion }}</span>
        <button
            type="button"
            class="inline-flex items-center gap-1 h-[22px] px-[7px] rounded-xs border border-ai-border bg-ai-subtle
                text-ai-text text-2xs font-medium cursor-pointer transition-colors duration-instant ease-standard
                hover:border-ai focus-visible:outline-none focus-visible:shadow-ring-ai"
        >
            <x-jaunt.icon name="sparkles" size="xs" class="text-ai w-[13px] h-[13px]" />
            <span>Accept</span>
            <span class="font-mono text-2xs opacity-70">Tab</span>
        </button>
    </span>
@else
    <button
        type="button"
        {{ $attributes->merge([
            'class' => 'inline-flex items-center gap-[5px] h-6 px-2 rounded-sm border border-transparent
                bg-transparent text-ai-text text-xs font-medium cursor-pointer
                transition-colors duration-instant ease-standard
                hover:bg-ai-subtle focus-visible:outline-none focus-visible:shadow-ring-ai',
        ]) }}
    >
        <x-jaunt.icon name="sparkles" size="xs" class="text-ai w-3.5 h-3.5" />
        <span>{{ $label }}</span>
    </button>
@endif
