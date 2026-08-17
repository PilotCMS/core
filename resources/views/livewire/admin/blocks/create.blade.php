<div class="cms-drawer-page flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Create Block Type" subtitle="Define a reusable block schema." top="0px" as="header" scroll-target="#block-create-scroll" aria-label="Page header">
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <a href="{{ route('admin.blocks.index') }}" wire:navigate class="cms-btn cms-btn-secondary">
                Cancel
            </a>
            <button type="submit" form="block-type-form" class="cms-btn cms-btn-primary">
                <x-jaunt.icon name="check" size="sm" />
                Create block type
            </button>
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="flex flex-1 min-h-0">

    {{-- Main content --}}
    <main id="block-create-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            @php
                $fieldTypes = [
                    ['type' => 'text', 'label' => 'Text', 'desc' => 'Single line text'],
                    ['type' => 'textarea', 'label' => 'Textarea', 'desc' => 'Multi-line text'],
                    ['type' => 'richtext', 'label' => 'Rich Text', 'desc' => 'Formatted content'],
                    ['type' => 'number', 'label' => 'Number', 'desc' => 'Numeric value'],
                    ['type' => 'boolean', 'label' => 'Boolean', 'desc' => 'True or false'],
                    ['type' => 'image', 'label' => 'Image', 'desc' => 'Asset reference'],
                    ['type' => 'reference', 'label' => 'Reference', 'desc' => 'Content relationship'],
                    ['type' => 'select', 'label' => 'Select', 'desc' => 'Choose from options'],
                    ['type' => 'repeater', 'label' => 'Repeater', 'desc' => 'Repeatable group'],
                ];
            @endphp

            <form wire:submit="save" id="block-type-form" class="space-y-8">
                <a href="{{ route('admin.blocks.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm text-secondary transition-colors hover:text-primary" wire:navigate>
                    <x-jaunt.icon name="arrow-left" size="sm" />
                    Back to Block Types
                </a>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-800">Block Type</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Name, key, and options</p>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <flux:field>
                                <flux:label>Name</flux:label>
                                <flux:input wire:model="name" placeholder="Hero Section" />
                                <flux:error name="name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Key</flux:label>
                                <flux:input wire:model="key" placeholder="hero_section" />
                                <flux:error name="key" />
                                <flux:description>Unique identifier (snake_case)</flux:description>
                            </flux:field>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <flux:field>
                                <flux:label>Icon</flux:label>
                                <flux:input wire:model="icon" placeholder="photo" />
                                <flux:error name="icon" />
                                <flux:description>Icon name (Heroicons)</flux:description>
                            </flux:field>
                            <div class="flex items-center gap-2 pt-6">
                                <flux:checkbox wire:model="isGlobal" label="Available across all spaces" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Fields</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Add and order fields for this block</p>
                        </div>
                        <button type="button" wire:click="addField" class="cms-btn cms-btn-secondary">
                            <x-jaunt.icon name="plus" size="sm" />
                            Add field
                        </button>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            @foreach($fieldTypes as $fieldType)
                                <button
                                    type="button"
                                    wire:click="addFieldOfType('{{ $fieldType['type'] }}')"
                                    class="rounded-sm border border-default bg-card p-3 text-left transition-colors hover:border-strong hover:bg-hover"
                                >
                                    <div class="font-medium text-sm text-slate-800">{{ $fieldType['label'] }}</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $fieldType['desc'] }}</div>
                                </button>
                            @endforeach
                        </div>

                        <div class="space-y-3">
                            @forelse($schema['fields'] ?? [] as $index => $field)
                                <div
                                    wire:click="selectField({{ $index }})"
                                    class="border rounded-lg p-4 cursor-pointer transition-colors {{ $selectedFieldIndex === $index ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-slate-800">{{ $field['label'] ?: 'Untitled field' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $field['type'] ?? 'text' }} · {{ $field['key'] ?: 'key' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <flux:button type="button" wire:click.stop="moveFieldUp({{ $index }})" variant="ghost" size="xs">
                                                <flux:icon.chevron-up class="size-4" />
                                            </flux:button>
                                            <flux:button type="button" wire:click.stop="moveFieldDown({{ $index }})" variant="ghost" size="xs">
                                                <flux:icon.chevron-down class="size-4" />
                                            </flux:button>
                                            <flux:button type="button" wire:click.stop="removeField({{ $index }})" variant="ghost" size="xs" class="text-red-600 hover:text-red-700">
                                                <flux:icon.trash class="size-4" />
                                            </flux:button>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                                        @if(!empty($field['required']))
                                            <span class="rounded bg-amber-100 text-amber-700 px-1.5 py-0.5">Required</span>
                                        @endif
                                        @if(!empty($field['translatable']))
                                            <span class="rounded bg-slate-100 text-slate-600 px-1.5 py-0.5">Translatable</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500 border-2 border-dashed border-slate-200 rounded-lg p-8 text-center">
                                    No fields yet. Click a field type above to add one.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.blocks.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Cancel
                    </a>
                    <flux:button type="submit" variant="primary">
                        Create Block Type
                    </flux:button>
                </div>
            </form>
        </div>
    </main>

    {{-- Right aside: Field Settings --}}
    <aside class="cms-drawer" aria-label="Field Settings">
        <div class="cms-drawer-header">
            <h2 class="cms-drawer-title">Field settings</h2>
        </div>
        <div class="cms-drawer-body">
            @if($selectedFieldIndex !== null && isset($schema['fields'][$selectedFieldIndex]))
                <div class="space-y-5">
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model="schema.fields.{{ $selectedFieldIndex }}.type">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="richtext">Rich Text</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                            <option value="image">Image</option>
                            <option value="reference">Reference</option>
                            <option value="select">Select</option>
                            <option value="repeater">Repeater</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Label</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.label" placeholder="Headline" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Key</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.key" placeholder="headline" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Placeholder</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.placeholder" placeholder="Type here..." />
                    </flux:field>

                    <flux:field>
                        <flux:label>Help Text</flux:label>
                        <flux:textarea wire:model="schema.fields.{{ $selectedFieldIndex }}.help" rows="2"></flux:textarea>
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Default</flux:label>
                            <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.default" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Rows</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.rows" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Min</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.min" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Max</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.max" />
                        </flux:field>
                    </div>

                    <div class="flex items-center gap-4">
                        <flux:field>
                            <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.required" label="Required" />
                        </flux:field>
                        <flux:field>
                            <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.translatable" label="Translatable" />
                        </flux:field>
                    </div>

                    @if(($schema['fields'][$selectedFieldIndex]['type'] ?? '') === 'select')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <flux:label>Options</flux:label>
                                <flux:button type="button" wire:click="addOption({{ $selectedFieldIndex }})" variant="ghost" size="xs">
                                    <flux:icon.plus class="size-4" />
                                    Add Option
                                </flux:button>
                            </div>
                            <div class="space-y-2">
                                @foreach($schema['fields'][$selectedFieldIndex]['options'] ?? [] as $optionIndex => $option)
                                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.options.{{ $optionIndex }}.value" placeholder="value" />
                                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.options.{{ $optionIndex }}.label" placeholder="Label" />
                                        <flux:button type="button" wire:click="removeOption({{ $selectedFieldIndex }}, {{ $optionIndex }})" variant="ghost" size="xs" class="text-red-600">
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500">Select a field in the list to configure its settings.</p>
            @endif
        </div>
    </aside>
    </div>
</div>
