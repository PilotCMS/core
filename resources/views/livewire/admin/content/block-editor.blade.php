<div class="space-y-7">
    @foreach($blockType->schema['fields'] ?? [] as $field)
        @php
            $rawFieldValue = $data[$field['key']] ?? '';
            $hasSchemaRepeaterFields = ($field['type'] ?? null) === 'repeater' && ! empty($field['fields']);
            $isObjectList = ! $hasSchemaRepeaterFields && is_array($rawFieldValue) && array_is_list($rawFieldValue) && ! empty($rawFieldValue) && collect($rawFieldValue)->every(fn ($item) => is_array($item));
            $objectKeys = $isObjectList
                ? collect($rawFieldValue)->flatMap(fn ($item) => array_keys($item))->unique()->values()
                : collect();
            $fieldValue = is_array($rawFieldValue) ? ($rawFieldValue['en'] ?? reset($rawFieldValue) ?: '') : $rawFieldValue;
            $typeLabel = $field['type'] ?? 'text';
        @endphp
        <div class="group">
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">{{ $field['label'] }}</label>
                <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">{{ $typeLabel }}</span>
            </div>

            @if($isObjectList)
                <div class="space-y-3">
                    @foreach($rawFieldValue as $idx => $item)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="mb-3 text-[10px] font-bold uppercase tracking-wide text-slate-400">Item {{ $idx + 1 }}</div>
                            <div class="space-y-3">
                                @foreach($objectKeys as $objectKey)
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $objectKey }}</label>
                                        <textarea rows="{{ $objectKey === 'body' ? 3 : 1 }}"
                                            wire:change="updateJsonObjectField(@js($field['key']), {{ $idx }}, @js($objectKey), $event.target.value)"
                                            class="w-full min-h-9 rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 shadow-sm outline-none transition-[border-color,box-shadow,background-color] duration-fast focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        >{{ $item[$objectKey] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($field['type'] === 'text')
                @if($this->isLinkField($field) && $contentChoices->isNotEmpty())
                    @include('livewire.admin.content.partials.link-input', [
                        'fieldKey' => $field['key'],
                        'placeholder' => $field['placeholder'] ?? '',
                        'value' => $fieldValue,
                    ])
                @else
                    <input type="text"
                        value="{{ $fieldValue }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast"
                    />
                @endif
            @elseif($field['type'] === 'textarea')
                <textarea rows="{{ $field['rows'] ?? 4 }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full min-h-[80px] p-3 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast"
                >{{ $fieldValue }}</textarea>
            @elseif($field['type'] === 'richtext')
                @include('livewire.admin.content.partials.richtext-editor', [
                    'field' => $field,
                    'value' => $fieldValue,
                    'fieldKey' => $field['key'],
                ])
            @elseif($field['type'] === 'number')
                <input type="number"
                    value="{{ $fieldValue !== '' ? $fieldValue : ($field['default'] ?? 0) }}"
                    min="{{ $field['min'] ?? '' }}"
                    max="{{ $field['max'] ?? '' }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm"
                />
            @elseif($field['type'] === 'boolean')
                @php $rawVal = $data[$field['key']] ?? false; $boolChecked = is_array($rawVal) ? !empty($rawVal) : (bool) $rawVal; @endphp
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox"
                        wire:change="updateField('{{ $field['key'] }}', $event.target.checked)"
                        {{ $boolChecked ? 'checked' : '' }}
                        class="rounded border-slate-200 text-blue-500 focus:ring-blue-500"
                    />
                    <span class="text-sm text-slate-600">Enabled</span>
                </label>
            @elseif($field['type'] === 'image')
                @php
                    $focalX = $data[$field['key'].'_focal_x'] ?? 50;
                    $focalY = $data[$field['key'].'_focal_y'] ?? 50;
                @endphp
                <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-blue-400 cursor-pointer transition-[border-color,background-color] duration-fast group/upload"
                     wire:click="$dispatch('open-asset-picker', { fieldKey: '{{ $field['key'] }}' })">
                    @if($fieldValue)
                        <div class="mb-2 rounded-lg overflow-hidden max-h-24 bg-slate-100">
                            <img src="{{ $fieldValue }}" alt="" class="max-h-24 object-cover" style="object-position: {{ $focalX }}% {{ $focalY }}%;" />
                        </div>
                        <span class="text-xs font-medium text-slate-600 truncate max-w-full">{{ $fieldValue }}</span>
                    @else
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2 group-hover/upload:bg-white group-hover/upload:text-blue-500 shadow-sm transition-colors">
                            <x-jaunt.icon name="upload" size="md" />
                        </div>
                        <span class="text-xs font-medium text-slate-600">Drop image here</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">or click to browse</span>
                    @endif
                </div>
            @elseif($field['type'] === 'repeater')
                @php $repeaterItems = $data[$field['key']] ?? []; $repeaterItems = is_array($repeaterItems) ? $repeaterItems : []; @endphp
                <div class="space-y-2">
                    @foreach($repeaterItems as $idx => $item)
                        @php
                            $firstSub = $field['fields'][0] ?? null;
                            $subVal = $firstSub ? ($item[$firstSub['key']] ?? '') : '';
                            $subVal = is_array($subVal) ? ($subVal['en'] ?? reset($subVal) ?: '') : $subVal;
                            $itemLabel = $item['label'] ?? $item['name'] ?? null;
                            $itemLabel = is_array($itemLabel) ? ($itemLabel['en'] ?? reset($itemLabel) ?: '') : $itemLabel;
                            $displayTitle = $itemLabel ?: ($field['label'] . ' ' . ($idx + 1));
                            $isExpanded = $this->isRepeaterItemExpanded($field['key'], $idx);
                        @endphp
                        <div class="bg-white border border-slate-200 rounded-lg shadow-sm hover:border-blue-300 transition-colors group/item relative overflow-hidden">
                            @if($idx === 0)<div class="absolute left-0 top-0 bottom-0 w-1 bg-accent"></div>@endif
                            <div class="flex items-center gap-3 p-3">
                                <x-jaunt.icon name="grip-vertical" size="sm" class="text-slate-300 cursor-move shrink-0" />
                                <button
                                    type="button"
                                    wire:click="toggleRepeaterItem(@js($field['key']), {{ $idx }})"
                                    class="flex flex-1 min-w-0 items-center gap-3 text-left"
                                    aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                                    aria-controls="repeater-{{ $field['key'] }}-{{ $idx }}"
                                >
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-xs font-bold text-slate-700">{{ $displayTitle }}</span>
                                        <span class="block text-[10px] text-slate-400 truncate">{{ $subVal ?: 'Empty' }}</span>
                                    </span>
                                    <x-jaunt.icon :name="$isExpanded ? 'chevron-down' : 'chevron-right'" size="sm" class="shrink-0 text-slate-400 group-hover/item:text-blue-500" />
                                </button>
                                <button type="button" wire:click.stop="removeRepeaterItem(@js($field['key']), {{ $idx }})" class="cms-iconbtn cms-iconbtn-danger shrink-0" aria-label="Remove item" title="Remove item"><x-jaunt.icon name="trash-2" size="sm" /></button>
                            </div>

                            @if($isExpanded)
                                <div id="repeater-{{ $field['key'] }}-{{ $idx }}" class="border-t border-slate-100 bg-slate-50/70 p-3 space-y-3">
                                    @forelse($field['fields'] ?? [] as $subField)
                                        @php
                                            $subFieldValue = $item[$subField['key']] ?? '';
                                            $subFieldValue = is_array($subFieldValue) ? ($subFieldValue['en'] ?? reset($subFieldValue) ?: '') : $subFieldValue;
                                            $subFieldType = $subField['type'] ?? 'text';
                                        @endphp
                                        <div>
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $subField['label'] ?? $subField['key'] }}</label>
                                                <span class="text-[10px] text-slate-400 bg-white px-1.5 py-0.5 rounded font-mono">{{ $subFieldType }}</span>
                                            </div>

                                            @if($subFieldType === 'richtext')
                                                @include('livewire.admin.content.partials.richtext-editor', [
                                                    'field' => $subField,
                                                    'value' => $subFieldValue,
                                                    'fieldKey' => $field['key'],
                                                    'repeaterIndex' => $idx,
                                                    'subFieldKey' => $subField['key'],
                                                ])
                                            @elseif($subFieldType === 'textarea')
                                                <textarea rows="{{ $subField['rows'] ?? ($subFieldType === 'richtext' ? 5 : 3) }}"
                                                    placeholder="{{ $subField['placeholder'] ?? '' }}"
                                                    wire:change="updateRepeaterField(@js($field['key']), {{ $idx }}, @js($subField['key']), $event.target.value)"
                                                    class="w-full min-h-[72px] rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 shadow-sm outline-none resize-none transition-[border-color,box-shadow,background-color] duration-fast focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                >{{ $subFieldValue }}</textarea>
                                            @elseif($subFieldType === 'number')
                                                <input type="number"
                                                    value="{{ $subFieldValue !== '' ? $subFieldValue : ($subField['default'] ?? '') }}"
                                                    min="{{ $subField['min'] ?? '' }}"
                                                    max="{{ $subField['max'] ?? '' }}"
                                                    placeholder="{{ $subField['placeholder'] ?? '' }}"
                                                    wire:change="updateRepeaterField(@js($field['key']), {{ $idx }}, @js($subField['key']), $event.target.value)"
                                                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm"
                                                />
                                            @elseif($subFieldType === 'boolean')
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox"
                                                        wire:change="updateRepeaterField(@js($field['key']), {{ $idx }}, @js($subField['key']), $event.target.checked)"
                                                        {{ $subFieldValue ? 'checked' : '' }}
                                                        class="rounded border-slate-200 text-blue-500 focus:ring-blue-500"
                                                    />
                                                    <span class="text-sm text-slate-600">Enabled</span>
                                                </label>
                                            @elseif($subFieldType === 'select')
                                                <div class="relative">
                                                    <select wire:change="updateRepeaterField(@js($field['key']), {{ $idx }}, @js($subField['key']), $event.target.value)"
                                                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer"
                                                    >
                                                        <option value="">Select...</option>
                                                        @foreach($subField['options'] ?? [] as $option)
                                                            <option value="{{ $option['value'] ?? '' }}" {{ $subFieldValue === ($option['value'] ?? '') ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? '' }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                                                </div>
                                            @else
                                                @if($this->isLinkField($subField) && $contentChoices->isNotEmpty())
                                                    @include('livewire.admin.content.partials.link-input', [
                                                        'fieldKey' => $field['key'],
                                                        'subFieldKey' => $subField['key'],
                                                        'repeaterIndex' => $idx,
                                                        'placeholder' => $subField['placeholder'] ?? ($subFieldType === 'image' ? 'Image URL' : ''),
                                                        'value' => $subFieldValue,
                                                    ])
                                                @else
                                                    <input type="text"
                                                        value="{{ $subFieldValue }}"
                                                        placeholder="{{ $subField['placeholder'] ?? ($subFieldType === 'image' ? 'Image URL' : '') }}"
                                                        wire:change="updateRepeaterField(@js($field['key']), {{ $idx }}, @js($subField['key']), $event.target.value)"
                                                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm"
                                                    />
                                                @endif
                                            @endif

                                            @if(!empty($subField['help']))
                                                <p class="mt-1 text-[10px] text-slate-400">{{ $subField['help'] }}</p>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400">No fields configured for this repeater.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRepeaterItem('{{ $field['key'] }}')" class="cms-text-btn">
                        {{ in_array(strtolower($field['key'] ?? ''), ['buttons', 'button']) ? 'Add button' : 'Add item' }}
                    </button>
                </div>
            @elseif($field['type'] === 'select')
                <div class="relative">
                <select wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer"
                >
                    <option value="">Select...</option>
                    @if(!empty($field['options']))
                        @foreach($field['options'] as $option)
                            <option value="{{ $option['value'] ?? '' }}" {{ $fieldValue === ($option['value'] ?? '') ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? '' }}</option>
                        @endforeach
                    @elseif(isset($field['datasource']))
                        @php $datasource = \Pilot\Core\Models\Datasource::where('slug', $field['datasource'])->first(); $entries = $datasource ? $datasource->entries : collect(); @endphp
                        @foreach($entries as $entry)
                            <option value="{{ $entry->key }}" {{ $fieldValue === $entry->key ? 'selected' : '' }}>{{ $entry->value['en'] ?? $entry->key }}</option>
                        @endforeach
                    @endif
                </select>
                <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                </div>
            @elseif($field['type'] === 'reference')
                <div class="relative">
                    <select wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer"
                    >
                        <option value="">Select content...</option>
                        @foreach($contentChoices as $contentChoice)
                            <option value="{{ $contentChoice->id }}" {{ (string) $fieldValue === (string) $contentChoice->id ? 'selected' : '' }}>
                                {{ $contentChoice->name }} /{{ $contentChoice->slug }}
                            </option>
                        @endforeach
                    </select>
                    <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                </div>
            @else
                @if($this->isLinkField($field) && $contentChoices->isNotEmpty())
                    @include('livewire.admin.content.partials.link-input', [
                        'fieldKey' => $field['key'],
                        'placeholder' => $field['placeholder'] ?? '',
                        'value' => $fieldValue,
                    ])
                @else
                    <input type="text"
                        value="{{ $fieldValue }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm"
                    />
                @endif
            @endif

            @if(!empty($field['help']))
                <p class="mt-1.5 text-[10px] text-slate-400">{{ $field['help'] }}</p>
            @endif
        </div>
    @endforeach
</div>
