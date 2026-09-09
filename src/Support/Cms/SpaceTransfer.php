<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;

class SpaceTransfer
{
    public const SCHEMA = 'pilot.space-transfer';

    public const VERSION = 1;

    /** @return array<string, mixed> */
    public function exportDatasources(Space $space): array
    {
        $datasources = $space->datasources()
            ->with('entries')
            ->orderBy('name')
            ->get()
            ->map(fn (Datasource $datasource): array => [
                'name' => $datasource->name,
                'slug' => $datasource->slug,
                'entries' => $datasource->entries->map(fn (DatasourceEntry $entry): array => [
                    'key' => $entry->key,
                    'value' => $entry->value,
                    'order' => $entry->order,
                ])->values()->all(),
            ])->values()->all();

        return $this->envelope($space, 'datasources', ['datasources' => $datasources]);
    }

    /** @return array{datasources: int, entries: int} */
    public function importDatasources(Space $space, array $document): array
    {
        $this->validateEnvelope($document, 'datasources');
        $payload = $this->validated($document, [
            'data.datasources' => ['required', 'array', 'max:1000'],
            'data.datasources.*.name' => ['required', 'string', 'max:255'],
            'data.datasources.*.slug' => ['required', 'string', 'max:255', 'alpha_dash', 'distinct'],
            'data.datasources.*.entries' => ['present', 'array', 'max:10000'],
            'data.datasources.*.entries.*.key' => ['required', 'string', 'max:255', 'alpha_dash'],
            'data.datasources.*.entries.*.value' => ['nullable', 'array'],
            'data.datasources.*.entries.*.order' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($space, $payload): array {
            $entryCount = 0;

            foreach ($payload['data']['datasources'] as $item) {
                $datasource = Datasource::query()->updateOrCreate(
                    ['space_id' => $space->id, 'slug' => $item['slug']],
                    ['name' => $item['name']],
                );

                $datasource->entries()->delete();

                foreach ($item['entries'] as $entry) {
                    $datasource->entries()->create(Arr::only($entry, ['key', 'value', 'order']));
                    $entryCount++;
                }
            }

            return ['datasources' => count($payload['data']['datasources']), 'entries' => $entryCount];
        });
    }

    /** @return array<string, mixed> */
    public function exportContent(Space $space): array
    {
        $contents = $space->contents()
            ->with(['contentType:id,key', 'allBlocks'])
            ->orderBy('id')
            ->get()
            ->map(fn (Content $content): array => [
                'source_id' => $content->id,
                'parent_source_id' => $content->parent_id,
                'content_type' => $content->contentType?->key,
                'type' => $content->type,
                'slug' => $content->slug,
                'name' => $content->name,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'published_at' => $content->published_at?->toIso8601String(),
                'scheduled_for' => $content->scheduled_for?->toIso8601String(),
                'meta' => $content->meta,
                'categories' => $content->categories,
                'tags' => $content->tags,
                'blocks' => $content->allBlocks->map(fn (Block $block): array => [
                    'source_id' => $block->id,
                    'parent_source_id' => $block->parent_block_id,
                    'reusable_source_id' => $block->reusable_source_block_id,
                    'type' => $block->type,
                    'reusable_key' => $block->reusable_key,
                    'reusable_name' => $block->reusable_name,
                    'position' => $block->position,
                    'data' => $block->data ?? [],
                ])->values()->all(),
            ])->values()->all();

        return $this->envelope($space, 'content', ['contents' => $contents]);
    }

    /** @return array{contents: int, blocks: int} */
    public function importContent(Space $space, array $document, ?int $userId = null): array
    {
        $this->validateEnvelope($document, 'content');
        $payload = $this->validated($document, [
            'data.contents' => ['required', 'array', 'max:10000'],
            'data.contents.*.source_id' => ['required', 'integer', 'distinct'],
            'data.contents.*.parent_source_id' => ['nullable', 'integer'],
            'data.contents.*.content_type' => ['nullable', 'string', 'max:255'],
            'data.contents.*.type' => ['required', 'string', 'in:page,folder,global'],
            'data.contents.*.slug' => ['required', 'string', 'max:255'],
            'data.contents.*.name' => ['required', 'string', 'max:255'],
            'data.contents.*.status' => ['required', 'string', 'in:draft,published'],
            'data.contents.*.workflow_status' => ['required', 'string', 'max:255'],
            'data.contents.*.published_at' => ['nullable', 'date'],
            'data.contents.*.scheduled_for' => ['nullable', 'date'],
            'data.contents.*.meta' => ['nullable', 'array'],
            'data.contents.*.categories' => ['nullable', 'array'],
            'data.contents.*.tags' => ['nullable', 'array'],
            'data.contents.*.blocks' => ['present', 'array', 'max:50000'],
            'data.contents.*.blocks.*.source_id' => ['required', 'integer', 'distinct'],
            'data.contents.*.blocks.*.parent_source_id' => ['nullable', 'integer'],
            'data.contents.*.blocks.*.reusable_source_id' => ['nullable', 'integer'],
            'data.contents.*.blocks.*.type' => ['required', 'string', 'max:255'],
            'data.contents.*.blocks.*.reusable_key' => ['nullable', 'string', 'max:255'],
            'data.contents.*.blocks.*.reusable_name' => ['nullable', 'string', 'max:255'],
            'data.contents.*.blocks.*.position' => ['required', 'integer', 'min:0'],
            'data.contents.*.blocks.*.data' => ['required', 'array'],
        ]);

        $items = collect($payload['data']['contents'])->keyBy('source_id');
        $this->assertParentReferences($items->all(), 'content');

        $typeIds = ContentType::query()
            ->whereIn('key', $items->pluck('content_type')->filter()->unique())
            ->pluck('id', 'key');
        $missingTypes = $items->pluck('content_type')->filter()->unique()->diff($typeIds->keys());

        if ($missingTypes->isNotEmpty()) {
            throw new InvalidArgumentException('Missing content types: '.$missingTypes->implode(', ').'.');
        }

        return DB::transaction(function () use ($space, $items, $typeIds, $userId): array {
            $contentIds = [];
            $claimedContentIds = [];
            $pending = $items->all();

            while ($pending !== []) {
                $progress = false;

                foreach ($pending as $sourceId => $item) {
                    $parentSourceId = $item['parent_source_id'];
                    if ($parentSourceId !== null && ! isset($contentIds[$parentSourceId])) {
                        continue;
                    }

                    $parentId = $parentSourceId === null ? null : $contentIds[$parentSourceId];
                    $identity = [
                        'space_id' => $space->id,
                        'parent_id' => $parentId,
                        'type' => $item['type'],
                        'slug' => $item['slug'],
                    ];
                    $content = Content::withTrashed()
                        ->where($identity)
                        ->when($claimedContentIds !== [], fn ($query) => $query->whereNotIn('id', $claimedContentIds))
                        ->orderBy('id')
                        ->first() ?? new Content($identity);

                    if ($content->trashed()) {
                        $content->restore();
                    }

                    $content->fill([
                        'content_type_id' => $item['content_type'] ? $typeIds[$item['content_type']] : null,
                        'name' => $item['name'],
                        'status' => $item['status'],
                        'workflow_status' => $item['workflow_status'],
                        'published_at' => $item['published_at'] ? Carbon::parse($item['published_at']) : null,
                        'scheduled_for' => $item['scheduled_for'] ? Carbon::parse($item['scheduled_for']) : null,
                        'meta' => $item['meta'],
                        'categories' => $item['categories'],
                        'tags' => $item['tags'],
                        'created_by' => $content->created_by ?? $userId,
                        'updated_by' => $userId,
                    ])->save();

                    $contentIds[$sourceId] = $content->id;
                    $claimedContentIds[] = $content->id;
                    unset($pending[$sourceId]);
                    $progress = true;
                }

                if (! $progress) {
                    throw new InvalidArgumentException('Content hierarchy contains a cycle.');
                }
            }

            $blockIds = [];
            $blockCount = 0;

            foreach ($items as $item) {
                Content::findOrFail($contentIds[$item['source_id']])->allBlocks()->delete();
            }

            foreach ($items as $item) {
                $pendingBlocks = collect($item['blocks'])->keyBy('source_id')->all();
                $this->assertParentReferences($pendingBlocks, 'block');

                while ($pendingBlocks !== []) {
                    $progress = false;
                    foreach ($pendingBlocks as $sourceBlockId => $blockItem) {
                        $parentSourceId = $blockItem['parent_source_id'];
                        if ($parentSourceId !== null && ! isset($blockIds[$parentSourceId])) {
                            continue;
                        }

                        $block = Block::create([
                            'content_id' => $contentIds[$item['source_id']],
                            'parent_block_id' => $parentSourceId === null ? null : $blockIds[$parentSourceId],
                            'type' => $blockItem['type'],
                            'reusable_key' => $blockItem['reusable_key'],
                            'reusable_name' => $blockItem['reusable_name'],
                            'position' => $blockItem['position'],
                            'data' => $this->remapContentReferences($blockItem['data'], $blockItem['type'], $contentIds),
                        ]);

                        $blockIds[$sourceBlockId] = $block->id;
                        unset($pendingBlocks[$sourceBlockId]);
                        $blockCount++;
                        $progress = true;
                    }

                    if (! $progress) {
                        throw new InvalidArgumentException('Block hierarchy contains a cycle.');
                    }
                }
            }

            foreach ($items as $item) {
                foreach ($item['blocks'] as $blockItem) {
                    if ($blockItem['reusable_source_id'] !== null && isset($blockIds[$blockItem['reusable_source_id']])) {
                        Block::whereKey($blockIds[$blockItem['source_id']])->update([
                            'reusable_source_block_id' => $blockIds[$blockItem['reusable_source_id']],
                        ]);
                    }
                }

                app(ContentLifecycle::class)->syncReferences(Content::findOrFail($contentIds[$item['source_id']]));
            }

            return ['contents' => $items->count(), 'blocks' => $blockCount];
        });
    }

    /** @return array<string, mixed> */
    protected function envelope(Space $space, string $resource, array $data): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'resource' => $resource,
            'exported_at' => now()->toIso8601String(),
            'space' => ['name' => $space->name, 'slug' => $space->slug],
            'data' => $data,
        ];
    }

    protected function validateEnvelope(array $document, string $resource): void
    {
        if (($document['schema'] ?? null) !== self::SCHEMA || ($document['version'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('This is not a supported Pilot transfer file.');
        }

        if (($document['resource'] ?? null) !== $resource) {
            throw new InvalidArgumentException("Choose a Pilot {$resource} export file.");
        }
    }

    /** @return array<string, mixed> */
    protected function validated(array $document, array $rules): array
    {
        $validator = Validator::make($document, $rules);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $validator->validated();
    }

    /** @param array<int|string, array<string, mixed>> $items */
    protected function assertParentReferences(array $items, string $label): void
    {
        $ids = array_map('intval', array_keys($items));

        foreach ($items as $item) {
            $parentId = $item['parent_source_id'] ?? null;
            if ($parentId !== null && ! in_array((int) $parentId, $ids, true)) {
                throw new InvalidArgumentException(ucfirst($label).' references a missing parent.');
            }
        }
    }

    /** @param array<int, int> $contentIds */
    protected function remapContentReferences(array $data, string $blockType, array $contentIds): array
    {
        $referenceKeys = BlockType::query()->where('key', $blockType)->first()?->schema['fields'] ?? [];
        $referenceKeys = collect($referenceKeys)
            ->where('type', 'reference')
            ->pluck('key')
            ->filter()
            ->all();

        foreach ($data as $key => $value) {
            if (in_array($key, $referenceKeys, true) || str_ends_with((string) $key, '_content_id')) {
                $data[$key] = is_array($value)
                    ? array_map(fn ($id) => $contentIds[$id] ?? $id, $value)
                    : ($contentIds[$value] ?? $value);
            } elseif (str_ends_with((string) $key, '_content_ids') && is_array($value)) {
                $data[$key] = array_map(fn ($id) => $contentIds[$id] ?? $id, $value);
            }
        }

        return $data;
    }
}
