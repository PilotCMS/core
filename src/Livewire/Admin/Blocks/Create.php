<?php

namespace Pilot\Core\Livewire\Admin\Blocks;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\BlockType;

class Create extends Component
{
    public $key = '';

    public $name = '';

    public $icon = '';

    public $isGlobal = false;

    public $schema = ['fields' => []];

    public $selectedFieldIndex = null;

    protected $rules = [
        'key' => 'required|string|max:255|unique:block_types,key',
        'name' => 'required|string|max:255',
        'icon' => 'nullable|string|max:255',
        'isGlobal' => 'boolean',
        'schema' => 'required|array',
    ];

    public function updatedName($value)
    {
        if (empty($this->key)) {
            $this->key = Str::slug($value);
        }
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

        BlockType::create([
            'key' => $this->key,
            'name' => $this->name,
            'icon' => $this->icon,
            'is_global' => $this->isGlobal,
            'schema' => $this->schema,
        ]);

        session()->flash('toast', ['message' => 'Block type created', 'type' => 'success']);

        return $this->redirect(route('admin.blocks.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.blocks.create')
            ->layout('layouts.admin');
    }
}
