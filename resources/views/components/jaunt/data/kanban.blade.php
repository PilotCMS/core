{{--
    jaunt.data.kanban — column board for pipeline-style data (campaign stages,
    event planning, partner deals). Ported from Kanban.jsx / KanbanCard.

    columns: [
        { id, title, color?, cards: [
            { id, title, tags?: [{ variant, label }], people?: [{ name, avatarSrc? }], footer? }
        ]},
    ]

    `color` is any CSS color value (matches the source's `col.color` — a raw
    CSS color/var, e.g. "var(--success)" or "#0e5852").

    Each card's `tags` renders as <x-jaunt.feedback.badge> pills and `people`
    as a small avatar cluster (via <x-jaunt.data.avatar>), mirroring how the
    mock data in ui_kits-source/app/data.js shapes kanban cards
    (tag: {v, t}, people: ["Name", ...]). A raw `footer` string/html is
    supported as a fallback when neither tags nor people fit.

    INTERACTIVITY — what's real vs stubbed:
      - Real: HTML5 native drag-and-drop (`draggable`, dragstart/dragover/drop)
        reorders cards between/within columns in an in-memory Alpine store
        seeded from the `columns` prop. Card counts update live. Keyboard
        users can still tab to cards (tabindex) but there is no keyboard
        reorder path — see Kanban.prompt.md ("provide a menu to move between
        columns without dragging"); that menu is NOT implemented here, it's
        a judgment call left to the app layer (Tier 2) since it needs
        per-workspace move targets.
      - Stubbed: nothing persists server-side. `onAddCard` in the source
        becomes an `add-card` browser CustomEvent dispatched with the column
        id — a Livewire/Tier-2 wrapper listens for it (`@add-card.window=`)
        to open a real "new card" flow. No optimistic-move network call,
        no undo/toast (source doc explicitly defers real DnD + toast/undo
        wiring to "the app layer").
--}}
@props([
    'columns' => [],
])

@php
// Normalize into a JSON-safe shape for Alpine's x-data (people/tags are
// already plain arrays/strings coming from PHP, so this is a straight pass-through).
$seedColumns = collect($columns)->map(function ($col) {
    return [
        'id' => $col['id'],
        'title' => $col['title'],
        'color' => $col['color'] ?? 'var(--text-tertiary)',
        'cards' => collect($col['cards'] ?? [])->map(function ($card) {
            return [
                'id' => $card['id'],
                'title' => $card['title'],
                'tags' => $card['tags'] ?? [],
                'people' => $card['people'] ?? [],
                'footer' => $card['footer'] ?? null,
            ];
        })->values()->all(),
    ];
})->values()->all();
@endphp

<div
    x-data="{
        columns: {{ Str::of(json_encode($seedColumns))->toHtmlString() }},
        dragCardId: null,
        dragFromColId: null,
        findCard(colId, cardId) {
            const col = this.columns.find(c => c.id === colId);
            if (!col) return null;
            const idx = col.cards.findIndex(c => c.id === cardId);
            return idx === -1 ? null : { col, idx };
        },
        onDragStart(colId, card) {
            this.dragCardId = card.id;
            this.dragFromColId = colId;
        },
        onDragEnd() {
            this.dragCardId = null;
            this.dragFromColId = null;
        },
        onDropOnCard(targetColId, targetCard) {
            if (!this.dragCardId) return;
            const from = this.findCard(this.dragFromColId, this.dragCardId);
            if (!from) return;
            const [moved] = from.col.cards.splice(from.idx, 1);
            const targetCol = this.columns.find(c => c.id === targetColId);
            const targetIdx = targetCol.cards.findIndex(c => c.id === targetCard.id);
            targetCol.cards.splice(targetIdx === -1 ? targetCol.cards.length : targetIdx, 0, moved);
            this.onDragEnd();
        },
        onDropOnColumn(targetColId) {
            if (!this.dragCardId) return;
            const from = this.findCard(this.dragFromColId, this.dragCardId);
            if (!from) return;
            // Only append to end when dropped on empty column space (not on a card).
            const targetCol = this.columns.find(c => c.id === targetColId);
            if (targetCol.cards.some(c => c.id === this.dragCardId)) { this.onDragEnd(); return; }
            const [moved] = from.col.cards.splice(from.idx, 1);
            targetCol.cards.push(moved);
            this.onDragEnd();
        },
        addCard(colId) {
            $dispatch('add-card', { columnId: colId });
        },
    }"
    {{ $attributes->merge(['class' => 'flex items-start gap-3.5 overflow-x-auto pb-2']) }}
>
    <template x-for="col in columns" :key="col.id">
        <div class="flex-none w-[288px] flex flex-col gap-2.5">
            <div class="flex items-center gap-2 px-1">
                <span class="w-2 h-2 rounded-full flex-none" :style="{ background: col.color }"></span>
                <span class="text-sm font-medium text-primary" x-text="col.title"></span>
                <span class="text-xs text-tertiary tabular-nums" x-text="col.cards.length"></span>
                <button
                    type="button"
                    @click="addCard(col.id)"
                    class="ml-auto inline-flex p-[3px] rounded-xs text-tertiary hover:bg-hover hover:text-primary"
                    :aria-label="'Add to ' + col.title"
                >
                    <x-jaunt.icon name="plus" size="xs" />
                </button>
            </div>

            <div
                class="flex flex-col gap-2 min-h-[40px] p-2 rounded-md bg-sunken"
                @dragover.prevent
                @drop.prevent="onDropOnColumn(col.id)"
            >
                <template x-for="card in col.cards" :key="card.id">
                    <div
                        draggable="true"
                        tabindex="0"
                        @dragstart="onDragStart(col.id, card)"
                        @dragend="onDragEnd()"
                        @dragover.prevent.stop
                        @drop.prevent.stop="onDropOnCard(col.id, card)"
                        class="flex flex-col gap-2 bg-card border border-[color:var(--border-default)] rounded-md px-3 py-2.5 shadow-xs cursor-grab active:cursor-grabbing transition-[box-shadow,transform] duration-fast ease-standard hover:shadow-sm hover:border-strong"
                        :class="dragCardId === card.id ? 'opacity-50' : ''"
                    >
                        <div class="flex flex-wrap gap-1.5" x-show="card.tags && card.tags.length">
                            <template x-for="tag in card.tags" :key="tag.label">
                                <span
                                    class="inline-flex items-center h-5 px-[7px] rounded-full text-xs font-medium border border-transparent whitespace-nowrap"
                                    :class="{
                                        'bg-sunken text-secondary': tag.variant === 'neutral' || !tag.variant,
                                        'bg-accent-subtle text-accent-text': tag.variant === 'accent',
                                        'bg-success-subtle text-success': tag.variant === 'success',
                                        'bg-warning-subtle text-warning': tag.variant === 'warning',
                                        'bg-danger-subtle text-danger': tag.variant === 'danger',
                                        'bg-info-subtle text-info': tag.variant === 'info',
                                        'bg-ai-subtle text-ai-text border-ai-border': tag.variant === 'ai',
                                        'bg-transparent text-secondary border-[color:var(--border-default)]': tag.variant === 'outline',
                                    }"
                                    x-text="tag.label"
                                ></span>
                            </template>
                        </div>

                        <div class="text-sm font-medium text-primary" x-text="card.title"></div>

                        <div class="flex items-center gap-1.5" x-show="(card.people && card.people.length) || card.footer">
                            <template x-if="card.people && card.people.length">
                                <div class="flex items-center -space-x-1.5">
                                    <template x-for="(person, i) in card.people.slice(0, 4)" :key="i">
                                        <span
                                            class="relative inline-flex items-center justify-center flex-none rounded-full ring-2 ring-card select-none font-semibold text-[9px]"
                                            style="width:20px;height:20px"
                                            x-init="$el.style.background = `color-mix(in oklab, var(--viz-${(i % 6) + 1}) 18%, transparent)`; $el.style.color = `var(--viz-${(i % 6) + 1})`"
                                            x-text="(person.name || person).split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"
                                        ></span>
                                    </template>
                                </div>
                            </template>
                            <span class="text-xs text-tertiary" x-show="card.footer" x-text="card.footer"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
