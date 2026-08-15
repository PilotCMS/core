<?php

namespace Pilot\Core\Support\Cms;

use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentReference;
use Pilot\Core\Models\ContentRevision;
use Pilot\Core\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContentLifecycle
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateContent(Content $content, array $attributes, ?int $userId = null): void
    {
        $oldSlug = $content->slug;

        if ($userId !== null) {
            $attributes['updated_by'] = $userId;
        }

        $content->update($attributes);

        if (array_key_exists('slug', $attributes) && $oldSlug !== $content->slug) {
            $this->createRedirectForSlugChange($content, $oldSlug);
        }
    }

    public function publish(Content $content, ?int $userId = null): ContentRevision
    {
        $revision = $this->createRevision($content, 'Published', $userId, 'published');

        $content->update([
            'status' => 'published',
            'workflow_status' => 'published',
            'published_at' => now(),
            'scheduled_for' => null,
            'review_requested_at' => null,
            'review_requested_by' => null,
            'reviewer_id' => null,
            'review_due_at' => null,
            'review_note' => null,
            'published_revision_id' => $revision->id,
            'updated_by' => $userId,
        ]);

        return $revision;
    }

    public function requestReview(Content $content, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'in_review',
            'review_requested_at' => now(),
            'review_requested_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function assignReview(Content $content, ?int $reviewerId, ?string $dueAt, ?string $note, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'in_review',
            'review_requested_at' => now(),
            'review_requested_by' => $userId,
            'reviewer_id' => $reviewerId,
            'review_due_at' => $dueAt,
            'review_note' => $note,
            'updated_by' => $userId,
        ]);
    }

    public function approveReview(Content $content, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'approved',
            'updated_by' => $userId,
        ]);
    }

    public function requestChanges(Content $content, ?string $note = null, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'changes_requested',
            'review_note' => $note ?: $content->review_note,
            'updated_by' => $userId,
        ]);
    }

    public function schedule(Content $content, string $scheduledFor, ?int $userId = null): void
    {
        $content->update([
            'status' => 'draft',
            'workflow_status' => 'scheduled',
            'scheduled_for' => $scheduledFor,
            'updated_by' => $userId,
        ]);
    }

    public function unpublish(Content $content, ?int $userId = null): void
    {
        $content->update([
            'status' => 'draft',
            'workflow_status' => 'draft',
            'published_at' => null,
            'scheduled_for' => null,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function createRevision(
        Content $content,
        ?string $label = null,
        ?int $userId = null,
        string $revisionType = 'manual',
        ?ContentRevision $sourceRevision = null,
        array $meta = [],
    ): ContentRevision {
        return ContentRevision::create([
            'content_id' => $content->id,
            'user_id' => $userId,
            'snapshot' => $this->snapshot($content),
            'label' => $label,
            'revision_type' => $revisionType,
            'source_revision_id' => $sourceRevision?->id,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function createRevisionIfChanged(
        Content $content,
        ?string $label = null,
        ?int $userId = null,
        string $revisionType = 'auto',
        ?ContentRevision $sourceRevision = null,
        array $meta = [],
    ): ?ContentRevision {
        $snapshot = $this->snapshot($content);
        $latestRevision = $content->revisions()->first();

        if ($latestRevision && $this->snapshotsMatch($latestRevision->snapshot, $snapshot)) {
            return null;
        }

        $revision = ContentRevision::create([
            'content_id' => $content->id,
            'user_id' => $userId,
            'snapshot' => $snapshot,
            'label' => $label,
            'revision_type' => $revisionType,
            'source_revision_id' => $sourceRevision?->id,
            'meta' => $meta === [] ? null : $meta,
        ]);

        if ($revisionType === 'auto') {
            $this->pruneAutoRevisions($content);
        }

        return $revision;
    }

    public function pruneAutoRevisions(Content $content): void
    {
        $retention = max(1, (int) config('cms.auto_revision_retention', 30));

        $revisionIdsToDelete = $content->revisions()
            ->where('revision_type', 'auto')
            ->skip($retention)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($revisionIdsToDelete->isEmpty()) {
            return;
        }

        ContentRevision::query()
            ->whereIn('id', $revisionIdsToDelete)
            ->delete();
    }

    public function restoreRevision(Content $content, ContentRevision $revision, ?int $userId = null): ContentRevision
    {
        return DB::transaction(function () use ($content, $revision, $userId): ContentRevision {
            $rollbackRevision = $this->createRevision(
                $content,
                'Before restore',
                $userId,
                'pre_restore',
                $revision,
                [
                    'restored_revision_id' => $revision->id,
                    'restored_revision_label' => $revision->label,
                ],
            );

            $snapshot = $revision->snapshot;

            if (isset($snapshot['content'])) {
                $this->updateContent($content, array_merge($snapshot['content'], [
                    'updated_by' => $userId,
                ]), $userId);
            }

            if (isset($snapshot['blocks'])) {
                Block::where('content_id', $content->id)->delete();

                foreach ($snapshot['blocks'] as $index => $blockSnapshot) {
                    $this->restoreSnapshotBlock($content, $blockSnapshot, null, $index);
                }
            }

            $this->syncReferences($content);

            return $rollbackRevision;
        });
    }

    public function restoreRevisionContent(Content $content, ContentRevision $revision, ?int $userId = null): ContentRevision
    {
        return DB::transaction(function () use ($content, $revision, $userId): ContentRevision {
            $rollbackRevision = $this->createRevision(
                $content,
                'Before metadata restore',
                $userId,
                'pre_restore',
                $revision,
                [
                    'restore_scope' => 'content',
                    'restored_revision_id' => $revision->id,
                    'restored_revision_label' => $revision->label,
                ],
            );

            if (isset($revision->snapshot['content'])) {
                $this->updateContent($content, array_merge($revision->snapshot['content'], [
                    'updated_by' => $userId,
                ]), $userId);
            }

            return $rollbackRevision;
        });
    }

    public function restoreRevisionBlock(Content $content, ContentRevision $revision, string $path, ?int $userId = null): ContentRevision
    {
        return DB::transaction(function () use ($content, $revision, $path, $userId): ContentRevision {
            $blockSnapshot = $this->snapshotBlockAtPath($revision->snapshot['blocks'] ?? [], $path);

            if (! $blockSnapshot) {
                abort(404);
            }

            $rollbackRevision = $this->createRevision(
                $content,
                'Before block restore',
                $userId,
                'pre_restore',
                $revision,
                [
                    'restore_scope' => 'block',
                    'restored_block_path' => $path,
                    'restored_revision_id' => $revision->id,
                    'restored_revision_label' => $revision->label,
                ],
            );

            $currentBlock = $this->contentBlockAtPath($content, $path);
            $parentPath = $this->parentPath($path);
            $parentBlock = $parentPath !== null ? $this->contentBlockAtPath($content, $parentPath) : null;
            $parentBlockId = $parentBlock?->id;
            $position = $this->pathPosition($path);

            if ($currentBlock) {
                $parentBlockId = $currentBlock->parent_block_id;
                $position = $currentBlock->position;
                $this->deleteBlockTree($currentBlock);
            }

            Block::query()
                ->where('content_id', $content->id)
                ->when($parentBlockId, fn ($query) => $query->where('parent_block_id', $parentBlockId), fn ($query) => $query->whereNull('parent_block_id'))
                ->where('position', '>=', $position)
                ->increment('position');

            $this->restoreSnapshotBlock($content, array_merge($blockSnapshot, ['position' => $position]), $parentBlockId, $position);
            $content->update(['updated_by' => $userId]);
            $this->syncReferences($content);

            return $rollbackRevision;
        });
    }

    public function syncReferences(Content $content): void
    {
        ContentReference::where('content_id', $content->id)->delete();

        $blocks = $content->allBlocks()->get();
        $blockTypes = BlockType::query()
            ->whereIn('key', $blocks->pluck('type')->unique())
            ->get()
            ->keyBy('key');

        $blocks->each(function (Block $block) use ($content, $blockTypes): void {
            $this->storeBlockReferences($content, $block, $blockTypes->get($block->type));
        });
    }

    public function syncReferencesForBlock(Content $content, Block $block): void
    {
        ContentReference::query()
            ->where('content_id', $content->id)
            ->where('block_id', $block->id)
            ->delete();

        $this->storeBlockReferences(
            $content,
            $block,
            BlockType::query()->where('key', $block->type)->first(),
        );
    }

    protected function storeBlockReferences(Content $content, Block $block, ?BlockType $blockType): void
    {
        foreach ($this->extractReferenceValues($block->data ?? [], $blockType?->schema['fields'] ?? []) as $fieldKey => $targetIds) {
            foreach ($targetIds as $targetId) {
                if ((int) $targetId === $content->id) {
                    continue;
                }

                ContentReference::firstOrCreate([
                    'content_id' => $content->id,
                    'target_content_id' => (int) $targetId,
                    'block_id' => $block->id,
                    'field_key' => $fieldKey,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Content $content): array
    {
        $blocks = $content->blocks()
            ->with('children')
            ->orderBy('position')
            ->get();

        return [
            'content' => [
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'content_type_id' => $content->content_type_id,
                'scheduled_for' => $content->scheduled_for?->toDateTimeString(),
                'meta' => $content->meta,
                'categories' => $content->categories,
                'tags' => $content->tags,
            ],
            'blocks' => $blocks->map(fn (Block $block): array => $this->snapshotBlock($block))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotBlock(Block $block): array
    {
        return [
            'id' => $block->id,
            'type' => $block->type,
            'reusable_source_block_id' => $block->reusable_source_block_id,
            'reusable_key' => $block->reusable_key,
            'reusable_name' => $block->reusable_name,
            'position' => $block->position,
            'data' => $block->data ?? [],
            'children' => $block->children
                ->sortBy('position')
                ->map(fn (Block $child): array => $this->snapshotBlock($child))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $blockSnapshot
     */
    protected function restoreSnapshotBlock(Content $content, array $blockSnapshot, ?int $parentBlockId, int $fallbackPosition): Block
    {
        $block = Block::create([
            'content_id' => $content->id,
            'type' => $blockSnapshot['type'],
            'reusable_source_block_id' => $this->restorableReusableSourceBlockId($blockSnapshot),
            'reusable_key' => $blockSnapshot['reusable_key'] ?? null,
            'reusable_name' => $blockSnapshot['reusable_name'] ?? null,
            'position' => $blockSnapshot['position'] ?? $fallbackPosition,
            'data' => $blockSnapshot['data'] ?? [],
            'parent_block_id' => $parentBlockId,
        ]);

        foreach ($blockSnapshot['children'] ?? [] as $index => $childSnapshot) {
            $this->restoreSnapshotBlock($content, $childSnapshot, $block->id, $index);
        }

        return $block;
    }

    /**
     * @param  array<string, mixed>  $blockSnapshot
     */
    protected function restorableReusableSourceBlockId(array $blockSnapshot): ?int
    {
        $sourceBlockId = $blockSnapshot['reusable_source_block_id'] ?? null;

        if (! is_numeric($sourceBlockId)) {
            return null;
        }

        $sourceBlockId = (int) $sourceBlockId;

        return Block::query()->whereKey($sourceBlockId)->exists() ? $sourceBlockId : null;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $candidate
     */
    protected function snapshotsMatch(array $current, array $candidate): bool
    {
        return json_encode($current) === json_encode($candidate);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>|null
     */
    protected function snapshotBlockAtPath(array $blocks, string $path): ?array
    {
        $segments = array_map('intval', explode('.', $path));
        $cursor = array_values($blocks);
        $block = null;

        foreach ($segments as $segment) {
            $index = max(0, $segment - 1);

            if (! array_key_exists($index, $cursor)) {
                return null;
            }

            $block = $cursor[$index];
            $cursor = array_values($block['children'] ?? []);
        }

        return $block;
    }

    protected function contentBlockAtPath(Content $content, string $path): ?Block
    {
        $blocksByParent = $content->allBlocks()
            ->get()
            ->sortBy([['position', 'asc'], ['id', 'asc']])
            ->groupBy('parent_block_id');

        $parentId = '';
        $block = null;

        foreach (array_map('intval', explode('.', $path)) as $segment) {
            $siblings = ($blocksByParent[$parentId] ?? collect())->values();
            $block = $siblings->get(max(0, $segment - 1));

            if (! $block) {
                return null;
            }

            $parentId = $block->id;
        }

        return $block;
    }

    protected function parentPath(string $path): ?string
    {
        $segments = explode('.', $path);
        array_pop($segments);

        return $segments === [] ? null : implode('.', $segments);
    }

    protected function pathPosition(string $path): int
    {
        $segments = explode('.', $path);

        return max(0, ((int) end($segments)) - 1);
    }

    protected function deleteBlockTree(Block $block): void
    {
        $block->children()->get()->each(fn (Block $child): mixed => $this->deleteBlockTree($child));
        $block->delete();
    }

    protected function createRedirectForSlugChange(Content $content, string $oldSlug): void
    {
        if ($oldSlug === '') {
            return;
        }

        Redirect::updateOrCreate(
            [
                'space_id' => $content->space_id,
                'source' => '/'.trim($oldSlug, '/'),
            ],
            [
                'content_id' => $content->id,
                'destination' => '/'.trim($content->slug, '/'),
                'status_code' => 301,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int|string>>
     */
    protected function extractReferenceValues(array $data, array $fields = []): array
    {
        $references = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'reference') {
                continue;
            }

            $fieldKey = (string) ($field['key'] ?? '');

            if ($fieldKey !== '' && array_key_exists($fieldKey, $data)) {
                $references[$fieldKey] = array_filter(Arr::wrap($data[$fieldKey]));
            }
        }

        foreach ($data as $key => $value) {
            if (str_ends_with((string) $key, '_content_id')) {
                $references[$key] = array_filter(Arr::wrap($value));
            }

            if (str_ends_with((string) $key, '_content_ids') && is_array($value)) {
                $references[$key] = array_filter($value);
            }
        }

        return $references;
    }
}
