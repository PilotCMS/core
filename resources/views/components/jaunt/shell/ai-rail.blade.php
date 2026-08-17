{{--
    jaunt.shell.ai-rail — "Ask Jaunt": the highest-bandwidth AI surface in the
    shell, for open-ended questions that don't belong inline in a field or a
    single suggestion card. Ported from AIRail() in ui_kits-source/app/App.jsx
    + the `.ai-rail*` rules in ui_kits-source/app/app.css (hand-translated to
    Tailwind + semantic tokens per docs/01-tokens.md).

    Opened from anywhere in the shell via the global Alpine store — the
    topbar's sparkles button (components/shell/topbar.blade.php) and the
    ⌘J shortcut (src/js/main.js) both just flip `$store.aiRail.open`. This
    component is the thing that actually renders when that flips true.

    Non-modal by design: the source .ai-rail has no scrim (unlike the
    command palette's bg-overlay backdrop) — it's a slide-in panel you can
    work alongside, not a blocking dialog. Fixed top/right/bottom, so the
    topbar and sidebar stay usable underneath it.

    Motion: entrance uses duration-slower + ease-spring, not the source
    CSS's plain ease-out — per docs/00-philosophy.md Motion Philosophy,
    ease-spring is reserved exclusively for AI-originated content arriving
    on screen, and this rail *is* that moment (see docs/01-tokens.md, the
    `dur-slower` token is literally annotated "AI rail entrance"). It also
    matches the ease-spring entrance already used by ai-suggestion.blade.php,
    so the whole AI surface family enters with one consistent signature.

    Composition only: this component wires layout + open/close state and
    delegates all AI-pattern rendering to the already-built primitives
    (ai-streaming for the thinking->answer phases, ai-suggestion +
    confidence-badge for the resulting recommendation). It does not
    reimplement their logic.

    Content: a grounded referral-drop analysis (mirrors the App.jsx source
    example) — never a generic "how can I help you" chatbot opener. Ask
    Jaunt answers the question that was actually asked.
--}}
@props([
    'question' => 'Why did partner referrals drop this week?',
    'thinkingLabel' => 'Analyzing referral sources',
    'answer' => 'Partner referrals fell 3.2% week-over-week. 82% of the drop traces to the events page — the new layout pushed partner links below the fold. Three partners account for most of the loss.',
    'recommendationLabel' => 'Recommended fix',
    'recommendation' => 'Restore partner links to the events page header. Estimated recovery: ~2,600 referrals/month.',
    'confidence' => 'medium',
    'thinkingMs' => 1400,
])

<template x-if="$store.aiRail.open">
    <div
        x-data="{
            phase: 'thinking',
            timer: null,
            start() {
                clearTimeout(this.timer);
                this.phase = 'thinking';
                this.timer = setTimeout(() => { this.phase = 'answer'; }, {{ (int) $thinkingMs }});
            },
            close() { $store.aiRail.open = false; },
        }"
        x-init="start()"
        @keydown.escape.window="close()"
        role="dialog"
        aria-label="Ask Jaunt"
        {{ $attributes->merge([
            'class' => 'fixed top-0 right-0 bottom-0 z-dialog flex flex-col w-[360px]
                bg-raised border-l border-subtle shadow-xl',
        ]) }}
        x-transition:enter="transition ease-spring duration-slower"
        x-transition:enter-start="opacity-0 translate-x-5"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-standard duration-fast"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-5"
    >
        <div class="flex items-center gap-2 px-4 py-3.5 border-b border-subtle">
            <span class="grid place-items-center w-6 h-6 shrink-0 rounded-sm bg-ai text-white">
                <x-jaunt.icon name="sparkles" size="xs" class="w-3.5 h-3.5" />
            </span>
            <span class="flex-1 min-w-0 text-md font-semibold text-primary truncate">Ask Jaunt</span>
            <x-jaunt.forms.icon-button label="Close" @click="close()">
                <x-jaunt.icon name="x" size="sm" />
            </x-jaunt.forms.icon-button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto p-4 flex flex-col gap-4">
            <div class="self-end max-w-[85%] bg-active text-primary text-sm leading-normal px-3 py-2.5 rounded-[12px_12px_3px_12px]">
                {{ $question }}
            </div>

            <template x-if="phase === 'thinking'">
                <x-jaunt.ai.ai-streaming thinking thinking-label="{{ $thinkingLabel }}" />
            </template>

            <template x-if="phase === 'answer'">
                <div class="flex flex-col gap-4">
                    <x-jaunt.ai.ai-streaming text="{{ $answer }}" typewriter />

                    <x-jaunt.ai.ai-suggestion
                        label="{{ $recommendationLabel }}"
                        accept-label="Apply fix"
                        on-accept="close()"
                        on-dismiss="close()"
                    >
                        <x-slot:confidence>
                            <x-jaunt.ai.confidence-badge level="{{ $confidence }}" />
                        </x-slot:confidence>
                        {{ $recommendation }}
                    </x-jaunt.ai.ai-suggestion>
                </div>
            </template>
        </div>
    </div>
</template>
