<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentRevision;

class ContentRevisionInspector
{
    public function __construct(
        protected ContentLifecycle $lifecycle,
    ) {}

    /**
     * @param  Collection<string, mixed>  $blockTypes
     * @param  array<int, string>  $ignoredContentFields
     * @return array<string, mixed>
     */
    public function compare(
        Content $content,
        ContentRevision $revision,
        ?ContentRevision $baseRevision = null,
        array $ignoredContentFields = [],
        Collection $blockTypes = new Collection,
    ): array {
        $baseSnapshot = $baseRevision?->snapshot ?? $this->lifecycle->snapshot($content->refresh());
        $revisionSnapshot = $revision->snapshot;

        $contentChanges = $this->contentChanges(
            $baseSnapshot['content'] ?? [],
            $revisionSnapshot['content'] ?? [],
            $ignoredContentFields,
        );

        $blockComparison = $this->blockComparison(
            $baseSnapshot['blocks'] ?? [],
            $revisionSnapshot['blocks'] ?? [],
            $blockTypes,
        );

        return [
            'content_changes' => $contentChanges,
            'block_summary' => $blockComparison['summary'],
            'block_changes' => $blockComparison['changes'],
            'has_changes' => $contentChanges !== [] || $blockComparison['changes'] !== [],
            'base_label' => $baseRevision?->label ?? 'Current draft',
        ];
    }

    /**
     * @param  array<string, mixed>  $currentContent
     * @param  array<string, mixed>  $revisionContent
     * @param  array<int, string>  $ignoredFields
     * @return array<int, array<string, string>>
     */
    protected function contentChanges(array $currentContent, array $revisionContent, array $ignoredFields = []): array
    {
        $labels = [
            'name' => 'Title',
            'slug' => 'Slug',
            'status' => 'Status',
            'workflow_status' => 'Workflow',
            'content_type_id' => 'Content type',
            'scheduled_for' => 'Scheduled publish',
            'meta' => 'SEO metadata',
            'categories' => 'Categories',
            'tags' => 'Tags',
        ];

        return collect($labels)
            ->reject(fn (string $label, string $field): bool => in_array($field, $ignoredFields, true))
            ->map(function (string $label, string $field) use ($currentContent, $revisionContent): ?array {
                $currentValue = $currentContent[$field] ?? null;
                $revisionValue = $revisionContent[$field] ?? null;

                if ($this->valuesMatch($currentValue, $revisionValue)) {
                    return null;
                }

                return [
                    'field' => $field,
                    'label' => $label,
                    'current' => $this->valueSummary($currentValue),
                    'revision' => $this->valueSummary($revisionValue),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $currentBlocks
     * @param  array<int, array<string, mixed>>  $revisionBlocks
     * @param  Collection<string, mixed>  $blockTypes
     * @return array{summary: array<string, int>, changes: array<int, array<string, mixed>>}
     */
    protected function blockComparison(array $currentBlocks, array $revisionBlocks, Collection $blockTypes): array
    {
        $currentByPath = $this->flattenSnapshotBlocks($currentBlocks);
        $revisionByPath = $this->flattenSnapshotBlocks($revisionBlocks);
        $paths = array_unique([...array_keys($currentByPath), ...array_keys($revisionByPath)]);

        $summary = [
            'added' => 0,
            'removed' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'current_total' => count($currentByPath),
            'revision_total' => count($revisionByPath),
        ];
        $changes = [];

        foreach ($paths as $path) {
            if (! array_key_exists($path, $currentByPath)) {
                $summary['added']++;
                $changes[] = $this->blockChange('added', $path, null, $revisionByPath[$path], $blockTypes);

                continue;
            }

            if (! array_key_exists($path, $revisionByPath)) {
                $summary['removed']++;
                $changes[] = $this->blockChange('removed', $path, $currentByPath[$path], null, $blockTypes);

                continue;
            }

            if ($this->valuesMatch($currentByPath[$path], $revisionByPath[$path])) {
                $summary['unchanged']++;

                continue;
            }

            $summary['changed']++;
            $changes[] = $this->blockChange('changed', $path, $currentByPath[$path], $revisionByPath[$path], $blockTypes);
        }

        return [
            'summary' => $summary,
            'changes' => $changes,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $currentBlock
     * @param  array<string, mixed>|null  $revisionBlock
     * @param  Collection<string, mixed>  $blockTypes
     * @return array<string, mixed>
     */
    protected function blockChange(string $action, string $path, ?array $currentBlock, ?array $revisionBlock, Collection $blockTypes): array
    {
        $block = $revisionBlock ?? $currentBlock ?? [];

        return [
            'action' => $action,
            'label' => $this->blockLabel($block, $blockTypes),
            'path' => $this->blockPathLabel($path),
            'fields' => $action === 'changed' ? $this->changedBlockFields($currentBlock ?? [], $revisionBlock ?? []) : [],
            'field_changes' => $action === 'changed' ? $this->changedBlockDataFields($currentBlock ?? [], $revisionBlock ?? [], $blockTypes) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  Collection<string, mixed>  $blockTypes
     */
    protected function blockLabel(array $block, Collection $blockTypes): string
    {
        $type = (string) ($block['type'] ?? 'Block');
        $blockType = $blockTypes[$type] ?? null;

        return $blockType?->name ?? Str::headline($type);
    }

    protected function blockPathLabel(string $path): string
    {
        return collect(explode('.', $path))
            ->map(fn (string $segment): string => (string) ((int) $segment + 1))
            ->implode('.');
    }

    /**
     * @param  array<string, mixed>  $currentBlock
     * @param  array<string, mixed>  $revisionBlock
     * @return array<int, string>
     */
    protected function changedBlockFields(array $currentBlock, array $revisionBlock): array
    {
        $labels = [
            'type' => 'type',
            'reusable_source_block_id' => 'reusable source',
            'reusable_key' => 'reusable key',
            'reusable_name' => 'reusable name',
            'position' => 'position',
            'data' => 'content',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $field): bool => ! $this->valuesMatch($currentBlock[$field] ?? null, $revisionBlock[$field] ?? null))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $currentBlock
     * @param  array<string, mixed>  $revisionBlock
     * @param  Collection<string, mixed>  $blockTypes
     * @return array<int, array<string, string>>
     */
    protected function changedBlockDataFields(array $currentBlock, array $revisionBlock, Collection $blockTypes): array
    {
        $currentData = $currentBlock['data'] ?? [];
        $revisionData = $revisionBlock['data'] ?? [];

        if (! is_array($currentData) || ! is_array($revisionData)) {
            return [];
        }

        $keys = array_unique([...array_keys($currentData), ...array_keys($revisionData)]);

        return collect($keys)
            ->filter(fn (string|int $key): bool => ! $this->valuesMatch($currentData[$key] ?? null, $revisionData[$key] ?? null))
            ->map(fn (string|int $key): array => [
                'field' => (string) $key,
                'label' => $this->blockFieldLabel($revisionBlock, (string) $key, $blockTypes),
                'current' => $this->valueSummary($currentData[$key] ?? null),
                'revision' => $this->valueSummary($revisionData[$key] ?? null),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  Collection<string, mixed>  $blockTypes
     */
    protected function blockFieldLabel(array $block, string $fieldKey, Collection $blockTypes): string
    {
        $type = (string) ($block['type'] ?? '');
        $blockType = $blockTypes[$type] ?? null;

        foreach ($blockType?->schema['fields'] ?? [] as $field) {
            if (($field['key'] ?? null) === $fieldKey) {
                return (string) ($field['label'] ?? Str::headline($fieldKey));
            }
        }

        return Str::headline($fieldKey);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, array<string, mixed>>
     */
    protected function flattenSnapshotBlocks(array $blocks, string $parentPath = ''): array
    {
        $flattened = [];

        foreach (array_values($blocks) as $index => $block) {
            $path = $parentPath === '' ? (string) $index : $parentPath.'.'.$index;
            $children = $block['children'] ?? [];

            unset($block['children']);

            $flattened[$path] = $block;
            $flattened += $this->flattenSnapshotBlocks($children, $path);
        }

        return $flattened;
    }

    protected function valuesMatch(mixed $currentValue, mixed $revisionValue): bool
    {
        return json_encode($currentValue) === json_encode($revisionValue);
    }

    protected function valueSummary(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Empty';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            if ($value === []) {
                return 'Empty';
            }

            return Str::limit(json_encode($value) ?: 'Updated data', 90);
        }

        return Str::limit((string) $value, 90);
    }
}
