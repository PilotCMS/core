{{--
    jaunt.data.timeline — vertical timeline / activity feed (same component).
    Ported from Timeline.jsx. Use for record history, audit trails, and
    "what changed" panels.

    items: [
        { id, icon?: 'lucide-name', title, meta?, time?, detail?, tone?: 'default'|'accent'|'ai' }
    ]

    `title` may contain simple HTML (e.g. "<b>Mara</b> published Harbor
    Lighthouse") to match the source's `<b>` actor emphasis — it is rendered
    unescaped via {!! !!}, so callers must only pass trusted/sanitized
    strings (never raw user input) into `title`/`detail`/`meta`.

    Semantic order is chronological per the prompt doc (newest first by
    convention) — this component renders `items` in the order given; sort
    before passing in.
--}}
@props([
    'items' => [],
])

@php
$toneNodeClasses = [
    'default' => 'bg-sunken text-secondary border-[color:var(--border-default)]',
    'accent' => 'bg-accent-subtle text-accent-text border-accent-border',
    'ai' => 'bg-ai-subtle text-ai-text border-ai-border',
];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    @foreach ($items as $i => $item)
        @php
            $tone = $item['tone'] ?? 'default';
            $nodeCls = $toneNodeClasses[$tone] ?? $toneNodeClasses['default'];
            $isLast = $i === count($items) - 1;
        @endphp
        <div class="relative flex gap-3 {{ $isLast ? '' : 'pb-[18px]' }}">
            <div class="relative flex-none flex flex-col items-center">
                <div class="w-7 h-7 rounded-full flex-none grid place-items-center border z-10 {{ $nodeCls }}">
                    @if (!empty($item['icon']))
                        <x-jaunt.icon :name="$item['icon']" size="sm" />
                    @endif
                </div>
                @if (! $isLast)
                    <div class="flex-1 w-0.5 bg-[color:var(--border-subtle)]" style="margin: 3px 0 -18px;"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0 pt-1">
                @if (!empty($item['title']))
                    <div class="text-sm text-primary [&_b]:font-semibold">{!! $item['title'] !!}</div>
                @endif
                @if (!empty($item['meta']) || !empty($item['time']))
                    <div class="text-xs text-tertiary mt-0.5 flex items-center gap-2">
                        @if (!empty($item['meta']))
                            <span>{{ $item['meta'] }}</span>
                        @endif
                        @if (!empty($item['time']))
                            <span>· {{ $item['time'] }}</span>
                        @endif
                    </div>
                @endif
                @if (!empty($item['detail']))
                    <div class="mt-2 px-3 py-2.5 rounded-md bg-sunken text-sm text-secondary">
                        {{ $item['detail'] }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
