{{--
    jaunt.data.card — surface container for grouped content (listings, media,
    dashboard tiles). Ported from Card.jsx, which is a compound component
    (Card.Header / Card.Body / Card.Footer / Card.Media / Card.Pad). Blade has
    no dot-notation subcomponents, so the compound parts are modeled as named
    slots on the same tag:

    <x-jaunt.data.card hoverable>
        <x-slot:media src="{{ $photo }}" alt="Harbor at dusk"></x-slot:media>
        <x-slot:header title="Harbor Lighthouse" subtitle="Attraction · North coast"></x-slot:header>
        <x-slot:action><x-jaunt.feedback.badge variant="success" dot>Published</x-jaunt.feedback.badge></x-slot:action>
        <x-slot:body>Body copy…</x-slot:body>
        <x-slot:footer>...</x-slot:footer>
    </x-jaunt.data.card>

    `header` accepts `title`/`subtitle` as slot attributes (`<x-slot:header title="…" subtitle="…"></x-slot:header>`);
    `action` is a sibling slot (not nested inside header — Blade doesn't support
    nested named slots) rendered at the end of the header row.

    For a single padded block (Card.Pad in the source), just pass the default
    slot with no header/body/footer — the fallback padding is applied
    automatically when none of the structured slots are used.
--}}
@props([
    'hoverable' => false,
    'clickable' => false,
    'selected' => false,
])

@php
$tag = $clickable ? 'button' : 'div';

$hasMedia = isset($media);
$hasHeader = isset($header);
$hasBody = isset($body);
$hasFooter = isset($footer);
$hasStructure = $hasMedia || $hasHeader || $hasBody || $hasFooter;

$base = 'bg-card outline outline-1 outline-[color:var(--border-subtle)] -outline-offset-1 rounded-xl shadow-sm overflow-hidden '
    . 'transition-[box-shadow,outline-color,transform] duration-fast ease-standard';

$hoverCls = $hoverable || $clickable
    ? 'hover:shadow-md'
    : '';

$clickableCls = $clickable
    ? 'cursor-pointer text-left w-full block font-inherit text-inherit hover:-translate-y-px focus-visible:outline-none focus-visible:shadow-ring'
    : '';

$selectedCls = $selected ? 'outline-[1.5px] outline-[color:var(--border-selected)]' : '';
@endphp

<{{ $tag }}
    {{ $attributes->merge(['class' => trim("$base $hoverCls $clickableCls $selectedCls")]) }}
    @if ($clickable) type="button" @endif
>
    @if ($hasMedia)
        <img src="{{ $media->src ?? '' }}" alt="{{ $media->alt ?? '' }}" class="block w-full aspect-video object-cover bg-sunken" />
    @endif

    @if ($hasHeader)
        <div class="flex items-start gap-2.5 px-5 pt-4">
            <div class="flex-1 min-w-0">
                @if ($header->title ?? null)
                    <div class="text-lg font-semibold text-primary truncate">{{ $header->title }}</div>
                @endif
                @if ($header->subtitle ?? null)
                    <div class="text-xs text-tertiary mt-0.5">{{ $header->subtitle }}</div>
                @endif
            </div>
            @isset($action)
                {{ $action }}
            @endisset
        </div>
    @endif

    @if ($hasBody)
        <div class="px-5 py-2.5 text-sm text-secondary">
            {{ $body }}
        </div>
    @endif

    @if ($hasFooter)
        <div class="flex items-center gap-2 px-5 py-3 border-t border-subtle">
            {{ $footer }}
        </div>
    @endif

    @if (! $hasStructure)
        <div class="p-5">
            {{ $slot }}
        </div>
    @endif
</{{ $tag }}>
