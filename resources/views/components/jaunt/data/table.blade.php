{{--
    jaunt.data.table — the workhorse data table (sticky header, hover rows,
    optional selection column, sortable columns, hover-revealed row actions,
    right-aligned tabular numbers). Ported from Table.jsx.

    The source's `column.render: (value, row) => ReactNode` is a JS function
    and has no direct Blade equivalent, so it's translated to a declarative
    `type` on each column instead — this keeps Table a dumb Tier-1 primitive
    (no closures passed from PHP) while covering the cell shapes actually
    used by the mock CRM/listings data in ui_kits-source/app/data.js:

    columns: [
        { key, header, align?, sortable?, width?,
          type?: 'text' | 'badge' | 'avatar' | 'currency' | 'number',
          // type: 'badge'   reads row[`${key}Variant`] (fallback 'neutral') for the pill color
          // type: 'avatar'  reads row[`${key}Sub`] as the secondary line under the name
        },
    ]
    data: array of row objects, each needs a unique `id`.

    Row actions: pass a `rowAction` slot, rendered inside the trailing
    hover-revealed action cell. Body rows are rendered client-side by an
    Alpine `x-for` (needed so sort actually reorders DOM, not just an
    invisible in-memory array), which means `rowAction`'s markup is static
    Blade output cloned identically into every row — it does NOT receive the
    row as a parameter (the source's `rowAction={(row) => ...}` closure has
    no Blade equivalent). In practice this is fine for a uniform "More" menu
    trigger; if the action truly varies per row (e.g. a differently-labeled
    button per row), compose a Tier-2 Livewire wrapper instead.

    There is likewise no per-column custom-cell escape hatch equivalent to
    the source's `render` closure — Blade slots can't be looked up
    dynamically by column key from inside a loop, so anything beyond the
    built-in `type`s (text/badge/avatar/currency/number) should be composed
    as a Tier-2 Livewire wrapper that pre-formats the value into `data`
    before passing it to this primitive.

    <x-jaunt.data.table
        :columns="[
            ['key' => 'name', 'header' => 'Name', 'sortable' => true, 'type' => 'avatar'],
            ['key' => 'status', 'header' => 'Status', 'type' => 'badge'],
            ['key' => 'revenue', 'header' => 'Revenue', 'align' => 'right', 'sortable' => true, 'type' => 'currency'],
        ]"
        :data="$partners"
        selectable
        :sort-key="'name'" sort-dir="asc"
    >
        <x-slot:rowAction>
            <x-jaunt.forms.icon-button label="More"><x-jaunt.icon name="more-horizontal" size="sm" /></x-jaunt.forms.icon-button>
        </x-slot:rowAction>
    </x-jaunt.data.table>

    INTERACTIVE STATE (all client-side Alpine, no server round-trip):
      - Sorting: clicking a sortable header toggles asc/desc and re-sorts the
        in-memory `data` array (string/number aware). A real app composing
        this inside a Livewire table would instead listen for the dispatched
        `sort` CustomEvent and re-query — both paths are supported: if an
        `onSort` behavior is needed server-side, listen for
        `@sort.window="..."` on the wrapping element; the built-in client
        sort still runs for instant visual feedback.
      - Selection: `selectable` adds a checkbox column; `selected` seeds the
        initially-checked row ids (array of ids). Selection state lives in
        Alpine (`selected` array) and a `selection-change` CustomEvent fires
        on every change with `{ selected: [...] }` for a parent to hook into.
      - Bulk-action bar: per Table.prompt.md ("when selected.length > 0, show
        a bulk-action bar above the table") — a `bulkActions` slot renders
        inside that bar; visibility is driven by `x-show="selected.length > 0"`.
--}}
@props([
    'columns' => [],
    'data' => [],
    'selectable' => false,
    'selected' => [],
    'sortKey' => null,
    'sortDir' => 'asc',
])

@php
$rowActionSlot = $rowAction ?? null;
$hasRowAction = isset($rowAction);
$hasBulkActions = isset($bulkActions);

$badgeVariantClasses = [
    'neutral' => 'bg-sunken text-secondary',
    'accent'  => 'bg-accent-subtle text-accent-text',
    'success' => 'bg-success-subtle text-success',
    'warning' => 'bg-warning-subtle text-warning',
    'danger'  => 'bg-danger-subtle text-danger',
    'info'    => 'bg-info-subtle text-info',
    'ai'      => 'bg-ai-subtle text-ai-text border-ai-border',
];
@endphp

<div
    x-data="{
        rows: {{ Str::of(json_encode(array_values($data)))->toHtmlString() }},
        columns: {{ Str::of(json_encode(array_values($columns)))->toHtmlString() }},
        selected: {{ Str::of(json_encode(array_values($selected)))->toHtmlString() }},
        sortKey: {{ $sortKey ? "'" . $sortKey . "'" : 'null' }},
        sortDir: '{{ $sortDir }}',
        get allChecked() { return this.rows.length > 0 && this.selected.length === this.rows.length; },
        get someChecked() { return this.selected.length > 0 && !this.allChecked; },
        toggleAll() {
            this.selected = this.allChecked ? [] : this.rows.map(r => r.id);
            this.$dispatch('selection-change', { selected: this.selected });
        },
        toggleRow(id) {
            this.selected = this.selected.includes(id) ? this.selected.filter(x => x !== id) : [...this.selected, id];
            this.$dispatch('selection-change', { selected: this.selected });
        },
        sortBy(col) {
            if (!col.sortable) return;
            this.sortDir = (this.sortKey === col.key && this.sortDir === 'asc') ? 'desc' : 'asc';
            this.sortKey = col.key;
            this.rows.sort((a, b) => {
                const av = a[col.key], bv = b[col.key];
                const cmp = (typeof av === 'number' && typeof bv === 'number') ? av - bv : String(av ?? '').localeCompare(String(bv ?? ''));
                return this.sortDir === 'asc' ? cmp : -cmp;
            });
            this.$dispatch('sort', { key: col.key, dir: this.sortDir });
        },
    }"
    {{ $attributes->merge(['class' => 'bg-card outline outline-1 outline-[color:var(--border-subtle)] -outline-offset-1 rounded-xl shadow-sm overflow-hidden']) }}
>
    @if ($hasBulkActions)
        <div x-show="selected.length > 0" x-cloak class="flex items-center gap-2 px-3.5 h-row border-b border-subtle bg-selected">
            <span class="text-xs text-secondary" x-text="selected.length + ' selected'"></span>
            <div class="flex items-center gap-1.5 ml-auto">
                {{ $bulkActions }}
            </div>
        </div>
    @endif

    <table class="w-full border-collapse text-sm">
        <thead>
            <tr>
                @if ($selectable)
                    <th class="w-10 pl-3.5 h-row text-left border-b border-subtle">
                        <input
                            type="checkbox"
                            aria-label="Select all"
                            class="w-[15px] h-[15px] accent-[var(--accent)] cursor-pointer"
                            :checked="allChecked"
                            x-effect="$el.indeterminate = someChecked"
                            @change="toggleAll()"
                        />
                    </th>
                @endif

                @foreach ($columns as $col)
                    @php
                        $align = $col['align'] ?? 'left';
                        $sortable = $col['sortable'] ?? false;
                        $width = $col['width'] ?? null;
                    @endphp
                    <th
                        @if ($width) style="width: {{ is_numeric($width) ? $width . 'px' : $width }}" @endif
                        class="h-row px-3.5 border-b border-subtle text-2xs font-semibold uppercase tracking-wide text-tertiary whitespace-nowrap
                            {{ $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left') }}
                            {{ $sortable ? 'cursor-pointer select-none hover:text-secondary' : '' }}"
                        @if ($sortable) @click="sortBy({{ Str::of(json_encode($col))->toHtmlString() }})" @endif
                        :aria-sort="sortKey === '{{ $col['key'] }}' ? (sortDir === 'asc' ? 'ascending' : 'descending') : undefined"
                    >
                        <span class="inline-flex items-center gap-1 {{ $align === 'right' ? 'justify-end w-full' : '' }}">
                            {{ $col['header'] ?? $col['key'] }}
                            @if ($sortable)
                                <span
                                    class="transition-opacity duration-fast"
                                    :class="sortKey === '{{ $col['key'] }}' ? 'opacity-100 text-primary' : 'opacity-0'"
                                >
                                    <template x-if="!(sortKey === '{{ $col['key'] }}' && sortDir === 'desc')">
                                        <x-jaunt.icon name="chevron-up" size="xs" />
                                    </template>
                                    <template x-if="sortKey === '{{ $col['key'] }}' && sortDir === 'desc'">
                                        <x-jaunt.icon name="chevron-down" size="xs" />
                                    </template>
                                </span>
                            @endif
                        </span>
                    </th>
                @endforeach

                @if ($hasRowAction)
                    <th class="w-11 h-row border-b border-subtle"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            <template x-if="rows.length === 0">
                <tr>
                    <td colspan="{{ count($columns) + ($selectable ? 1 : 0) + ($hasRowAction ? 1 : 0) }}" class="h-24 text-center text-sm text-tertiary">
                        No data.
                    </td>
                </tr>
            </template>
            <template x-for="row in rows" :key="row.id">
                <tr
                    class="group transition-colors duration-instant ease-standard hover:bg-hover last:[&>td]:border-b-0"
                    :class="selected.includes(row.id) ? 'bg-selected' : ''"
                >
                    @if ($selectable)
                        <td class="w-10 pl-3.5 h-row border-b border-subtle align-middle">
                            <input
                                type="checkbox"
                                aria-label="Select row"
                                class="w-[15px] h-[15px] accent-[var(--accent)] cursor-pointer"
                                :checked="selected.includes(row.id)"
                                @change="toggleRow(row.id)"
                            />
                        </td>
                    @endif

                    @foreach ($columns as $col)
                        @php
                            $key = $col['key'];
                            $type = $col['type'] ?? 'text';
                            $align = $col['align'] ?? 'left';
                        @endphp
                        <td
                            class="px-3.5 h-row border-b border-subtle align-middle text-primary
                                {{ $align === 'right' ? 'text-right tabular-nums' : ($align === 'center' ? 'text-center' : '') }}"
                        >
                            @if ($type === 'badge')
                                <span
                                    class="inline-flex items-center gap-1 h-5 px-[7px] rounded-full text-xs font-medium border border-transparent whitespace-nowrap"
                                    :class="{
                                        'bg-sunken text-secondary': !row['{{ $key }}Variant'] || row['{{ $key }}Variant'] === 'neutral',
                                        'bg-accent-subtle text-accent-text': row['{{ $key }}Variant'] === 'accent',
                                        'bg-success-subtle text-success': row['{{ $key }}Variant'] === 'success',
                                        'bg-warning-subtle text-warning': row['{{ $key }}Variant'] === 'warning',
                                        'bg-danger-subtle text-danger': row['{{ $key }}Variant'] === 'danger',
                                        'bg-info-subtle text-info': row['{{ $key }}Variant'] === 'info',
                                        'bg-ai-subtle text-ai-text border-ai-border': row['{{ $key }}Variant'] === 'ai',
                                    }"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span>
                                    <span x-text="row['{{ $key }}']"></span>
                                </span>
                            @elseif ($type === 'avatar')
                                {{-- Inline Alpine-driven avatar: <x-jaunt.data.avatar> computes its
                                     deterministic color/initials server-side in @php from a static
                                     $name prop, which can't react to the per-row `row[key]` value
                                     inside this x-for template — so the same hash-based color trick
                                     is reproduced here in JS. Keep in sync with avatar.blade.php. --}}
                                <div
                                    class="flex items-center gap-2.5 min-w-0"
                                    x-data="{
                                        get initials() { const n = (row['{{ $key }}'] || '').trim(); if (!n) return '?'; const p = n.split(/\s+/); return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase(); },
                                        get vizVar() { const n = row['{{ $key }}'] || ''; let h = 0; for (const c of n) { h = c.charCodeAt(0) + ((h << 5) - h); } const palette = [1,2,3,4,6,7]; return '--viz-' + palette[Math.abs(h) % palette.length]; },
                                    }"
                                >
                                    <span
                                        class="relative inline-flex items-center justify-center flex-none rounded-full overflow-hidden select-none font-semibold text-xs"
                                        style="width:24px;height:24px"
                                        :style="`background: color-mix(in oklab, var(${vizVar}) 18%, transparent); color: var(${vizVar});`"
                                        x-text="initials"
                                    ></span>
                                    <div class="min-w-0">
                                        <div class="text-sm text-primary truncate" x-text="row['{{ $key }}']"></div>
                                        <div class="text-xs text-tertiary truncate" x-show="row['{{ $key }}Sub']" x-text="row['{{ $key }}Sub']"></div>
                                    </div>
                                </div>
                            @elseif ($type === 'currency')
                                <span x-text="typeof row['{{ $key }}'] === 'number' ? '$' + row['{{ $key }}'].toLocaleString() : (row['{{ $key }}'] || '—')"></span>
                            @elseif ($type === 'number')
                                <span x-text="typeof row['{{ $key }}'] === 'number' ? row['{{ $key }}'].toLocaleString() : row['{{ $key }}']"></span>
                            @else
                                <span x-text="row['{{ $key }}']"></span>
                            @endif
                        </td>
                    @endforeach

                    @if ($hasRowAction)
                        <td class="w-11 px-2 h-row border-b border-subtle text-right opacity-0 group-hover:opacity-100 transition-opacity duration-fast">
                            {{ $rowActionSlot }}
                        </td>
                    @endif
                </tr>
            </template>
        </tbody>
    </table>
</div>
