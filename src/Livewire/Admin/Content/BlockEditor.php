<?php

namespace Pilot\Core\Livewire\Admin\Content;

use JsonException;
use Livewire\Component;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Support\Cms\ContentCollectionResolver;

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
        $field = collect($this->blockType->schema['fields'] ?? [])
            ->first(fn (array $field): bool => ($field['key'] ?? null) === $key);
        $currentValue = $this->data[$key] ?? null;

        if (($field['translatable'] ?? false)) {
            $currentLocaleValue = is_array($currentValue) && ! array_is_list($currentValue)
                ? ($currentValue['en'] ?? null)
                : $currentValue;

            if ($currentLocaleValue == $value) {
                return;
            }

            $value = array_merge(is_array($currentValue) && ! array_is_list($currentValue) ? $currentValue : [], ['en' => $value]);
        } elseif ($currentValue == $value) {
            return;
        }

        $this->data[$key] = $value;
        $this->dispatch('block-updated', $this->block['id'], $key, $value);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, array{value: string, label: string}>
     */
    public function optionsForField(array $field): array
    {
        $usesDatasource = ($field['option_source'] ?? (isset($field['datasource']) ? 'datasource' : 'inline')) === 'datasource';

        if ($usesDatasource && filled($field['datasource'] ?? null)) {
            $content = Content::query()->find($this->block['content_id'] ?? null);
            $datasource = Datasource::query()
                ->where('slug', $field['datasource'])
                ->when($content, fn ($query) => $query->where('space_id', $content->space_id))
                ->first();

            return $datasource?->entries
                ->map(fn ($entry): array => [
                    'value' => (string) $entry->key,
                    'label' => (string) ($entry->value['en'] ?? $entry->key),
                ])
                ->values()
                ->all() ?? [];
        }

        return collect($field['options'] ?? [])
            ->filter(fn ($option): bool => isset($option['value']) && $option['value'] !== '')
            ->map(fn ($option): array => [
                'value' => (string) $option['value'],
                'label' => (string) ($option['label'] ?? $option['value']),
            ])
            ->values()
            ->all();
    }

    public function updateContentCollectionOption(string $key, string $option, mixed $value): void
    {
        $configuration = is_array($this->data[$key] ?? null) ? $this->data[$key] : [];

        $configuration[$option] = match ($option) {
            'categories', 'tags' => collect(explode(',', (string) $value))
                ->map(fn (string $item): string => trim($item))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'limit' => max(1, min(50, (int) $value)),
            'order_direction' => $value === 'asc' ? 'asc' : 'desc',
            'order_by' => in_array($value, app(ContentCollectionResolver::class)->orderableFields(), true)
                ? $value
                : 'published_at',
            default => $value,
        };

        $this->updateField($key, $configuration);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, array<string, mixed>>
     */
    public function contentCollectionPreview(array $field): array
    {
        $content = Content::query()->find($this->block['content_id'] ?? null);
        $key = (string) ($field['key'] ?? '');

        if (! $content) {
            return [];
        }

        return app(ContentCollectionResolver::class)->resolve(
            $content,
            $field,
            is_array($this->data[$key] ?? null) ? $this->data[$key] : [],
            (string) config('cms.default_locale', 'en'),
        );
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

        $currentValue = $items[$index][$subKey] ?? null;

        if (($subField['translatable'] ?? false)) {
            $currentLocaleValue = is_array($currentValue) && ! array_is_list($currentValue)
                ? ($currentValue['en'] ?? null)
                : $currentValue;

            if ($currentLocaleValue == $value) {
                return;
            }

            $nextValue = array_merge(is_array($currentValue) && ! array_is_list($currentValue) ? $currentValue : [], ['en' => $value]);
        } else {
            if ($currentValue == $value) {
                return;
            }

            $nextValue = $value;
        }

        $items[$index][$subKey] = $nextValue;
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

        if (($items[$index][$objectKey] ?? null) == $value) {
            return;
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
            'contentTypes' => ContentType::query()->orderBy('name')->get()->keyBy('key'),
        ]);
    }
}
