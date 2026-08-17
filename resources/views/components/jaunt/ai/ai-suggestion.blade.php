{{--
    jaunt.ai.ai-suggestion — the core "AI-suggests, human-approves" surface.
    Presents AI-generated content (a drafted description, autofilled fields,
    detected duplicates) inline with Accept / Edit / Dismiss — never a
    separate screen. Ported from AISuggestion.jsx.

    Nothing AI-authored is committed without an explicit Accept. Pair with
    <x-jaunt.ai.confidence-badge /> via the `confidence` slot so users can
    calibrate trust. Keep copy grounded ("Drafted from your listing
    details. Review before publishing.") — never overclaim.

    Entrance motion uses ease-spring per the motion philosophy: the spring
    curve is reserved for AI-originated content arriving on screen. Wrap
    the caller's x-show/x-if with x-transition using duration-slow +
    ease-spring, or rely on the default transition below if rendered as a
    plain Alpine-controlled block (see `show` prop).
--}}
@props([
    'label' => 'Suggested by Jaunt',
    'acceptLabel' => 'Accept',
    'onAccept' => null, // optional Alpine/Livewire expression string, e.g. "acceptDraft()" or "$wire.accept()"
    'onEdit' => null,
    'onDismiss' => null,
])

<div
    {{ $attributes->merge([
        'class' => 'rounded-md border border-ai-border bg-ai-subtle overflow-hidden',
    ]) }}
    role="group"
    aria-label="{{ $label }}"
    x-data="{}"
    x-transition:enter="transition ease-spring duration-slow"
    x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
>
    <div class="flex items-center gap-2 px-3 py-2.5">
        <span class="grid place-items-center w-[22px] h-[22px] shrink-0 rounded-sm bg-ai text-white">
            <x-jaunt.icon name="sparkles" size="xs" class="w-3.5 h-3.5" />
        </span>
        <span class="text-sm font-medium text-ai-text">{{ $label }}</span>
        @isset($confidence)
            <span class="ml-auto">{{ $confidence }}</span>
        @endisset
    </div>

    <div class="mx-3 mb-2.5 p-3 rounded-sm bg-card border border-subtle text-sm text-primary leading-normal">
        {{ $slot }}
    </div>

    @if ($onAccept || $onEdit || $onDismiss)
        <div class="flex items-center gap-2 px-3 pb-3">
            @if ($onAccept)
                <button
                    type="button"
                    @click="{{ $onAccept }}"
                    class="inline-flex items-center gap-1.5 h-7 px-[11px] rounded-sm border border-transparent
                        bg-ai text-white text-xs font-medium cursor-pointer
                        transition-colors duration-instant ease-standard hover:brightness-[1.06]
                        focus-visible:outline-none focus-visible:shadow-ring-ai"
                >
                    <x-jaunt.icon name="check" size="xs" class="w-3.5 h-3.5" />
                    {{ $acceptLabel }}
                </button>
            @endif
            @if ($onEdit)
                <button
                    type="button"
                    @click="{{ $onEdit }}"
                    class="inline-flex items-center h-7 px-[11px] rounded-sm border border-transparent
                        bg-transparent text-secondary text-xs font-medium cursor-pointer
                        transition-colors duration-instant ease-standard hover:bg-hover hover:text-primary
                        focus-visible:outline-none focus-visible:shadow-ring"
                >
                    Edit
                </button>
            @endif
            @if ($onDismiss)
                <button
                    type="button"
                    @click="{{ $onDismiss }}"
                    class="inline-flex items-center h-7 px-[11px] rounded-sm border border-transparent
                        bg-transparent text-secondary text-xs font-medium cursor-pointer ml-auto
                        transition-colors duration-instant ease-standard hover:bg-hover hover:text-primary
                        focus-visible:outline-none focus-visible:shadow-ring"
                >
                    Dismiss
                </button>
            @endif
        </div>
    @endif
</div>
