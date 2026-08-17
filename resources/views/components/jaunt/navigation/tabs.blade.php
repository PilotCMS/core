{{--
    jaunt.navigation.tabs — tab bar for switching views within a workspace.
    Ported from Tabs.jsx. `underline` for primary view switching, `pills` for
    compact filters.

    items: [{ id, label, icon?, count? }]  (icon is a Lucide name string)

    Controlled via `value` (+ listen for the `change` event dispatched on
    select), or uncontrolled via `defaultValue`. Since Blade components are
    server-rendered once, "controlled" here means: pass `:value` and handle
    the `@tab-change` event yourself (e.g. wire:model / Livewire) — Alpine
    still owns the moment-to-moment active-tab UI state per the Alpine
    ownership rule in docs/09-engineering-alignment.md.

    Usage:
      <x-jaunt.navigation.tabs :items="[
          ['id' => 'all', 'label' => 'All', 'count' => 248],
          ['id' => 'published', 'label' => 'Published', 'count' => 210],
          ['id' => 'draft', 'label' => 'Draft', 'count' => 38],
      ]" default-value="all" @tab-change="view = $event.detail.id" />

    Keyboard: ArrowLeft/ArrowRight move focus + selection between tabs
    (wraps at the ends), Home/End jump to first/last, Enter/Space activates
    the focused tab. A11y: role="tablist"/"tab", aria-selected, roving
    tabindex (only the active tab is in the tab order).
--}}
@props([
    'items' => [],
    'value' => null,
    'defaultValue' => null,
    'variant' => 'underline', // underline | pills
])

@php
$initial = $value ?? $defaultValue ?? ($items[0]['id'] ?? null);
$pills = $variant === 'pills';
@endphp

<div
    x-data="{
        active: {{ Str::of(json_encode($initial))->toHtmlString() }},
        items: {{ Str::of(json_encode(array_values($items)))->toHtmlString() }},
        select(id) {
            this.active = id;
            this.$dispatch('tab-change', { id });
            $nextTick(() => this.$refs['tab-' + id] && this.$refs['tab-' + id].focus());
        },
        onKeydown(e, id) {
            const ids = this.items.map(t => t.id);
            const i = ids.indexOf(id);
            if (e.key === 'ArrowRight') { e.preventDefault(); this.select(ids[(i + 1) % ids.length]); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); this.select(ids[(i - 1 + ids.length) % ids.length]); }
            else if (e.key === 'Home') { e.preventDefault(); this.select(ids[0]); }
            else if (e.key === 'End') { e.preventDefault(); this.select(ids[ids.length - 1]); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.select(id); }
        },
    }"
    role="tablist"
    {{ $attributes->merge(['class' => $pills ? 'flex items-center gap-1' : 'flex items-center gap-0.5 border-b border-subtle']) }}
>
    @foreach ($items as $tab)
        <button
            type="button"
            role="tab"
            x-ref="tab-{{ $tab['id'] }}"
            :aria-selected="active === '{{ $tab['id'] }}'"
            :tabindex="active === '{{ $tab['id'] }}' ? 0 : -1"
            @click="select('{{ $tab['id'] }}')"
            @keydown="onKeydown($event, '{{ $tab['id'] }}')"
            class="relative inline-flex items-center gap-1.5 h-[34px] {{ $pills ? 'px-2.5 rounded-sm' : 'px-2.5' }} text-sm font-medium
                text-secondary hover:text-primary
                transition-colors duration-instant ease-standard
                focus-visible:outline-none focus-visible:shadow-ring focus-visible:rounded-sm
                aria-selected:text-primary
                {{ $pills ? 'aria-selected:bg-card aria-selected:shadow-[inset_0_0_0_1.5px_var(--border-selected)]' : '' }}"
        >
            @if (!empty($tab['icon']))
                <x-jaunt.icon :name="$tab['icon']" size="sm" />
            @endif
            <span>{{ $tab['label'] }}</span>
            @if (isset($tab['count']))
                <span
                    class="font-mono text-2xs font-semibold px-1.5 py-px rounded-full bg-sunken text-tertiary u-tabular"
                    :class="active === '{{ $tab['id'] }}' && 'bg-selected text-primary'"
                >{{ $tab['count'] }}</span>
            @endif
            @if (!$pills)
                <span
                    x-show="active === '{{ $tab['id'] }}'"
                    x-cloak
                    class="absolute left-1.5 right-1.5 -bottom-px h-0.5 rounded-full bg-accent"
                    aria-hidden="true"
                ></span>
            @endif
        </button>
    @endforeach
</div>
