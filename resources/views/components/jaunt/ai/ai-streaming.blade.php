{{--
    jaunt.ai.ai-streaming — how Jaunt shows AI *working* and *responding*.
    A pulsing sparkle mark plus either the "thinking…" dots (before tokens)
    or streaming text with a soft blinking caret (during/after). Ported from
    AIStreaming.jsx. Motion communicates state, never entertains.

    Lifecycle: thinking -> streaming (caret) -> settled (no caret). The mark
    stays the same throughout for continuity.

    Typewriter implementation note: this is a static Blade component with no
    live backend stream, so `typewriter` drives a client-side Alpine
    x-data/setInterval that reveals `text` progressively at `speed` ms/char —
    a mock/demo affordance only. In production, bind a real SSE or Livewire
    stream instead: pass `streaming` + a `text` prop that grows as chunks
    arrive (e.g. from a Livewire `wire:stream` target or an Alpine store fed
    by an EventSource), and drop `typewriter` entirely — the caret already
    renders correctly off `streaming` alone without any client-side timer.

    A11y: role="status" aria-live="polite" so assistive tech hears the
    settled result once, not every token. Caret/dots respect
    prefers-reduced-motion via the underlying animate-* utilities + the
    global reduced-motion media query in tokens/motion.css.
--}}
@props([
    'text' => '',
    'streaming' => false,
    'thinking' => false,
    'thinkingLabel' => 'Jaunt is thinking',
    'typewriter' => false,
    'speed' => 18, // ms/char, typewriter mode only
])

<div
    {{ $attributes->merge(['class' => 'flex gap-2.5']) }}
    role="status"
    aria-live="polite"
    @if ($typewriter)
        x-data="{
            full: @js($text),
            shown: '',
            i: 0,
            timer: null,
            get busy() { return {{ $thinking ? 'true' : 'false' }} || {{ $streaming ? 'true' : 'false' }} || this.shown.length < this.full.length; },
            start() {
                clearInterval(this.timer);
                this.shown = '';
                this.i = 0;
                this.timer = setInterval(() => {
                    this.i++;
                    this.shown = this.full.slice(0, this.i);
                    if (this.i >= this.full.length) clearInterval(this.timer);
                }, {{ (int) $speed }});
            },
        }"
        x-init="start()"
    @endif
>
    <span
        @if ($typewriter)
            :class="busy ? 'j-ai-stream-busy' : ''"
        @endif
        class="grid place-items-center w-6 h-6 shrink-0 rounded-sm bg-ai-subtle text-ai border border-ai-border
            {{ ($thinking || $streaming) ? 'j-ai-stream-busy' : '' }}"
    >
        <x-jaunt.icon name="sparkles" size="xs" class="w-3.5 h-3.5" />
    </span>

    <div class="flex-1 min-w-0 text-sm leading-normal text-primary">
        @if ($thinking)
            <span class="inline-flex items-center gap-2 text-ai-text text-sm">
                {{ $thinkingLabel }}
                <span class="inline-flex gap-[3px]" aria-hidden="true">
                    <i class="j-ai-stream-dot w-[5px] h-[5px] rounded-full bg-ai" style="animation-delay:0ms"></i>
                    <i class="j-ai-stream-dot w-[5px] h-[5px] rounded-full bg-ai" style="animation-delay:150ms"></i>
                    <i class="j-ai-stream-dot w-[5px] h-[5px] rounded-full bg-ai" style="animation-delay:300ms"></i>
                </span>
            </span>
        @elseif ($typewriter)
            <span x-text="shown"></span><span x-show="busy" class="j-ai-stream-caret" aria-hidden="true"></span>
        @else
            {{ $text }}@if ($streaming)<span class="j-ai-stream-caret" aria-hidden="true"></span>@endif
        @endif
    </div>
</div>

@once
    <style>
        @keyframes j-ai-pulse { 0%, 100% { box-shadow: 0 0 0 0 var(--ai-glow); } 50% { box-shadow: 0 0 0 5px transparent; } }
        @keyframes j-ai-blink { 50% { opacity: 0; } }
        @keyframes j-ai-bounce { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

        .j-ai-stream-busy { animation: j-ai-pulse 1.4s var(--ease-standard) infinite; }
        .j-ai-stream-caret {
            display: inline-block;
            width: 2px;
            height: 1em;
            margin-left: 1px;
            vertical-align: text-bottom;
            background: var(--ai-accent);
            animation: j-ai-blink 1s steps(2) infinite;
        }
        .j-ai-stream-dot {
            display: inline-block;
            animation: j-ai-bounce 1.1s var(--ease-standard) infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .j-ai-stream-busy,
            .j-ai-stream-caret,
            .j-ai-stream-dot { animation: none; }
        }
    </style>
@endonce
