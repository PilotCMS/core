<?php

namespace Pilot\Core\Livewire\Admin\Assets;

use Livewire\Component;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\AssetFolder;
use Pilot\Core\Models\Space;

class AssetPickerModal extends Component
{
    public $show = false;

    public $fieldKey = '';

    public $spaceId = null;

    public $folderId = null;

    public $search = '';

    public string $typeFilter = 'images';

    public $viewMode = 'grid'; // grid|list

    protected $listeners = ['open-asset-picker' => 'open'];

    public function open($fieldKey = '')
    {
        $this->fieldKey = $fieldKey;
        $this->spaceId = Space::first()?->id;
        $this->folderId = null;
        $this->search = '';
        $this->typeFilter = 'images';
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
        $this->fieldKey = '';
    }

    public function selectAsset($assetId)
    {
        $asset = Asset::findOrFail($assetId);
        $this->dispatch('asset-selected', [
            'fieldKey' => $this->fieldKey,
            'asset' => [
                'id' => $asset->id,
                'url' => $asset->relativeUrl(),
                'filename' => $asset->filename,
                'width' => $asset->width,
                'height' => $asset->height,
                'mime' => $asset->mime,
                'alt' => $asset->alt,
                'focal_x' => $asset->focalX(),
                'focal_y' => $asset->focalY(),
            ],
        ]);
        $this->close();
    }

    public function selectFolder($folderId)
    {
        $this->folderId = $folderId ?: null;
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function render()
    {
        $folders = collect();
        $assets = collect();

        if ($this->spaceId) {
            $folders = AssetFolder::where('space_id', $this->spaceId)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();

            $assetsQuery = Asset::where('space_id', $this->spaceId)
                ->when($this->folderId, fn ($q) => $q->where('folder_id', $this->folderId))
                ->when(! $this->folderId, fn ($q) => $q->whereNull('folder_id'))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('filename', 'like', "%{$this->search}%")
                        ->orWhere('display_name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('credit', 'like', "%{$this->search}%")
                        ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$this->search}%"));
                }))
                ->when($this->typeFilter === 'images', fn ($q) => $q->where('mime', 'like', 'image/%'))
                ->when($this->typeFilter === 'videos', fn ($q) => $q->where('mime', 'like', 'video/%'))
                ->when($this->typeFilter === 'documents', fn ($q) => $q->where(function ($query): void {
                    $query->where('mime', 'like', 'application/%')
                        ->orWhere('mime', 'like', 'text/%');
                }))
                ->orderByDesc('created_at')
                ->limit(50);

            $assets = $assetsQuery->get();
        }

        return view('livewire.admin.assets.asset-picker-modal', [
            'folders' => $folders,
            'assets' => $assets,
        ]);
    }
}
