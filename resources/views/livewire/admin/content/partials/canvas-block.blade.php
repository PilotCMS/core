@php
    $isSelected = $selectedBlockId === $block['id'];
    $blockType = $blockTypes[$block['type']] ?? null;
    $canContainBlocks = (bool) ($blockType?->schema['can_contain_blocks'] ?? false);
    $children = collect($block['children'] ?? [])->values();
    $columnCount = (int) ($block['data']['columns'] ?? 2);
    $columnCount = max(1, min(4, $columnCount));
    $columnClasses = [
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
    ][$columnCount];
    $hasColumnSlots = in_array($block['type'], ['columns', 'grid'], true);
    $isNested = $depth > 0;
    $flattenSummaryValues = function ($value, string $path = '') use (&$flattenSummaryValues): array {
        if (! is_array($value)) {
            return is_scalar($value) && trim((string) $value) !== ''
                ? [['key' => $path, 'value' => (string) $value]]
                : [];
        }

        $values = [];

        foreach ($value as $key => $nestedValue) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            $nestedPath = $path === '' ? (string) $key : $path.'.'.$key;
            $values = array_merge($values, $flattenSummaryValues($nestedValue, $nestedPath));
        }

        return $values;
    };
    $summaryValues = collect($flattenSummaryValues($block['data'] ?? []));
    $summaryValueFor = function (array $keys) use ($summaryValues): ?string {
        $match = $summaryValues->first(function (array $item) use ($keys): bool {
            $path = collect(explode('.', strtolower($item['key'])));

            return $path->intersect($keys)->isNotEmpty();
        });

        return $match['value'] ?? null;
    };
    $summaryTitle = $summaryValueFor(['title', 'headline', 'heading', 'name', 'label']);
    $summaryText = $summaryValueFor(['subtitle', 'description', 'summary', 'text', 'body', 'copy', 'content']);
    $summaryImage = $summaryValues->first(function (array $item): bool {
        $key = strtolower($item['key']);
        $value = strtolower($item['value']);

        return preg_match('/(image|photo|asset).*(url|src|path)?/', $key) === 1
            && (str_starts_with($value, 'http') || str_starts_with($value, '/'));
    })['value'] ?? null;
    $summaryTitle = $summaryTitle ? str(strip_tags($summaryTitle))->squish()->limit(80) : null;
    $summaryText = $summaryText ? str(strip_tags($summaryText))->squish()->limit(150) : null;
    $isHidden = ! empty($block['data']['_hidden']) || (($block['data']['visible'] ?? true) === false);
    $blockIcon = match ($blockType?->icon) {
        'rectangle-stack' => 'panels-top-left',
        'document-text' => 'align-left',
        'photo' => 'image',
        'squares-2x2' => 'grid-2x2',
        'arrow-right' => 'mouse-pointer-click',
        'columns' => 'columns-3',
        'squares-plus' => 'layout-grid',
        'map' => 'map',
        'calendar' => 'calendar-days',
        default => 'box',
    };
    $childrenForColumn = function (int $columnIndex) use ($children, $columnCount) {
        return $children->filter(function ($child, $index) use ($columnIndex, $columnCount) {
            $childColumn = array_key_exists('_column', $child['data'] ?? [])
                ? (int) $child['data']['_column']
                : $index % $columnCount;

            return $childColumn === $columnIndex;
        });
    };
@endphp

<div
    wire:key="block-{{ $block['id'] }}"
    data-editor-block="{{ $block['id'] }}"
    class="group/block relative {{ $isNested ? 'mb-3 rounded-lg border bg-white p-2 shadow-sm transition-colors' : 'mb-8 rounded-lg transition-[background-color,border-color,box-shadow,transform] duration-fast' }} {{ $isSelected ? ($isNested ? 'border-blue-300 ring-2 ring-blue-100' : 'editor-highlight') : ($isNested ? 'border-slate-200 hover:border-blue-200' : 'hover-highlight') }}"
    @if($isSelected) data-label="{{ $blockType->name ?? $block['type'] }}" @endif
>
    <div class="absolute right-2 top-2 z-20 flex overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm transition-opacity {{ $isSelected ? 'opacity-100' : 'opacity-0 group-hover/block:opacity-100' }}">
        <button type="button" wire:click.stop="moveBlockUp({{ $block['id'] }})" class="cms-iconbtn !rounded-none border-r border-subtle" title="Move block up" aria-label="Move block up">
            <x-jaunt.icon name="arrow-up" size="xs" />
        </button>
        <button type="button" wire:click.stop="moveBlockDown({{ $block['id'] }})" class="cms-iconbtn !rounded-none border-r border-subtle" title="Move block down" aria-label="Move block down">
            <x-jaunt.icon name="arrow-down" size="xs" />
        </button>
        <button type="button" wire:click.stop="addBlockAbove({{ $block['id'] }})" class="cms-iconbtn !rounded-none border-r border-subtle" title="Insert above" aria-label="Insert above">
            <x-jaunt.icon name="arrow-up-to-line" size="xs" />
        </button>
        <button type="button" wire:click.stop="addBlockBelow({{ $block['id'] }})" class="cms-iconbtn !rounded-none border-r border-subtle" title="Insert below" aria-label="Insert below">
            <x-jaunt.icon name="arrow-down-to-line" size="xs" />
        </button>
        <button type="button" wire:click.stop="duplicateBlock({{ $block['id'] }})" class="cms-iconbtn !rounded-none border-r border-subtle" title="Duplicate block" aria-label="Duplicate block">
            <x-jaunt.icon name="copy" size="xs" />
        </button>
        <button type="button" wire:click.stop="deleteBlock({{ $block['id'] }})" wire:confirm="Delete this block?" class="cms-iconbtn cms-iconbtn-danger !rounded-none" title="Delete block" aria-label="Delete block">
            <x-jaunt.icon name="trash-2" size="xs" />
        </button>
    </div>

    <div
        wire:click="$set('selectedBlockId', {{ $block['id'] }})"
        class="relative z-10 cursor-pointer rounded-md transition-colors {{ $isNested ? 'px-2 py-2' : '-mx-1 px-1 py-2' }} {{ $isSelected ? 'hover:bg-blue-50/30' : 'hover:bg-slate-50/80' }}"
    >
        <div class="flex min-h-24 items-center gap-4 rounded-md border border-subtle bg-card p-4 pr-24 shadow-xs">
            @if($summaryImage)
                <img src="{{ $summaryImage }}" alt="" class="h-20 w-28 shrink-0 rounded-md bg-sunken object-cover" loading="lazy" />
            @else
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-md bg-accent-subtle text-accent-text">
                    <x-jaunt.icon :name="$blockIcon" size="md" />
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-2xs font-semibold uppercase tracking-[var(--ls-caps)] text-tertiary">{{ $blockType->name ?? str($block['type'])->headline() }}</span>
                    @if($isHidden)
                        <span class="cms-badge">Hidden</span>
                    @endif
                </div>
                @if($summaryTitle)
                    <div class="mt-1 truncate text-sm font-semibold text-primary">{{ $summaryTitle }}</div>
                @endif
                <p class="mt-1 line-clamp-2 text-sm leading-5 text-secondary">
                    {{ $summaryText ?: ($summaryTitle ? 'Select this block to edit its fields.' : 'Add content from the inspector.') }}
                </p>
                @if($children->isNotEmpty())
                    <div class="mt-2 text-2xs text-tertiary">{{ $children->count() }} nested {{ Str::plural('block', $children->count()) }}</div>
                @endif
            </div>
        </div>
    </div>

    @if($canContainBlocks && $hasColumnSlots)
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nested content</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-400 ring-1 ring-slate-200">{{ $columnCount }} columns</span>
            </div>

            <div class="grid gap-3 p-3 {{ $columnClasses }}">
                @foreach(range(0, $columnCount - 1) as $columnIndex)
                    @php
                        $columnChildren = $childrenForColumn($columnIndex);
                    @endphp

                    <div class="min-h-32 rounded-lg border border-dashed border-slate-200 bg-slate-50/70 p-2 transition-colors hover:border-blue-300 hover:bg-blue-50/30">
                        <div class="mb-2 flex items-center justify-between px-1">
                            <span class="text-xs font-medium text-slate-500">Column {{ $columnIndex + 1 }}</span>
                            <button type="button" wire:click="addNestedBlock({{ $block['id'] }}, {{ $columnIndex }})" class="cms-iconbtn" title="Add block to column {{ $columnIndex + 1 }}" aria-label="Add block to column {{ $columnIndex + 1 }}">
                                <x-jaunt.icon name="plus" size="sm" />
                            </button>
                        </div>

                        @if($columnChildren->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($columnChildren as $child)
                                    @include('livewire.admin.content.partials.canvas-block', [
                                        'block' => $child,
                                        'blockTypes' => $blockTypes,
                                        'selectedBlockId' => $selectedBlockId,
                                        'depth' => $depth + 1,
                                    ])
                                @endforeach
                            </div>
                        @else
                            <button type="button" wire:click="addNestedBlock({{ $block['id'] }}, {{ $columnIndex }})" class="flex min-h-24 w-full items-center justify-center gap-2 rounded-sm border border-dashed border-default bg-card/70 px-3 py-4 text-sm font-medium text-secondary transition-colors hover:border-strong hover:bg-hover hover:text-primary">
                                <x-jaunt.icon name="circle-plus" size="sm" />
                                Add block
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($canContainBlocks)
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Child blocks</span>
                <button type="button" wire:click="addNestedBlock({{ $block['id'] }})" class="cms-text-btn">
                    <x-jaunt.icon name="plus" size="sm" />
                    Add
                </button>
            </div>

            @if($children->isNotEmpty())
                <div class="space-y-3 p-3">
                    @foreach($children as $child)
                        @include('livewire.admin.content.partials.canvas-block', [
                            'block' => $child,
                            'blockTypes' => $blockTypes,
                            'selectedBlockId' => $selectedBlockId,
                            'depth' => $depth + 1,
                        ])
                    @endforeach
                </div>
            @else
                <div class="p-3">
                    <button type="button" wire:click="addNestedBlock({{ $block['id'] }})" class="flex min-h-24 w-full items-center justify-center gap-2 rounded-sm border border-dashed border-default bg-sunken px-3 py-4 text-sm font-medium text-secondary transition-colors hover:border-strong hover:bg-hover hover:text-primary">
                        <x-jaunt.icon name="circle-plus" size="sm" />
                        Add child block
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
