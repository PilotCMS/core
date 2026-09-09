<?php

namespace Pilot\Core\Livewire\Admin\Blocks;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;

class Edit extends Component
{
    public BlockType $blockType;

    public $key = '';

    public $name = '';

    public $icon = '';

    public $isGlobal = false;

    public $schema = ['fields' => []];

    public $selectedFieldIndex = null;

    public function mount(BlockType $blockType)
    {
        $this->blockType = $blockType;
        $this->key = $blockType->key;
        $this->name = $blockType->name;
        $this->icon = $blockType->icon;
        $this->isGlobal = $blockType->is_global;
        $this->schema = $blockType->schema;
        $this->normalizeSchema();
        if (! empty($this->schema['fields'])) {
            $this->selectedFieldIndex = 0;
        }
    }

    protected function rules()
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('block_types', 'key')->ignore($this->blockType->id),
            ],
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'isGlobal' => 'boolean',
            'schema' => 'required|array',
        ];
    }

    public function addField()
    {
        $this->addFieldOfType('text');
    }

    public function addFieldOfType(string $type)
    {
        $this->schema['fields'][] = $this->defaultFieldForType($type);
        $this->selectedFieldIndex = count($this->schema['fields']) - 1;
    }

    public function updatedSchema(mixed $value, string $key): void
    {
        if (! preg_match('/^fields\.(\d+)\.type$/', $key, $matches)) {
            return;
        }

        $index = (int) $matches[1];
        $type = (string) $value;
        $defaults = $this->defaultFieldForType($type);
        $existingOptions = $this->schema['fields'][$index]['options'] ?? [];

        $this->schema['fields'][$index]['default'] = $defaults['default'];
        $this->schema['fields'][$index]['options'] = in_array($type, ['select', 'multiselect'], true)
            ? ($existingOptions ?: $defaults['options'])
            : [];
    }

    public function removeField($index)
    {
        unset($this->schema['fields'][$index]);
        $this->schema['fields'] = array_values($this->schema['fields']);
        if ($this->selectedFieldIndex === $index) {
            $this->selectedFieldIndex = count($this->schema['fields']) ? 0 : null;
        }
    }

    public function moveFieldUp($index)
    {
        if ($index <= 0) {
            return;
        }

        $fields = $this->schema['fields'];
        [$fields[$index - 1], $fields[$index]] = [$fields[$index], $fields[$index - 1]];
        $this->schema['fields'] = array_values($fields);
        $this->selectedFieldIndex = $index - 1;
    }

    public function moveFieldDown($index)
    {
        if ($index >= count($this->schema['fields']) - 1) {
            return;
        }

        $fields = $this->schema['fields'];
        [$fields[$index + 1], $fields[$index]] = [$fields[$index], $fields[$index + 1]];
        $this->schema['fields'] = array_values($fields);
        $this->selectedFieldIndex = $index + 1;
    }

    public function selectField($index)
    {
        $this->selectedFieldIndex = $index;
    }

    public function addOption($fieldIndex)
    {
        $this->schema['fields'][$fieldIndex]['options'][] = ['value' => '', 'label' => ''];
    }

    public function removeOption($fieldIndex, $optionIndex)
    {
        unset($this->schema['fields'][$fieldIndex]['options'][$optionIndex]);
        $this->schema['fields'][$fieldIndex]['options'] = array_values($this->schema['fields'][$fieldIndex]['options']);
    }

    public function addMapping(int $fieldIndex): void
    {
        $this->schema['fields'][$fieldIndex]['mappings'][] = ['target' => '', 'source' => ''];
    }

    public function removeMapping(int $fieldIndex, int $mappingIndex): void
    {
        unset($this->schema['fields'][$fieldIndex]['mappings'][$mappingIndex]);
        $this->schema['fields'][$fieldIndex]['mappings'] = array_values($this->schema['fields'][$fieldIndex]['mappings']);
    }

    protected function normalizeSchema(): void
    {
        $fields = $this->schema['fields'] ?? [];
        foreach ($fields as $index => $field) {
            $type = $field['type'] ?? 'text';
            $fields[$index] = array_merge($this->defaultFieldForType($type), $field);
            if (! array_key_exists('option_source', $field) && filled($field['datasource'] ?? null)) {
                $fields[$index]['option_source'] = 'datasource';
            }
            if (! in_array($type, ['select', 'multiselect'], true)) {
                $fields[$index]['options'] = [];
            } elseif (empty($fields[$index]['options'])) {
                $fields[$index]['options'] = [['value' => '', 'label' => '']];
            }
        }
        $this->schema['fields'] = array_values($fields);
    }

    protected function defaultFieldForType(string $type): array
    {
        return [
            'type' => $type,
            'key' => '',
            'label' => '',
            'translatable' => false,
            'required' => false,
            'default' => match ($type) {
                'boolean' => false,
                'multiselect' => [],
                'content_collection' => $this->defaultContentCollectionQuery(),
                default => '',
            },
            'placeholder' => '',
            'help' => '',
            'min' => null,
            'max' => null,
            'rows' => $type === 'textarea' ? 4 : 3,
            'options' => in_array($type, ['select', 'multiselect'], true) ? [['value' => '', 'label' => '']] : [],
            'option_source' => 'inline',
            'datasource' => null,
            'reference_type' => $type === 'reference' ? 'content' : null,
            'source_content_type' => null,
            'mappings' => $type === 'content_collection' ? [['target' => '', 'source' => '']] : [],
        ];
    }

    protected function defaultContentCollectionQuery(): array
    {
        return [
            'categories' => [],
            'tags' => [],
            'limit' => 6,
            'order_by' => 'published_at',
            'order_direction' => 'desc',
        ];
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function (): void {
            $originalKey = $this->blockType->key;

            if ($this->key !== $originalKey) {
                Block::query()
                    ->where('type', $originalKey)
                    ->update(['type' => $this->key]);

                ContentType::query()
                    ->whereNotNull('allowed_blocks')
                    ->each(function (ContentType $contentType) use ($originalKey): void {
                        $allowedBlocks = $contentType->allowed_blocks ?? [];

                        if (! in_array($originalKey, $allowedBlocks, true)) {
                            return;
                        }

                        $contentType->update([
                            'allowed_blocks' => array_values(array_unique(array_map(
                                fn ($blockKey) => $blockKey === $originalKey ? $this->key : $blockKey,
                                $allowedBlocks,
                            ))),
                        ]);
                    });
            }

            $this->blockType->update([
                'key' => $this->key,
                'name' => $this->name,
                'icon' => $this->icon,
                'is_global' => $this->isGlobal,
                'schema' => $this->schema,
            ]);
        });

        session()->flash('toast', ['message' => 'Block type saved', 'type' => 'success']);

        return $this->redirect(route('admin.blocks.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.blocks.edit', [
            'contentTypes' => ContentType::query()->where('is_active', true)->orderBy('name')->get(),
            'datasources' => Datasource::query()->with('space')->orderBy('name')->get(),
        ])
            ->layout('layouts.admin');
    }
}
