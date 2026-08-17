<?php

namespace Pilot\Core\Livewire\Admin\ContentTypes;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\ContentType;

class Index extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $key = '';

    public string $description = '';

    public array $schema = ['fields' => []];

    public array $allowedBlocks = [];

    public array $settings = [
        'url_pattern' => '/{slug}',
        'preview_enabled' => true,
    ];

    public bool $isActive = true;

    public ?int $selectedFieldIndex = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'unique:content_types,key,'.($this->editingId ?? 'NULL').',id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'schema' => ['required', 'array'],
            'allowedBlocks' => ['array'],
            'settings.url_pattern' => ['required', 'string', 'max:255'],
            'settings.preview_enabled' => ['boolean'],
            'isActive' => ['boolean'],
        ];
    }

    public function updatedName(string $value): void
    {
        if ($this->key === '') {
            $this->key = Str::slug($value);
        }
    }

    public function create(): void
    {
        $this->resetForm();
    }

    public function edit(int $contentTypeId): void
    {
        $contentType = ContentType::findOrFail($contentTypeId);

        $this->editingId = $contentType->id;
        $this->name = $contentType->name;
        $this->key = $contentType->key;
        $this->description = $contentType->description ?? '';
        $this->schema = $contentType->schema ?: ['fields' => []];
        $this->allowedBlocks = $contentType->allowed_blocks ?: [];
        $this->settings = array_merge($this->settings, $contentType->settings ?: []);
        $this->isActive = $contentType->is_active;
        $this->selectedFieldIndex = ! empty($this->schema['fields']) ? 0 : null;
    }

    public function save(): void
    {
        $this->validate();
        $message = $this->editingId ? 'Content type saved' : 'Content type created';

        ContentType::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'key' => $this->key,
                'description' => $this->description,
                'schema' => $this->schema,
                'allowed_blocks' => $this->allowedBlocks,
                'settings' => $this->settings,
                'is_active' => $this->isActive,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: $message);
    }

    public function addFieldOfType(string $type): void
    {
        $this->schema['fields'][] = $this->defaultFieldForType($type);
        $this->selectedFieldIndex = count($this->schema['fields']) - 1;
    }

    public function removeField(int $index): void
    {
        unset($this->schema['fields'][$index]);
        $this->schema['fields'] = array_values($this->schema['fields']);
        $this->selectedFieldIndex = empty($this->schema['fields']) ? null : min($index, count($this->schema['fields']) - 1);
    }

    public function selectField(int $index): void
    {
        $this->selectedFieldIndex = $index;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'key', 'description', 'allowedBlocks', 'isActive', 'selectedFieldIndex']);
        $this->schema = ['fields' => []];
        $this->settings = [
            'url_pattern' => '/{slug}',
            'preview_enabled' => true,
        ];
        $this->isActive = true;
    }

    protected function defaultFieldForType(string $type): array
    {
        return [
            'type' => $type,
            'key' => '',
            'label' => '',
            'required' => false,
            'translatable' => false,
            'default' => $type === 'boolean' ? false : '',
            'placeholder' => '',
            'help' => '',
        ];
    }

    public function render()
    {
        return view('livewire.admin.content-types.index', [
            'contentTypes' => ContentType::query()->orderBy('name')->get(),
            'blockTypes' => BlockType::query()->orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
