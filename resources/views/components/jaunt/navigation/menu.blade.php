{{--
    jaunt.navigation.menu — dropdown / context menu. Ported from Menu.jsx.

    Source note: Menu.jsx exports a single `Menu` component used for BOTH the
    dropdown case (trigger is a button, click opens) and the context-menu case
    (trigger is arbitrary content, right-click opens) — the .prompt.md says so
    explicitly ("Dropdown and context menu. Same component for both"). There is
    no separate ContextMenu export in the source, so this stays one Blade file.
    To use it as a context menu, pass `trigger-event="contextmenu"` and wrap
    your right-clickable content in the trigger slot (see usage below).

    Items are a plain PHP array (icons are Lucide names, not JSX nodes):
      items: [{ type?: 'item'|'separator'|'label', label?, icon?, kbd?,
                 checked?, danger?, ai?, onSelect? }]

    `onSelect` has no direct PHP/Alpine equivalent — instead each item may
    carry an `onSelectEvent` (client event name dispatched via
    $dispatch, for Alpine/JS listeners) and/or `wireClick` (a raw
    wire:click expression string) so callers can wire either client-only or
    Livewire behavior without the component needing to know which.

    Usage (dropdown):
      <x-jaunt.navigation.menu align="right" :items="[
          ['icon' => 'pencil', 'label' => 'Rename', 'kbd' => 'E'],
          ['icon' => 'sparkles', 'label' => 'Rewrite with AI', 'ai' => true],
          ['type' => 'separator'],
          ['icon' => 'trash-2', 'label' => 'Delete', 'danger' => true],
      ]">
          <x-slot:trigger>
              <x-jaunt.forms.icon-button label="More"><x-jaunt.icon name="more-horizontal" /></x-jaunt.forms.icon-button>
          </x-slot:trigger>
      </x-jaunt.navigation.menu>

    Usage (context menu — right-click anywhere in the trigger slot):
      <x-jaunt.navigation.menu trigger-event="contextmenu" :items="$items">
          <x-slot:trigger><div class="p-6">Right-click me</div></x-slot:trigger>
      </x-jaunt.navigation.menu>

    Keyboard: ArrowDown/ArrowUp move active item, Enter/Space selects,
    Esc closes. Click-outside closes. A11y: role="menu"/"menuitem".
--}}
@props([
    'items' => [],
    'align' => 'left',       // left | right
    'triggerEvent' => 'click', // click | contextmenu
    'open' => false,          // initial open state (uncontrolled default)
])

@php
$selectableCount = count(array_filter($items, fn ($it) => ($it['type'] ?? 'item') === 'item'));
@endphp

<span
    class="relative inline-flex"
    x-data="{
        open: {{ $open ? 'true' : 'false' }},
        active: -1,
        items: {{ Str::of(json_encode(array_values($items)))->toHtmlString() }},
        get selectable() { return this.items.filter(it => (it.type ?? 'item') === 'item'); },
        openMenu() { this.open = true; this.active = -1; },
        closeMenu() { this.open = false; this.active = -1; },
        toggle() { this.open ? this.closeMenu() : this.openMenu(); },
        select(it) {
            if (it.onSelectEvent) this.$dispatch(it.onSelectEvent, it);
            this.closeMenu();
        },
        onKeydown(e) {
            if (!this.open) return;
            if (e.key === 'Escape') { e.preventDefault(); this.closeMenu(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); this.active = Math.min(this.active + 1, this.selectable.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = Math.max(this.active - 1, 0); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); const it = this.selectable[this.active]; if (it) this.select(it); }
        },
    }"
    @click.outside="closeMenu()"
    @keydown.window="onKeydown"
    {{ $attributes }}
>
    <span
        @if ($triggerEvent === 'contextmenu')
            @contextmenu.prevent="openMenu()"
        @else
            @click="toggle()"
        @endif
    >{{ $trigger }}</span>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-base"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-cloak
        role="menu"
        class="absolute top-full {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-1.5 z-dropdown min-w-[200px] p-1.5 bg-raised border rounded-md shadow-lg origin-top-{{ $align === 'right' ? 'right' : 'left' }}"
    >
        @foreach ($items as $i => $item)
            @php $type = $item['type'] ?? 'item'; @endphp

            @if ($type === 'separator')
                <div class="h-px my-1 mx-1 bg-[color:var(--border-subtle)]" role="separator"></div>
            @elseif ($type === 'label')
                <div class="px-2 pt-1.5 pb-1 text-2xs font-semibold uppercase tracking-wide text-tertiary">{{ $item['label'] ?? '' }}</div>
            @else
                <button
                    type="button"
                    role="menuitem"
                    @click="select(items[{{ $i }}])"
                    @mouseenter="active = selectable.indexOf(items[{{ $i }}])"
                    :data-active="selectable.indexOf(items[{{ $i }}]) === active"
                    @if (!empty($item['wireClick'])) wire:click="{{ $item['wireClick'] }}" @endif
                    class="flex items-center gap-2.5 w-full px-2 py-1.5 rounded-sm text-sm text-left cursor-pointer
                        transition-colors duration-instant ease-standard
                        data-[active=true]:bg-hover
                        {{ !empty($item['danger']) ? 'text-danger hover:bg-danger-subtle' : (!empty($item['ai']) ? 'text-ai-text' : 'text-primary') }}"
                >
                    @if (!empty($item['icon']))
                        <x-jaunt.icon
                            :name="$item['icon']"
                            size="sm"
                            class="{{ !empty($item['danger']) ? 'text-danger' : (!empty($item['ai']) ? 'text-ai' : 'text-secondary') }} shrink-0"
                        />
                    @endif
                    <span class="flex-1 min-w-0">{{ $item['label'] ?? '' }}</span>
                    @if (!empty($item['checked']))
                        <span class="text-accent" aria-hidden="true"><x-jaunt.icon name="check" size="sm" /></span>
                    @endif
                    @if (!empty($item['kbd']))
                        <span class="text-2xs font-mono text-tertiary">{{ $item['kbd'] }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</span>
