@php
    $collectionField = $schema['fields'][$selectedFieldIndex];
    $sourceContentType = $contentTypes->firstWhere('key', $collectionField['source_content_type'] ?? null);
    $sourceFields = collect([
        '$id' => 'Content ID',
        '$url' => 'Content URL',
        'name' => 'Name',
        'slug' => 'Slug',
        'categories.0' => 'First category',
        'tags.0' => 'First tag',
        'published_at' => 'Published date',
        'created_at' => 'Created date',
        'updated_at' => 'Updated date',
    ])->merge(collect($sourceContentType?->schema['fields'] ?? [])->mapWithKeys(
        fn (array $field): array => ['meta.'.($field['key'] ?? '') => $field['label'] ?? $field['key'] ?? 'Field']
    ));
@endphp

<div class="space-y-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4">
    <div>
        <h3 class="text-sm font-semibold text-slate-800">Content source and mapping</h3>
        <p class="mt-1 text-xs leading-5 text-slate-500">Choose the content type this component will query, then map its fields into the item shape expected by the component.</p>
    </div>

    <flux:field>
        <flux:label>Source content type</flux:label>
        <flux:select wire:model.live="schema.fields.{{ $selectedFieldIndex }}.source_content_type">
            <option value="">Choose a content type...</option>
            @foreach($contentTypes as $contentType)
                <option value="{{ $contentType->key }}">{{ $contentType->name }}</option>
            @endforeach
        </flux:select>
    </flux:field>

    <div class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Field mappings</div>
                <div class="mt-0.5 text-[11px] text-slate-500">Component item field ← source content field</div>
            </div>
            <flux:button type="button" wire:click="addMapping({{ $selectedFieldIndex }})" variant="ghost" size="xs">
                <flux:icon.plus class="size-4" />
                Add mapping
            </flux:button>
        </div>

        @foreach($collectionField['mappings'] ?? [] as $mappingIndex => $mapping)
            <div class="grid grid-cols-[minmax(0,1fr)_18px_minmax(0,1fr)_auto] items-center gap-2">
                <flux:input
                    wire:model="schema.fields.{{ $selectedFieldIndex }}.mappings.{{ $mappingIndex }}.target"
                    placeholder="title"
                    aria-label="Component item field"
                />
                <span class="text-center text-slate-400" aria-hidden="true">←</span>
                <flux:select wire:model="schema.fields.{{ $selectedFieldIndex }}.mappings.{{ $mappingIndex }}.source" aria-label="Source content field">
                    <option value="">Choose field...</option>
                    @foreach($sourceFields as $sourceKey => $sourceLabel)
                        @if($sourceKey !== 'meta.')
                            <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                        @endif
                    @endforeach
                </flux:select>
                <flux:button type="button" wire:click="removeMapping({{ $selectedFieldIndex }}, {{ $mappingIndex }})" variant="ghost" size="xs" class="text-red-600" aria-label="Remove mapping">
                    <flux:icon.trash class="size-4" />
                </flux:button>
            </div>
        @endforeach
    </div>
</div>
