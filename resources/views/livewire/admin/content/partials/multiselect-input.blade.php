@php
    $multiselectValue = $rawFieldValue;

    if (($field['translatable'] ?? false) && is_array($multiselectValue) && ! array_is_list($multiselectValue)) {
        $multiselectValue = $multiselectValue['en'] ?? reset($multiselectValue) ?: [];
    }

    $multiselectValue = is_array($multiselectValue) ? array_values($multiselectValue) : [];
    $multiselectOptions = $this->optionsForField($field);
@endphp

<div
    wire:key="multiselect-{{ $block['id'] }}-{{ $field['key'] }}"
    x-data="{
        open: false,
        query: '',
        options: @js($multiselectOptions),
        selected: @js(array_map('strval', $multiselectValue)),
        lastSavedSelection: @js(array_map('strval', $multiselectValue)),
        get filteredOptions() {
            const query = this.query.trim().toLowerCase();
            return query === ''
                ? this.options
                : this.options.filter((option) => option.label.toLowerCase().includes(query));
        },
        labelFor(value) {
            return this.options.find((option) => option.value === value)?.label ?? value;
        },
        toggle(value) {
            this.selected = this.selected.includes(value)
                ? this.selected.filter((selectedValue) => selectedValue !== value)
                : [...this.selected, value];
        },
        flush() {
            if (JSON.stringify(this.selected) === JSON.stringify(this.lastSavedSelection)) {
                return;
            }

            this.lastSavedSelection = [...this.selected];
            $wire.updateField(@js($field['key']), this.selected);
        },
        handleFocusOut() {
            setTimeout(() => {
                if (! this.$root.contains(document.activeElement)) {
                    this.open = false;
                    this.flush();
                }
            });
        },
    }"
    class="relative"
    @click.outside="open = false; flush()"
    @focusout="handleFocusOut()"
    @keydown.escape.window="open = false"
>
    <div
        class="flex min-h-10 w-full flex-wrap items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm shadow-sm transition-[border-color,box-shadow] focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"
        @click="open = true; $refs.search.focus()"
    >
        <template x-for="value in selected" :key="value">
            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                <span x-text="labelFor(value)"></span>
                <button
                    type="button"
                    class="rounded text-slate-400 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    @click.stop="toggle(value)"
                    :aria-label="`Remove ${labelFor(value)}`"
                >
                    <x-jaunt.icon name="x" size="xs" />
                </button>
            </span>
        </template>

        <input
            x-ref="search"
            x-model="query"
            type="search"
            role="combobox"
            aria-label="Search options"
            :aria-expanded="open.toString()"
            autocomplete="off"
            placeholder="{{ $field['placeholder'] ?? 'Search options...' }}"
            class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:ring-0"
            @focus="open = true"
            @keydown.arrow-down.prevent="open = true; $refs.listbox?.querySelector('button:not([disabled])')?.focus()"
        />
        <x-jaunt.icon name="chevrons-up-down" size="sm" class="shrink-0 text-slate-400" />
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.100ms
        x-ref="listbox"
        role="listbox"
        aria-multiselectable="true"
        class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
    >
        <template x-for="option in filteredOptions" :key="option.value">
            <button
                type="button"
                role="option"
                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
                :aria-selected="selected.includes(option.value).toString()"
                @click="toggle(option.value)"
            >
                <span
                    class="flex size-4 shrink-0 items-center justify-center rounded border"
                    :class="selected.includes(option.value) ? 'border-blue-500 bg-blue-500 text-white' : 'border-slate-300 bg-white text-transparent'"
                >
                    <x-jaunt.icon name="check" size="xs" />
                </span>
                <span x-text="option.label"></span>
            </button>
        </template>
        <p x-show="filteredOptions.length === 0" class="px-2.5 py-3 text-center text-xs text-slate-400">No matching options</p>
    </div>
</div>
