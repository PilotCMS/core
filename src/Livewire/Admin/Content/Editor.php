<?php

namespace Pilot\Core\Livewire\Admin\Content;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Livewire\Admin\Assets\AssetPickerModal;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockComment;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentPresence;
use Pilot\Core\Models\ContentRevision;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\EditorPreference;
use Pilot\Core\Support\Cms\ContentLifecycle;
use Pilot\Core\Support\Cms\ContentRevisionInspector;
use Pilot\Core\Support\Cms\ContentSyncFingerprint;

class Editor extends Component
{
    public Content $content;

    public $selectedBlockId = null;

    public $blocks = [];

    public $blockTypes = [];

    public $blockLibraryOpen = false;

    public $addBlockPosition = null; // 'above'|'below'|null, when set opens library to insert at position

    public $addBlockParentId = null;

    public $addBlockColumnIndex = null;

    public $drawerOpen = true;

    public $leftSidebarCollapsed = false;

    public $rightPanelTab = 'content';

    public string $saveState = 'saved';

    public ?string $conflictMessage = null;

    public $lastSavedAt = null;

    public $savedJustNow = false;

    public $scheduledFor = '';

    public $selectedPreviewTargetId = '';

    public string $newCommentBody = '';

    public string $reviewerId = '';

    public string $reviewDueAt = '';

    public string $reviewNote = '';

    public string $reusableBlockName = '';

    public string $checkpointLabel = '';

    public bool $revisionModalOpen = false;

    public ?int $selectedRevisionId = null;

    public $compareRevisionId = '';

    public string $revisionTypeFilter = '';

    public string $revisionAuthorFilter = '';

    public int $revisionsPerPage = 20;

    public int $previewVersion = 1;

    public int $editorSyncVersion = 1;

    public array $expandedRepeaterItemsByBlock = [];

    public ?string $lastKnownContentUpdatedAt = null;

    public ?string $lastKnownContentSyncKey = null;

    protected $listeners = [
        'block-updated' => 'handleBlockUpdated',
        'repeater-expansion-updated' => 'handleRepeaterExpansionUpdated',
        'asset-selected' => 'handleAssetSelected',
        'open-asset-picker' => 'handleOpenAssetPicker',
        'content-external-change-detected' => 'syncExternalChanges',
    ];

    public function mount(Content $content)
    {
        $this->content = $content;
        $this->lastKnownContentUpdatedAt = $content->updated_at?->toJSON();
        $this->loadBlocks();
        $this->lastKnownContentSyncKey = ContentSyncFingerprint::makeFromBlocks($content, $this->blocks);
        $this->blockTypes = $this->availableBlockTypes()->keyBy('key');
        $this->scheduledFor = $content->scheduled_for?->format('Y-m-d\TH:i') ?? '';
        $this->reviewerId = (string) ($content->reviewer_id ?? '');
        $this->reviewDueAt = $content->review_due_at?->format('Y-m-d\TH:i') ?? '';
        $this->reviewNote = $content->review_note ?? '';
        $this->selectedPreviewTargetId = $content->space?->previewTargets()
            ->where('is_default', true)
            ->value('id') ?? $content->space?->previewTargets()->value('id') ?? '';

        // Load editor preferences
        $prefs = EditorPreference::get(auth()->id(), 'editor', []);
        $this->leftSidebarCollapsed = $prefs['leftSidebarCollapsed'] ?? false;
        $this->drawerOpen = $prefs['drawerOpen'] ?? true;
        $previewTargetPreferences = $prefs['previewTargets'] ?? [];
        $this->selectedPreviewTargetId = $previewTargetPreferences[$content->space_id] ?? $this->selectedPreviewTargetId;
        $this->touchPresence();
    }

    public function loadBlocks()
    {
        $this->blocks = $this->content->blocks()
            ->with(['children' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    public function updatedSelectedPreviewTargetId($value): void
    {
        $prefs = EditorPreference::get(auth()->id(), 'editor', []);
        $prefs['previewTargets'][$this->content->space_id] = $value;
        EditorPreference::set(auth()->id(), 'editor', $prefs);

        $this->dispatchPreviewFrameRefresh();
    }

    public function addBlock($blockTypeKey, $position = null)
    {
        $this->markSaving();

        if (! $this->availableBlockTypes()->pluck('key')->contains($blockTypeKey)) {
            return;
        }

        $blockType = BlockType::where('key', $blockTypeKey)->firstOrFail();
        $parentId = $this->addBlockParentId ? (int) $this->addBlockParentId : null;

        $insertPosition = 0;
        if ($position !== null && is_numeric($position)) {
            $insertPosition = (int) $position;
        } elseif ($this->addBlockPosition === 'inside' && $parentId) {
            $insertPosition = Block::where('content_id', $this->content->id)
                ->where('parent_block_id', $parentId)
                ->count();
        } elseif ($this->addBlockPosition === 'above' && $this->selectedBlockId) {
            $selectedBlock = Block::where('content_id', $this->content->id)->findOrFail($this->selectedBlockId);
            $parentId = $selectedBlock->parent_block_id;
            $insertPosition = $selectedBlock->position;
        } elseif ($this->addBlockPosition === 'below' && $this->selectedBlockId) {
            $selectedBlock = Block::where('content_id', $this->content->id)->findOrFail($this->selectedBlockId);
            $parentId = $selectedBlock->parent_block_id;
            $insertPosition = $selectedBlock->position + 1;
        } else {
            $insertPosition = $this->blocks ? max(array_column($this->blocks, 'position')) + 1 : 0;
        }

        $this->createUndoCheckpoint('Before block add', ['operation' => 'add_block']);

        // Shift positions
        Block::where('content_id', $this->content->id)
            ->when($parentId, fn ($query) => $query->where('parent_block_id', $parentId), fn ($query) => $query->whereNull('parent_block_id'))
            ->where('position', '>=', $insertPosition)
            ->increment('position');

        $block = Block::create([
            'content_id' => $this->content->id,
            'parent_block_id' => $parentId,
            'type' => $blockType->key,
            'position' => $insertPosition,
            'data' => $this->getDefaultDataForBlockType($blockType, $this->addBlockColumnIndex),
        ]);

        $this->addBlockParentId = null;
        $this->addBlockColumnIndex = null;
        $this->addBlockPosition = null;
        $this->blockLibraryOpen = false;
        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();

        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => Block::class,
            'subject_id' => $block->id,
        ]);
    }

    public function addBlockAbove($blockId)
    {
        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $this->addBlockPosition = 'above';
        $this->addBlockParentId = $block->parent_block_id;
        $this->addBlockColumnIndex = $block->data['_column'] ?? null;
        $this->selectedBlockId = $blockId;
        $this->blockLibraryOpen = true;
    }

    public function addBlockBelow($blockId)
    {
        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $this->addBlockPosition = 'below';
        $this->addBlockParentId = $block->parent_block_id;
        $this->addBlockColumnIndex = $block->data['_column'] ?? null;
        $this->selectedBlockId = $blockId;
        $this->blockLibraryOpen = true;
    }

    public function addNestedBlock($parentBlockId, $columnIndex = null): void
    {
        $parentBlock = Block::where('content_id', $this->content->id)->findOrFail($parentBlockId);

        if (! $this->blockCanContainBlocks($parentBlock->type)) {
            return;
        }

        $this->addBlockParentId = $parentBlock->id;
        $this->addBlockColumnIndex = $columnIndex !== null ? (int) $columnIndex : null;
        $this->addBlockPosition = 'inside';
        $this->selectedBlockId = $parentBlock->id;
        $this->blockLibraryOpen = true;
    }

    public function duplicateBlock($blockId)
    {
        $this->markSaving();

        $original = Block::findOrFail($blockId);
        $this->createUndoCheckpoint('Before block duplicate', ['operation' => 'duplicate_block']);

        $newPosition = $original->position + 1;
        Block::where('content_id', $this->content->id)
            ->when($original->parent_block_id, fn ($query) => $query->where('parent_block_id', $original->parent_block_id), fn ($query) => $query->whereNull('parent_block_id'))
            ->where('position', '>=', $newPosition)
            ->increment('position');

        $block = Block::create([
            'content_id' => $this->content->id,
            'parent_block_id' => $original->parent_block_id,
            'reusable_source_block_id' => $original->reusable_source_block_id,
            'type' => $original->type,
            'reusable_key' => $original->reusable_key,
            'reusable_name' => $original->reusable_name,
            'position' => $newPosition,
            'data' => $original->data,
        ]);

        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();
    }

    public function updateContent($field, $value)
    {
        $this->markSaving();

        $lifecycle = app(ContentLifecycle::class);
        $this->createUndoCheckpoint('Before page edit', ['operation' => 'update_content', 'field' => $field]);

        // Auto-generate slug when name changes and slug hasn't been manually edited
        if ($field === 'name' && $this->content->slug === Str::slug($this->content->name)) {
            $lifecycle->updateContent($this->content, [
                'name' => $value,
                'slug' => Str::slug($value),
            ], auth()->id());
        } elseif ($field === 'parent_id') {
            $lifecycle->updateContent($this->content, [
                'parent_id' => $value ?: null,
            ], auth()->id());
        } elseif ($field === 'status') {
            if ($value === 'published') {
                $lifecycle->publish($this->content, auth()->id());
            } else {
                $lifecycle->unpublish($this->content, auth()->id());
            }
        } else {
            $lifecycle->updateContent($this->content, [
                $field => $value,
            ], auth()->id());
        }
        $this->content->refresh();
        $this->markSaved();
    }

    public function getFoldersProperty()
    {
        return Content::where('space_id', $this->content->space_id)
            ->where('type', 'folder')
            ->where('id', '!=', $this->content->id)
            ->orderBy('name')
            ->get();
    }

    public function getPreviewTargetsProperty()
    {
        return $this->content->space?->previewTargets()->get() ?? collect();
    }

    public function getPreviewTargetOriginsProperty(): array
    {
        return $this->previewTargets
            ->map(function ($target): ?string {
                $parts = parse_url((string) $target->url);

                if (! isset($parts['scheme'], $parts['host'])) {
                    return null;
                }

                $origin = $parts['scheme'].'://'.$parts['host'];

                if (isset($parts['port'])) {
                    $origin .= ':'.$parts['port'];
                }

                return $origin;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getContentTypesProperty()
    {
        return ContentType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function availableBlockTypes()
    {
        $allowedBlocks = $this->content->contentType?->allowed_blocks ?? [];

        return BlockType::query()
            ->when(! empty($allowedBlocks), fn ($query) => $query->whereIn('key', $allowedBlocks))
            ->orderBy('name')
            ->get();
    }

    public function updateContentMeta($key, $value)
    {
        $this->markSaving();
        $this->createUndoCheckpoint('Before metadata edit', ['operation' => 'update_content_meta', 'field' => $key]);

        $meta = $this->content->meta ?? [];
        $meta[$key] = $value;
        $this->content->update([
            'meta' => $meta,
            'updated_by' => auth()->id(),
        ]);
        $this->content->refresh();
        $this->markSaved();
    }

    public function updateTaxonomy(string $field, string $value): void
    {
        $this->markSaving();

        if (! in_array($field, ['categories', 'tags'], true)) {
            return;
        }

        $this->createUndoCheckpoint('Before taxonomy edit', ['operation' => 'update_taxonomy', 'field' => $field]);

        app(ContentLifecycle::class)->updateContent($this->content, [
            $field => $this->taxonomyValuesFromString($value),
        ], auth()->id());

        $this->content->refresh();
        $this->markSaved();
    }

    public function handleBlockUpdated($blockId = null, $fieldKey = null, $value = null)
    {
        if ($blockId === null) {
            return;
        }
        if (is_array($blockId)) {
            $fieldKey = $blockId['fieldKey'] ?? $blockId[1] ?? null;
            $value = $blockId['value'] ?? $blockId[2] ?? null;
            $blockId = $blockId['blockId'] ?? $blockId[0] ?? null;
        }
        if ($blockId !== null && $fieldKey !== null) {
            $this->updateBlock($blockId, $fieldKey, $value);
        }
    }

    public function handleRepeaterExpansionUpdated($blockId = null, $fieldKey = null, $expandedItems = []): void
    {
        if (is_array($blockId)) {
            $fieldKey = $blockId['fieldKey'] ?? $blockId[1] ?? null;
            $expandedItems = $blockId['expandedItems'] ?? $blockId[2] ?? [];
            $blockId = $blockId['blockId'] ?? $blockId[0] ?? null;
        }

        if ($blockId === null || $fieldKey === null) {
            return;
        }

        $this->expandedRepeaterItemsByBlock[(int) $blockId][(string) $fieldKey] = is_array($expandedItems) ? $expandedItems : [];
    }

    public function updateBlock($blockId, $fieldKey, $value)
    {
        $this->markSaving();

        $block = Block::findOrFail($blockId);
        $this->createUndoCheckpoint('Before block edit', ['operation' => 'update_block', 'block_id' => $block->id, 'field' => $fieldKey]);

        $data = $block->data ?? [];
        $data[$fieldKey] = $value;
        $block->update(['data' => $data]);
        $this->syncReusableBlockInstances($block);

        $this->content->update(['updated_by' => auth()->id()]);
        app(ContentLifecycle::class)->syncReferencesForBlock($this->content, $block);

        $this->updateLoadedBlockData((int) $blockId, $data);
        $this->selectedBlockId = $blockId;
        $this->markSaved();
        $this->skipRender();
    }

    public function deleteBlock($blockId)
    {
        $this->markSaving();

        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $this->createUndoCheckpoint('Before block delete', ['operation' => 'delete_block', 'block_id' => $block->id]);

        $position = $block->position;
        $parentBlockId = $block->parent_block_id;

        DB::transaction(function () use ($block, $parentBlockId, $position): void {
            $this->deleteBlockTree($block);

            Block::where('content_id', $this->content->id)
                ->when($parentBlockId, fn ($query) => $query->where('parent_block_id', $parentBlockId), fn ($query) => $query->whereNull('parent_block_id'))
                ->where('position', '>', $position)
                ->decrement('position');

            $this->content->touch();
            $this->content->update(['updated_by' => auth()->id()]);
            app(ContentLifecycle::class)->syncReferences($this->content);
        });

        $this->loadBlocks();
        $this->selectedBlockId = $parentBlockId ?? ($this->blocks[0]['id'] ?? null);
        $this->markSaved();
    }

    protected function deleteBlockTree(Block $block): void
    {
        $block->children()->get()->each(fn (Block $child): mixed => $this->deleteBlockTree($child));
        $block->delete();
    }

    public function sortItem($itemId, $position)
    {
        $this->markSaving();

        $blockIds = array_column($this->blocks, 'id');
        $currentIndex = array_search((int) $itemId, $blockIds);

        if ($currentIndex === false) {
            return;
        }

        $this->createUndoCheckpoint('Before block reorder', ['operation' => 'sort_block', 'block_id' => (int) $itemId]);

        array_splice($blockIds, $currentIndex, 1);
        array_splice($blockIds, $position, 0, [(int) $itemId]);

        foreach ($blockIds as $index => $blockId) {
            Block::where('id', $blockId)->update(['position' => $index]);
        }

        $this->content->touch();
        $this->content->update(['updated_by' => auth()->id()]);
        $this->loadBlocks();
        $this->markSaved();
    }

    public function moveBlockUp($blockId): void
    {
        $this->moveBlock($blockId, -1);
    }

    public function moveBlockDown($blockId): void
    {
        $this->moveBlock($blockId, 1);
    }

    protected function moveBlock($blockId, int $direction): void
    {
        $this->markSaving();

        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $siblings = $this->movableSiblingsFor($block);
        $currentIndex = $siblings->search(fn (Block $sibling): bool => $sibling->id === $block->id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;

        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        $this->createUndoCheckpoint('Before block move', ['operation' => 'move_block', 'block_id' => $block->id]);

        $ordered = $siblings->values()->all();
        [$ordered[$currentIndex], $ordered[$targetIndex]] = [$ordered[$targetIndex], $ordered[$currentIndex]];

        foreach ($ordered as $position => $sibling) {
            $sibling->update(['position' => $position]);
        }

        $this->content->touch();
        $this->content->update(['updated_by' => auth()->id()]);
        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();
    }

    protected function movableSiblingsFor(Block $block)
    {
        $column = $block->data['_column'] ?? null;

        return Block::query()
            ->where('content_id', $block->content_id)
            ->when(
                $block->parent_block_id,
                fn ($query) => $query->where('parent_block_id', $block->parent_block_id),
                fn ($query) => $query->whereNull('parent_block_id')
            )
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(function (Block $sibling) use ($column): bool {
                $siblingColumn = $sibling->data['_column'] ?? null;

                return $siblingColumn === $column;
            })
            ->values();
    }

    public function publish()
    {
        $this->markSaving();

        app(ContentLifecycle::class)->createRevisionIfChanged($this->content, 'Before publish', auth()->id(), 'auto');
        app(ContentLifecycle::class)->publish($this->content, auth()->id());
        $this->content->refresh();

        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'published',
            'subject_type' => Content::class,
            'subject_id' => $this->content->id,
        ]);

        $this->dispatch('published');
        $this->markSaved();
    }

    public function unpublish()
    {
        $this->markSaving();
        app(ContentLifecycle::class)->unpublish($this->content, auth()->id());
        $this->content->refresh();
        $this->dispatch('toast', message: 'Content unpublished', suppressAutosave: true);
        $this->markSaved();
    }

    public function requestReview(): void
    {
        $this->markSaving();
        app(ContentLifecycle::class)->requestReview($this->content, auth()->id());
        $this->content->refresh();
        $this->dispatch('toast', message: 'Review requested', suppressAutosave: true);
        $this->markSaved();
    }

    public function schedulePublishing(): void
    {
        $this->markSaving();

        $this->validate([
            'scheduledFor' => 'required|date|after:now',
        ]);

        app(ContentLifecycle::class)->schedule($this->content, $this->scheduledFor, auth()->id());
        $this->content->refresh();
        $this->dispatch('toast', message: 'Publishing scheduled', suppressAutosave: true);
        $this->markSaved();
    }

    public function assignReview(): void
    {
        $this->markSaving();

        $this->validate([
            'reviewerId' => 'nullable|exists:users,id',
            'reviewDueAt' => 'nullable|date',
            'reviewNote' => 'nullable|string|max:2000',
        ]);

        app(ContentLifecycle::class)->assignReview(
            $this->content,
            $this->reviewerId !== '' ? (int) $this->reviewerId : null,
            $this->reviewDueAt !== '' ? $this->reviewDueAt : null,
            $this->reviewNote !== '' ? $this->reviewNote : null,
            auth()->id(),
        );

        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'requested review',
            'subject_type' => Content::class,
            'subject_id' => $this->content->id,
            'meta' => [
                'reviewer_id' => $this->reviewerId,
                'due_at' => $this->reviewDueAt,
            ],
        ]);

        $this->content->refresh();
        $this->markSaved();
    }

    public function approveReview(): void
    {
        $this->markSaving();
        app(ContentLifecycle::class)->approveReview($this->content, auth()->id());
        $this->content->refresh();
        $this->markSaved();
    }

    public function requestChanges(): void
    {
        $this->markSaving();
        app(ContentLifecycle::class)->requestChanges($this->content, $this->reviewNote, auth()->id());
        $this->content->refresh();
        $this->markSaved();
    }

    public function addBlockComment(): void
    {
        $this->validate([
            'newCommentBody' => 'required|string|max:2000',
        ]);

        BlockComment::create([
            'content_id' => $this->content->id,
            'block_id' => $this->selectedBlockId,
            'user_id' => auth()->id(),
            'body' => $this->newCommentBody,
        ]);

        $this->newCommentBody = '';
    }

    public function resolveBlockComment(int $commentId): void
    {
        BlockComment::query()
            ->where('content_id', $this->content->id)
            ->whereKey($commentId)
            ->update(['resolved_at' => now()]);
    }

    public function touchPresence(): void
    {
        if (! auth()->check()) {
            return;
        }

        $selectedBlockId = $this->validSelectedBlockId();
        $this->selectedBlockId = $selectedBlockId;

        ContentPresence::updateOrCreate(
            [
                'content_id' => $this->content->id,
                'user_id' => auth()->id(),
            ],
            [
                'selected_block_id' => $selectedBlockId,
                'status' => $selectedBlockId ? 'editing' : 'viewing',
                'last_seen_at' => now(),
            ],
        );
    }

    public function updatedSelectedBlockId(): void
    {
        $this->touchPresence();
        $this->newCommentBody = '';
        $this->reusableBlockName = '';
        $this->dispatch('preview-selection-sync', blockId: $this->selectedBlockId ? (int) $this->selectedBlockId : null);
    }

    public function makeSelectedBlockReusable(): void
    {
        if (! $this->selectedBlockId) {
            return;
        }

        $this->validate([
            'reusableBlockName' => 'required|string|max:120',
        ]);

        $block = Block::query()
            ->where('content_id', $this->content->id)
            ->findOrFail($this->selectedBlockId);

        $this->createUndoCheckpoint('Before reusable block change', ['operation' => 'make_reusable_block', 'block_id' => $block->id]);

        $block->update([
            'reusable_source_block_id' => null,
            'reusable_key' => Str::slug($this->reusableBlockName).'-'.$block->id,
            'reusable_name' => $this->reusableBlockName,
        ]);

        $this->reusableBlockName = '';
        $this->loadBlocks();
        $this->markSaved();
    }

    public function insertReusableBlock(int $sourceBlockId): void
    {
        $this->markSaving();

        $source = Block::query()
            ->whereNull('reusable_source_block_id')
            ->whereNotNull('reusable_key')
            ->findOrFail($sourceBlockId);

        $this->createUndoCheckpoint('Before reusable block insert', ['operation' => 'insert_reusable_block', 'source_block_id' => $source->id]);

        $position = $this->blocks ? max(array_column($this->blocks, 'position')) + 1 : 0;

        $block = Block::create([
            'content_id' => $this->content->id,
            'parent_block_id' => null,
            'reusable_source_block_id' => $source->id,
            'type' => $source->type,
            'reusable_key' => $source->reusable_key,
            'reusable_name' => $source->reusable_name,
            'position' => $position,
            'data' => $source->data ?? [],
        ]);

        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();
    }

    public function createRevision(?string $label = null): void
    {
        app(ContentLifecycle::class)->createRevision($this->content, $label, auth()->id());
    }

    public function saveCheckpoint(): void
    {
        $this->validate([
            'checkpointLabel' => 'nullable|string|max:120',
        ]);

        $label = trim($this->checkpointLabel) !== '' ? trim($this->checkpointLabel) : 'Manual checkpoint';

        app(ContentLifecycle::class)->createRevisionIfChanged($this->content, $label, auth()->id(), 'manual');
        $this->checkpointLabel = '';
        $this->markSaved();
    }

    public function undoLastChange(): void
    {
        $undoRevision = $this->undoRevision;

        if (! $undoRevision) {
            return;
        }

        $this->markSaving();

        $rollbackRevision = app(ContentLifecycle::class)->restoreRevision($this->content, $undoRevision, auth()->id());

        $this->recordRevisionRestoreActivity($undoRevision, $rollbackRevision, 'undo', [
            'consumed_revision_id' => $undoRevision->id,
            'consumed_revision_label' => $undoRevision->label,
        ]);

        $undoRevision->delete();

        $this->loadBlocks();
        $this->content->refresh();
        $this->selectedBlockId = $this->validSelectedBlockId();
        $this->selectedRevisionId = null;
        $this->compareRevisionId = '';
        $this->editorSyncVersion++;
        $this->markSaved();
    }

    public function openRevisionModal(): void
    {
        $this->revisionModalOpen = true;
    }

    public function openCheckpointModal(): void
    {
        $this->revisionModalOpen = true;
    }

    public function closeRevisionModal(): void
    {
        $this->revisionModalOpen = false;
    }

    public function restoreRevision($revisionId): void
    {
        $revision = ContentRevision::where('content_id', $this->content->id)->findOrFail($revisionId);
        $rollbackRevision = app(ContentLifecycle::class)->restoreRevision($this->content, $revision, auth()->id());

        $this->loadBlocks();
        $this->content->refresh();

        $this->recordRevisionRestoreActivity($revision, $rollbackRevision, 'full');

        $this->selectedBlockId = $this->validSelectedBlockId();
        $this->selectedRevisionId = null;
        $this->compareRevisionId = '';
        $this->editorSyncVersion++;
        $this->markSaved();
    }

    public function restoreSelectedRevisionContent(): void
    {
        $revision = $this->selectedRevision;

        if (! $revision) {
            return;
        }

        $rollbackRevision = app(ContentLifecycle::class)->restoreRevisionContent($this->content, $revision, auth()->id());
        $this->recordRevisionRestoreActivity($revision, $rollbackRevision, 'content');
        $this->content->refresh();
        $this->editorSyncVersion++;
        $this->markSaved();
    }

    public function restoreSelectedRevisionBlock(string $path): void
    {
        $revision = $this->selectedRevision;

        if (! $revision) {
            return;
        }

        $rollbackRevision = app(ContentLifecycle::class)->restoreRevisionBlock($this->content, $revision, $path, auth()->id());
        $this->recordRevisionRestoreActivity($revision, $rollbackRevision, 'block', ['block_path' => $path]);
        $this->loadBlocks();
        $this->content->refresh();
        $this->editorSyncVersion++;
        $this->markSaved();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function recordRevisionRestoreActivity(ContentRevision $revision, ContentRevision $rollbackRevision, string $scope, array $meta = []): void
    {
        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'restored revision',
            'subject_type' => Content::class,
            'subject_id' => $this->content->id,
            'meta' => [
                'restored_revision_id' => $revision->id,
                'restored_revision_label' => $revision->label,
                'rollback_revision_id' => $rollbackRevision->id,
                'restore_scope' => $scope,
                ...$meta,
            ],
        ]);
    }

    public function selectRevision(int $revisionId): void
    {
        ContentRevision::query()
            ->where('content_id', $this->content->id)
            ->findOrFail($revisionId);

        $this->selectedRevisionId = $revisionId;
        $this->compareRevisionId = '';
        $this->revisionModalOpen = true;
    }

    public function clearSelectedRevision(): void
    {
        $this->selectedRevisionId = null;
        $this->compareRevisionId = '';
    }

    public function selectPublishedRevision(): void
    {
        if (! $this->content->published_revision_id) {
            return;
        }

        $this->selectRevision((int) $this->content->published_revision_id);
    }

    public function loadMoreRevisions(): void
    {
        $this->revisionsPerPage += 20;
    }

    public function updatedRevisionTypeFilter(): void
    {
        $this->revisionsPerPage = 20;
    }

    public function updatedRevisionAuthorFilter(): void
    {
        $this->revisionsPerPage = 20;
    }

    protected function markSaved(): void
    {
        $this->content->refresh();
        $this->lastKnownContentUpdatedAt = $this->content->updated_at?->toJSON();
        $this->lastKnownContentSyncKey = ContentSyncFingerprint::makeFromBlocks($this->content, $this->blocks);
        $this->lastSavedAt = now();
        $this->saveState = 'saved';
        $this->conflictMessage = null;
        $this->savedJustNow = true;
        $this->previewVersion++;
        $this->dispatch('saved');
        $this->dispatchPreviewFrameRefresh();
    }

    protected function markSaving(): void
    {
        $this->saveState = 'saving';
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function createUndoCheckpoint(string $label, array $meta = []): ?ContentRevision
    {
        return app(ContentLifecycle::class)->createRevisionIfChanged(
            $this->content,
            $label,
            auth()->id(),
            'auto',
            null,
            ['undoable' => true, ...$meta],
        );
    }

    protected function validSelectedBlockId(): ?int
    {
        if (! $this->selectedBlockId) {
            return null;
        }

        $selectedBlockId = (int) $this->selectedBlockId;

        return Block::query()
            ->where('content_id', $this->content->id)
            ->whereKey($selectedBlockId)
            ->exists()
                ? $selectedBlockId
                : null;
    }

    /**
     * @return array<int, string>
     */
    protected function taxonomyValuesFromString(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->values()
            ->all();
    }

    public function syncExternalChanges(): void
    {
        $freshContent = Content::query()->find($this->content->id);

        if (! $freshContent) {
            return;
        }

        $updatedAt = $freshContent->updated_at?->toJSON();
        $syncKey = ContentSyncFingerprint::make($freshContent);

        if ($updatedAt === null || $syncKey === $this->lastKnownContentSyncKey) {
            return;
        }

        $this->content = $freshContent;
        $this->lastKnownContentUpdatedAt = $updatedAt;
        $this->lastKnownContentSyncKey = $syncKey;
        $this->loadBlocks();
        $this->previewVersion++;
        $this->editorSyncVersion++;
        $this->saveState = 'conflict';
        $this->conflictMessage = 'This content changed in another session. The editor has refreshed to the latest version.';
        $this->lastSavedAt = $freshContent->updated_at;
        $this->dispatchPreviewFrameRefresh();
    }

    protected function dispatchPreviewFrameRefresh(): void
    {
        $this->dispatch('preview-frame-refresh', url: $this->previewFrameUrl);
    }

    public function setSelectedBlockFromPreview(int $blockId): void
    {
        if (! $this->findBlockInTree($blockId)) {
            return;
        }

        $this->selectedBlockId = $blockId;
        $this->drawerOpen = true;
        $this->rightPanelTab = 'content';
        $this->touchPresence();
    }

    public function handleOpenAssetPicker($payload = null)
    {
        if ($payload === null) {
            return;
        }
        $fieldKey = is_array($payload) ? ($payload['fieldKey'] ?? $payload[0] ?? '') : $payload;
        $this->dispatch('open-asset-picker', fieldKey: $fieldKey)->to(AssetPickerModal::class);
    }

    public function handleAssetSelected($payload = null)
    {
        if ($payload === null) {
            return;
        }
        $fieldKey = $payload['fieldKey'] ?? $payload[0] ?? null;
        $asset = $payload['asset'] ?? $payload[1] ?? null;

        if ($fieldKey && $asset && $this->selectedBlockId) {
            $url = is_array($asset) ? ($asset['url'] ?? '') : ($asset->url ?? '');
            $url = Asset::toRelativeUrl($url);
            $this->updateBlock($this->selectedBlockId, $fieldKey, $url);

            if (is_array($asset)) {
                if (isset($asset['focal_x'])) {
                    $this->updateBlock($this->selectedBlockId, $fieldKey.'_focal_x', (float) $asset['focal_x']);
                }

                if (isset($asset['focal_y'])) {
                    $this->updateBlock($this->selectedBlockId, $fieldKey.'_focal_y', (float) $asset['focal_y']);
                }
            }
        }
    }

    public function getContentTreeProperty()
    {
        $space = $this->content->space;
        if (! $space) {
            return collect();
        }

        return Content::where('space_id', $space->id)
            ->whereNull('parent_id')
            ->orderBy('type')
            ->orderBy('name')
            ->with('children')
            ->get();
    }

    public function getBreadcrumbsProperty()
    {
        $crumbs = [];
        $current = $this->content->parent;
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent;
        }

        return $crumbs;
    }

    public function getRevisionsProperty()
    {
        return $this->content->revisions()
            ->with(['user', 'sourceRevision'])
            ->when($this->revisionTypeFilter !== '', fn ($query) => $query->where('revision_type', $this->revisionTypeFilter))
            ->when($this->revisionAuthorFilter !== '', fn ($query) => $query->where('user_id', (int) $this->revisionAuthorFilter))
            ->take($this->revisionsPerPage)
            ->get();
    }

    public function getRevisionTotalCountProperty(): int
    {
        return $this->content->revisions()
            ->when($this->revisionTypeFilter !== '', fn ($query) => $query->where('revision_type', $this->revisionTypeFilter))
            ->when($this->revisionAuthorFilter !== '', fn ($query) => $query->where('user_id', (int) $this->revisionAuthorFilter))
            ->count();
    }

    public function getUndoRevisionProperty(): ?ContentRevision
    {
        return $this->content->revisions()
            ->reorder()
            ->where('revision_type', 'auto')
            ->where('meta->undoable', true)
            ->latest()
            ->latest('id')
            ->first();
    }

    public function getRevisionTypeOptionsProperty(): Collection
    {
        return $this->content->revisions()
            ->reorder()
            ->select('revision_type')
            ->distinct()
            ->orderBy('revision_type')
            ->pluck('revision_type')
            ->filter()
            ->values();
    }

    public function getRevisionAuthorOptionsProperty(): Collection
    {
        return User::query()
            ->whereIn('id', $this->content->revisions()->whereNotNull('user_id')->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getComparisonRevisionOptionsProperty(): Collection
    {
        return $this->content->revisions()
            ->with('user')
            ->when($this->selectedRevisionId, fn ($query) => $query->whereKeyNot($this->selectedRevisionId))
            ->take(50)
            ->get();
    }

    public function getSelectedRevisionProperty(): ?ContentRevision
    {
        if (! $this->selectedRevisionId) {
            return null;
        }

        return ContentRevision::query()
            ->where('content_id', $this->content->id)
            ->with(['user', 'sourceRevision'])
            ->find($this->selectedRevisionId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSelectedRevisionComparisonProperty(): ?array
    {
        $revision = $this->selectedRevision;

        if (! $revision) {
            return null;
        }

        $baseRevision = $this->compareRevisionId
            ? ContentRevision::query()
                ->where('content_id', $this->content->id)
                ->find($this->compareRevisionId)
            : null;

        return app(ContentRevisionInspector::class)->compare(
            $this->content,
            $revision,
            baseRevision: $baseRevision,
            blockTypes: collect($this->blockTypes),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublishedRevisionComparisonProperty(): ?array
    {
        if (! $this->content->published_revision_id) {
            return null;
        }

        $revision = ContentRevision::query()
            ->where('content_id', $this->content->id)
            ->find($this->content->published_revision_id);

        if (! $revision) {
            return null;
        }

        return app(ContentRevisionInspector::class)->compare(
            $this->content,
            $revision,
            ignoredContentFields: ['status', 'workflow_status', 'scheduled_for'],
            blockTypes: collect($this->blockTypes),
        );
    }

    public function getReviewersProperty(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getActivePresencesProperty(): Collection
    {
        return ContentPresence::query()
            ->where('content_id', $this->content->id)
            ->where('user_id', '!=', auth()->id())
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->with(['user', 'selectedBlock'])
            ->latest('last_seen_at')
            ->get();
    }

    public function getSelectedBlockCommentsProperty(): Collection
    {
        return BlockComment::query()
            ->where('content_id', $this->content->id)
            ->where('block_id', $this->selectedBlockId)
            ->whereNull('resolved_at')
            ->with('user')
            ->latest()
            ->get();
    }

    public function getReusableBlocksProperty(): Collection
    {
        return Block::query()
            ->whereNull('reusable_source_block_id')
            ->whereNotNull('reusable_key')
            ->with('content')
            ->orderBy('reusable_name')
            ->get();
    }

    public function getValidationIssuesProperty(): Collection
    {
        $issues = collect();

        if (blank($this->content->name)) {
            $issues->push([
                'severity' => 'error',
                'label' => 'Page title is required.',
                'block_id' => null,
            ]);
        }

        if (blank($this->content->slug)) {
            $issues->push([
                'severity' => 'error',
                'label' => 'Slug is required.',
                'block_id' => null,
            ]);
        }

        collect($this->flattenBlocks())->each(function (array $block) use ($issues): void {
            $blockType = $this->blockTypes[$block['type']] ?? null;

            foreach ($blockType?->schema['fields'] ?? [] as $field) {
                if (! ($field['required'] ?? false)) {
                    continue;
                }

                $value = $block['data'][$field['key']] ?? null;

                if (is_array($value)) {
                    $value = $value['en'] ?? reset($value) ?: null;
                }

                if (blank($value)) {
                    $issues->push([
                        'severity' => 'error',
                        'label' => ($blockType->name ?? $block['type']).' is missing '.$field['label'].'.',
                        'block_id' => $block['id'],
                    ]);
                }
            }
        });

        if (blank($this->content->meta['meta_title'] ?? null)) {
            $issues->push([
                'severity' => 'warning',
                'label' => 'Meta title is empty.',
                'block_id' => null,
            ]);
        }

        if (blank($this->content->meta['meta_description'] ?? null)) {
            $issues->push([
                'severity' => 'warning',
                'label' => 'Meta description is empty.',
                'block_id' => null,
            ]);
        }

        return $issues;
    }

    public function getSelectedBlockProperty(): ?array
    {
        return $this->selectedBlockId ? $this->findBlockInTree((int) $this->selectedBlockId) : null;
    }

    public function getPreviewUrlProperty(): string
    {
        $target = $this->selectedPreviewTarget();

        return $target ? $target->previewUrlFor($this->content) : route('admin.content.preview', $this->content);
    }

    public function getPreviewFrameUrlProperty(): string
    {
        $target = $this->selectedPreviewTarget();

        if ($target) {
            return $this->appendPreviewFrameParameters($target->previewUrlFor($this->content));
        }

        return $this->appendPreviewFrameParameters(route('admin.content.preview', ['content' => $this->content]));
    }

    protected function selectedPreviewTarget()
    {
        if (! $this->selectedPreviewTargetId) {
            return null;
        }

        return $this->content->space?->previewTargets()
            ->whereKey($this->selectedPreviewTargetId)
            ->first();
    }

    protected function appendPreviewFrameParameters(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query([
            'v' => $this->previewVersion,
            'pilot_in_context' => 0,
            'pilot_in_context_panel' => 0,
            'pilot_selected_block' => $this->selectedBlockId ? (int) $this->selectedBlockId : '',
        ]);
    }

    protected function getDefaultDataForBlockType($blockType, ?int $columnIndex = null): array
    {
        $data = [];
        foreach ($blockType->schema['fields'] ?? [] as $field) {
            $data[$field['key']] = $field['default'] ?? '';
            if (($field['translatable'] ?? false)) {
                $data[$field['key']] = ['en' => $data[$field['key']]];
            }
            if (($field['type'] ?? '') === 'repeater') {
                $data[$field['key']] = [];
            }
        }

        if ($columnIndex !== null) {
            $data['_column'] = $columnIndex;
        }

        return $data;
    }

    protected function findBlockInTree(int $blockId, ?array $blocks = null): ?array
    {
        foreach ($blocks ?? $this->blocks as $block) {
            if ((int) $block['id'] === $blockId) {
                return $block;
            }

            $child = $this->findBlockInTree($blockId, $block['children'] ?? []);

            if ($child) {
                return $child;
            }
        }

        return null;
    }

    protected function updateLoadedBlockData(int $blockId, array $data): void
    {
        $this->blocks = $this->replaceBlockData($this->blocks, $blockId, $data);
    }

    protected function replaceBlockData(array $blocks, int $blockId, array $data): array
    {
        return array_map(function (array $block) use ($blockId, $data): array {
            if ((int) $block['id'] === $blockId) {
                $block['data'] = $data;

                return $block;
            }

            if (! empty($block['children'])) {
                $block['children'] = $this->replaceBlockData($block['children'], $blockId, $data);
            }

            return $block;
        }, $blocks);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function flattenBlocks(?array $blocks = null): array
    {
        $flattened = [];

        foreach ($blocks ?? $this->blocks as $block) {
            $flattened[] = $block;
            $flattened = array_merge($flattened, $this->flattenBlocks($block['children'] ?? []));
        }

        return $flattened;
    }

    protected function syncReusableBlockInstances(Block $block): void
    {
        if ($block->reusable_source_block_id !== null || $block->reusable_key === null) {
            return;
        }

        Block::query()
            ->where('reusable_source_block_id', $block->id)
            ->update([
                'type' => $block->type,
                'reusable_key' => $block->reusable_key,
                'reusable_name' => $block->reusable_name,
                'data' => $block->data ?? [],
            ]);
    }

    protected function blockCanContainBlocks(string $blockTypeKey): bool
    {
        $blockType = $this->blockTypes[$blockTypeKey] ?? BlockType::where('key', $blockTypeKey)->first();

        return (bool) ($blockType?->schema['can_contain_blocks'] ?? false);
    }

    public function render()
    {
        return view('livewire.admin.content.editor')
            ->layout('layouts.admin');
    }
}
