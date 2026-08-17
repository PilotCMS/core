<?php

namespace Pilot\Core\Livewire\Admin\Blocks;

use Livewire\Component;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\BlockTypeFolder;

class Index extends Component
{
    public $showNewFolderModal = false;

    public $newFolderName = '';

    public $search = '';

    /** @var string|null 'all', 'none', or folder id */
    public $folderFilter = 'all';

    public $sortBy = 'name';

    public $sortDir = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'folderFilter' => ['except' => 'all', 'as' => 'folder'],
        'sortBy' => ['except' => 'name', 'as' => 'sort'],
        'sortDir' => ['except' => 'asc', 'as' => 'dir'],
    ];

    public function setFolderFilter($value)
    {
        $this->folderFilter = $value;
    }

    public function setSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }
    }

    public function deleteBlockType($id)
    {
        $blockType = BlockType::findOrFail($id);
        $blockType->delete();

        $this->dispatch('block-type-deleted');
    }

    public function setFolder($blockTypeId, $folderId)
    {
        $blockType = BlockType::findOrFail($blockTypeId);
        $blockType->folder_id = $folderId ?: null;
        $blockType->save();
    }

    public function createFolder()
    {
        $this->validate([
            'newFolderName' => 'required|string|max:255',
        ]);
        BlockTypeFolder::create(['name' => $this->newFolderName]);
        $this->newFolderName = '';
        $this->showNewFolderModal = false;
    }

    public function getFoldersProperty()
    {
        return BlockTypeFolder::orderBy('name')->get();
    }

    public function getBlockTypesProperty()
    {
        $query = BlockType::with('folder');

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('key', 'like', $term);
            });
        }

        if ($this->folderFilter === 'none') {
            $query->whereNull('folder_id');
        } elseif ($this->folderFilter !== 'all' && $this->folderFilter !== '') {
            $query->where('folder_id', $this->folderFilter);
        }

        $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        $query->orderBy($this->sortBy, $dir);

        return $query->get();
    }

    public function render()
    {
        return view('livewire.admin.blocks.index', [
            'blockTypes' => $this->blockTypes,
            'folders' => $this->folders,
        ])->layout('layouts.admin');
    }
}
