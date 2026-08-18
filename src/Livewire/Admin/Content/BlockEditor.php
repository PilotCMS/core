<?php

namespace Pilot\Core\Livewire\Admin\Content;

use JsonException;
use Livewire\Component;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;

class BlockEditor extends Component
{
    public $block;

    public BlockType $blockType;

    public $data = [];

    public array $expandedRepeaterItems = [];

    /**
     * @param  array<string, mixed>  $field
     */
    public function isLinkField(array $field): bool
    {
        $type = strtolower((string) ($field['type'] ?? ''));
        $key = strtolower((string) ($field['key'] ?? ''));

        if (in_array($type, ['link', 'url'], true)) {
            return true;
        }

        return str_contains($key, 'url')
            || str_contains($key, 'href')
            || str_contains($key, 'link');
    }

    public function relativeContentUrl(Content $content): string
    {
        return '/'.trim($content->slug, '/');
    }

    public function mount($block, $blockType, array $expandedRepeaterItems = [])
    {
        $this->block = $block;
        $this->blockType = $blockType;
        $this->data = $block['data'] ?? [];
        $this->expandedRepeaterItems = $expandedRepeaterItems;
    }

    public function updateField($key, $value)
    {
        $this->data[$key] = $value;
        $this->dispatch('block-updated', $this->block['id'], $key, $value);
    }

    public function addRepeaterItem(string $key): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        $newItem = [];
        foreach ($this->blockType->schema['fields'] ?? [] as $field) {
            if (($field['key'] ?? '') === $key && isset($field['fields'])) {
                foreach ($field['fields'] as $sub) {
                    $newItem[$sub['key']] = ($sub['translatable'] ?? false) ? ['en' => ''] : '';
                }
                break;
            }
        }
        $items[] = $newItem;
        $this->data[$key] = $items;
        $this->expandedRepeaterItems[$key] = [count($items) - 1 => true];
        $this->dispatchRepeaterExpansionUpdated($key);
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function toggleRepeaterItem(string $key, int $index): void
    {
        if ($this->isRepeaterItemExpanded($key, $index)) {
            $this->expandedRepeaterItems[$key] = [];
        } else {
            $this->expandedRepeaterItems[$key] = [$index => true];
        }

        $this->dispatchRepeaterExpansionUpdated($key);
    }

    public function isRepeaterItemExpanded(string $key, int $index): bool
    {
        return (bool) ($this->expandedRepeaterItems[$key][$index] ?? false);
    }

    public function removeRepeaterItem(string $key, int $index): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        array_splice($items, $index, 1);
        $this->data[$key] = $items;

        if (isset($this->expandedRepeaterItems[$key])) {
            $expandedItems = $this->expandedRepeaterItems[$key];
            unset($expandedItems[$index]);
            $this->expandedRepeaterItems[$key] = array_values($expandedItems);
            $this->dispatchRepeaterExpansionUpdated($key);
        }

        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function updateRepeaterField(string $key, int $index, string $subKey, $value): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        if (! isset($items[$index])) {
            $items[$index] = [];
        }

        $subField = null;
        foreach ($this->blockType->schema['fields'] ?? [] as $field) {
            if (($field['key'] ?? '') !== $key) {
                continue;
            }

            foreach ($field['fields'] ?? [] as $candidate) {
                if (($candidate['key'] ?? '') === $subKey) {
                    $subField = $candidate;
                    break 2;
                }
            }
        }

        $items[$index][$subKey] = ($subField['translatable'] ?? false) ? ['en' => $value] : $value;
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function updateJsonObjectField(string $key, int $index, string $objectKey, $value): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];

        if (! isset($items[$index]) || ! is_array($items[$index])) {
            $items[$index] = [];
        }

        $items[$index][$objectKey] = $value;
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function updateJsonObjectFieldFromJson(string $key, int $index, string $objectKey, string $value): void
    {
        $errorKey = "jsonObject.{$key}.{$index}.{$objectKey}";

        try {
            $decodedValue = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->addError($errorKey, 'Enter valid JSON.');

            return;
        }

        if (! is_array($decodedValue)) {
            $this->addError($errorKey, 'The JSON value must be an array or object.');

            return;
        }

        $this->resetErrorBag($errorKey);
        $this->updateJsonObjectField($key, $index, $objectKey, $decodedValue);
    }

    protected function dispatchRepeaterExpansionUpdated(string $key): void
    {
        $this->dispatch(
            'repeater-expansion-updated',
            blockId: $this->block['id'],
            fieldKey: $key,
            expandedItems: $this->expandedRepeaterItems[$key] ?? [],
        );
    }

    public function render()
    {
        return view('livewire.admin.content.block-editor', [
            'contentChoices' => Content::query()
                ->where('type', 'page')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status']),
        ]);
    }
}
