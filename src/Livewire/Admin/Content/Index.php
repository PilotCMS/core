<?php

namespace Pilot\Core\Livewire\Admin\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

class Index extends Component
{
    use WithPagination;

    public $selectedFolderId = null;

    /** @var array<int> Folder IDs that are currently expanded in the tree */
    public $expandedFolderIds = [];

    public $search = '';

    public $typeFilter = 'all'; // all, page, folder, global

    public $sortBy = 'updated_at';

    public $sortDir = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all', 'as' => 'type'],
        'sortBy' => ['except' => 'updated_at', 'as' => 'sort'],
        'sortDir' => ['except' => 'desc', 'as' => 'dir'],
    ];

    public function mount($folder = null)
    {
        $this->selectedFolderId = $folder;
    }

    public function toggleFolder($folderId)
    {
        $id = (int) $folderId;
        if (in_array($id, $this->expandedFolderIds, true)) {
            $this->expandedFolderIds = array_values(array_diff($this->expandedFolderIds, [$id]));
        } else {
            $this->expandedFolderIds = array_values(array_merge($this->expandedFolderIds, [$id]));
        }
    }

    public function isFolderExpanded($folderId): bool
    {
        return in_array((int) $folderId, $this->expandedFolderIds, true);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function setTypeFilter($type)
    {
        $this->typeFilter = $type;
        $this->resetPage();
    }

    public function setSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'desc';
        }
    }

    public function selectFolder($folderId)
    {
        $this->selectedFolderId = $folderId;
        $this->resetPage();
    }

    public function getSpaceProperty()
    {
        return Space::first();
    }

    public function getStatsProperty()
    {
        $space = $this->space;
        if (! $space) {
            return ['total' => 0, 'published' => 0, 'drafts' => 0, 'languages' => 0];
        }

        $base = Content::where('space_id', $space->id);

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'drafts' => (clone $base)->where('status', 'draft')->count(),
            'languages' => max(1, $space->languages ?? 1),
        ];
    }

    public function getContentsProperty()
    {
        if (! $this->space) {
            return Content::query()->paginate(15);
        }

        $query = $this->filteredContentQuery();

        if (! $this->hasFlatFilters()) {
            $query->where('parent_id', $this->selectedFolderId);
        }

        return $query->paginate(15);
    }

    /**
     * Tree of content for expandable folders: flat list of [content, depth] in tree order.
     * When search or typeFilter is set, matching rows are shown flat so filters affect the rendered list.
     */
    public function getContentTreeProperty()
    {
        if (! $this->space) {
            return collect();
        }

        $contents = $this->filteredContentQuery()->get();

        if ($this->hasFlatFilters()) {
            return $contents->map(fn (Content $content): object => (object) [
                'content' => $content,
                'depth' => 0,
            ]);
        }

        $all = $contents->keyBy('id');

        $byParent = $all->groupBy('parent_id');

        $list = [];
        $this->appendTreeRows(
            $byParent->get($this->selectedFolderId, collect())->values(),
            0,
            $byParent,
            $list
        );

        return collect($list);
    }

    /**
     * @param  Collection<int, Content>  $items
     * @param  array<int, object{content: Content, depth: int}>  $list
     */
    protected function appendTreeRows($items, int $depth, $byParent, array &$list): void
    {
        foreach ($items as $content) {
            $list[] = (object) ['content' => $content, 'depth' => $depth];

            if ($content->isFolder() && $this->isFolderExpanded($content->id)) {
                $children = $byParent->get($content->id, collect())->values();
                $this->appendTreeRows($children, $depth + 1, $byParent, $list);
            }
        }
    }

    public function getFoldersProperty()
    {
        $space = $this->space;

        if (! $space) {
            return collect();
        }

        return Content::where('space_id', $space->id)
            ->where('type', 'folder')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function getRecentActivityProperty()
    {
        $space = $this->space;

        if (! $space) {
            return collect();
        }

        return Activity::where('space_id', $space->id)
            ->with(['user', 'subject'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function deleteContent($id)
    {
        $content = Content::findOrFail($id);

        if (! auth()->user()->can('delete content')) {
            $this->dispatch('error', message: 'You do not have permission to delete content.');

            return;
        }

        $content->delete();
        $this->dispatch('content-deleted');
    }

    protected function filteredContentQuery(): Builder
    {
        $query = Content::query()
            ->with(['updater:id,name', 'creator:id,name'])
            ->where('space_id', $this->space->id);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('categories', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        return $query->orderBy($this->safeSortColumn(), $this->safeSortDirection());
    }

    protected function hasFlatFilters(): bool
    {
        return trim($this->search) !== '' || $this->typeFilter !== 'all';
    }

    protected function safeSortColumn(): string
    {
        return in_array($this->sortBy, ['updated_at', 'name', 'created_at', 'status'], true)
            ? $this->sortBy
            : 'updated_at';
    }

    protected function safeSortDirection(): string
    {
        return $this->sortDir === 'asc' ? 'asc' : 'desc';
    }

    public function render()
    {
        return view('livewire.admin.content.index')
            ->layout('layouts.admin');
    }
}
