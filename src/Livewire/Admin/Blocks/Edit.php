<?php

namespace Pilot\Core\Livewire\Admin\Blocks;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\ContentType;

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

    protected function normalizeSchema(): void
    {
        $fields = $this->schema['fields'] ?? [];
        foreach ($fields as $index => $field) {
            $type = $field['type'] ?? 'text';
            $fields[$index] = array_merge($this->defaultFieldForType($type), $field);
            if ($type !== 'select') {
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
            'default' => $type === 'boolean' ? false : '',
            'placeholder' => '',
            'help' => '',
            'min' => null,
            'max' => null,
            'rows' => $type === 'textarea' ? 4 : 3,
            'options' => $type === 'select' ? [['value' => '', 'label' => '']] : [],
            'reference_type' => $type === 'reference' ? 'content' : null,
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
        return view('livewire.admin.blocks.edit')
            ->layout('layouts.admin');
    }
}
