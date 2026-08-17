{{--
    jaunt.navigation.command-palette — the universal ⌘K entry point: navigate,
    act, or ask AI. Ported from CommandPalette.jsx. Groups are passed as a
    plain array (icons are Lucide names, not elements — Blade can't pass JSX).

    groups: [{ label: string, items: [{ id, label, icon, hint?, kbd? }] }]

    Open state lives in the global Alpine.store('commandPalette') (see
    src/js/main.js) so any trigger in the shell (sidebar search button,
    topbar search icon, ⌘K) can open it without prop drilling.
--}}
@props([
    'groups' => [],
    'placeholder' => 'Search or type a command…',
    'aiEnabled' => true,
])

<template x-if="$store.commandPalette.open">
    <div
        x-data="{
            q: '',
            active: 0,
            groups: {{ Str::of(json_encode($groups))->toHtmlString() }},
            aiEnabled: {{ $aiEnabled ? 'true' : 'false' }},
            get rows() {
                const rows = [];
                if (this.aiEnabled && this.q.trim()) {
                    rows.push({ ai: true, id: '__ask', label: `Ask Jaunt: “${this.q.trim()}”`, hint: 'AI', icon: 'sparkles' });
                }
                this.groups.forEach(g => {
                    const items = g.items.filter(it => it.label.toLowerCase().includes(this.q.toLowerCase()));
                    if (items.length) rows.push({ group: g.label });
                    items.forEach(it => rows.push(it));
                });
                return rows;
            },
            get selectable() { return this.rows.filter(r => !r.group); },
            select(row) {
                this.$dispatch('command-select', row);
                this.close();
            },
            close() { $store.commandPalette.open = false; },
            onKeydown(e) {
                if (e.key === 'Escape') this.close();
                else if (e.key === 'ArrowDown') { e.preventDefault(); this.active = Math.min(this.active + 1, this.selectable.length - 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = Math.max(this.active - 1, 0); }
                else if (e.key === 'Enter') { e.preventDefault(); const r = this.selectable[this.active]; if (r) this.select(r); }
            },
        }"
        x-init="$watch('q', () => active = 0); $nextTick(() => window.lucide && window.lucide.createIcons()); $refs.input.focus()"
        x-effect="rows; $nextTick(() => window.lucide && window.lucide.createIcons())"
        @keydown.window="onKeydown"
        @mousedown="if ($event.target === $event.currentTarget) close()"
        class="fixed inset-0 z-command flex justify-center items-start pt-[12vh] px-4 bg-overlay backdrop-blur-[3px]"
        x-transition:enter="transition ease-out duration-fast" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    >
        <div
            role="dialog" aria-modal="true" aria-label="Command palette"
            class="w-full max-w-[560px] overflow-hidden bg-raised border shadow-xl rounded-2xl"
            x-transition:enter="transition ease-out duration-DEFAULT"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.985]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-subtle">
                <x-jaunt.icon name="search" size="sm" class="text-tertiary" />
                <input
                    x-ref="input" x-model="q"
                    placeholder="{{ $placeholder }}"
                    class="flex-1 border-0 bg-transparent outline-none text-primary text-lg placeholder:text-tertiary"
                />
                <span class="text-2xs font-mono text-tertiary border rounded-xs px-1.5 py-0.5">esc</span>
            </div>

            <div class="max-h-[52vh] overflow-y-auto p-1.5">
                <template x-if="selectable.length === 0">
                    <div class="py-7 px-4 text-center text-sm text-tertiary" x-text="`No results for “${q}”`"></div>
                </template>

                <template x-for="(row, i) in rows" :key="row.id || i">
                    <template x-if="row.group">
                        <div class="px-2 pt-2 pb-1 text-2xs font-semibold uppercase tracking-wide text-tertiary" x-text="row.group"></div>
                    </template>
                </template>
                <template x-for="row in rows.filter(r => !r.group)" :key="row.id">
                    <button
                        type="button"
                        @click="select(row)"
                        @mouseenter="active = selectable.indexOf(row)"
                        :data-active="selectable.indexOf(row) === active"
                        class="flex items-center gap-2.5 w-full px-2.5 py-2 rounded-sm text-sm text-left transition-colors
                            data-[active=true]:bg-active"
                        :class="row.ai ? 'text-ai-text' : 'text-primary'"
                    >
                        <span :class="row.ai ? 'text-ai' : 'text-secondary'">
                            <i :data-lucide="row.icon" style="width:17px;height:17px;stroke-width:1.5" aria-hidden="true"></i>
                        </span>
                        <span class="flex-1 min-w-0" x-text="row.label"></span>
                        <span x-show="row.hint" class="text-xs text-tertiary" x-text="row.hint"></span>
                        <span x-show="row.kbd" class="text-2xs font-mono text-tertiary border border-subtle rounded-xs px-1.5 py-0.5" x-text="row.kbd"></span>
                    </button>
                </template>
            </div>

            <div class="flex items-center gap-3.5 px-3.5 py-2 border-t border-subtle text-2xs text-tertiary">
                <span><b class="font-mono font-normal text-secondary">↑</b> <b class="font-mono font-normal text-secondary">↓</b> to navigate</span>
                <span><b class="font-mono font-normal text-secondary">↵</b> to select</span>
                <span class="ml-auto">Jaunt</span>
            </div>
        </div>
    </div>
</template>
