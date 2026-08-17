<?php

namespace Pilot\Core\Livewire\Admin\Assets;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\AssetFolder;
use Pilot\Core\Models\AssetTag;
use Pilot\Core\Models\Space;
use Pilot\Core\Support\Cms\AssetThumbnailer;
use Pilot\Core\Support\Cms\AssetUsageFinder;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public $spaceId = null;

    public $folderId = null;

    public $uploadFiles = [];

    public $showUploadModal = false;

    public $showDetailSlideOver = false;

    public $selectedAssetId = null;

    public $showNewFolderModal = false;

    public $newFolderName = '';

    public $sortBy = 'created_at';

    public $sortDir = 'desc';

    public string $search = '';

    #[Url(as: 'type', except: 'all')]
    public string $typeFilter = 'all';

    // Edit form (for slide-over)
    public $editDisplayName = '';

    public string $editDescription = '';

    public string $editAlt = '';

    public string $editTitle = '';

    public string $editCredit = '';

    public string $editCopyright = '';

    public string $editLicense = '';

    public string $editSourceUrl = '';

    public ?string $editExpiresAt = null;

    public $editFolderId = null;

    public $editTags = '';

    public float $editFocalX = 50.0;

    public float $editFocalY = 50.0;

    public function mount()
    {
        $space = Space::first();
        $this->spaceId = $space?->id;
    }

    public function uploadAssets()
    {
        if (! $this->spaceId) {
            $this->addError('uploadFiles', 'No space available. Create a space first.');

            return;
        }

        if (empty($this->uploadFiles)) {
            $this->addError('uploadFiles', 'Please select at least one file to upload.');

            return;
        }

        $this->validate([
            'uploadFiles.*' => 'file|max:51200', // 50MB max for videos
        ]);

        // Ensure the assets directory exists on the public disk
        Storage::disk('public')->makeDirectory('assets');

        $uploadCount = count($this->uploadFiles);

        foreach ($this->uploadFiles as $file) {
            $path = $file->store('assets', 'public');

            if ($path === false) {
                $this->addError('uploadFiles', 'Failed to store file: '.$file->getClientOriginalName());

                continue;
            }

            [$width, $height] = $this->imageDimensions($file->getRealPath());

            $asset = Asset::create([
                'space_id' => $this->spaceId,
                'folder_id' => $this->folderId ?: null,
                'disk' => 'public',
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'metadata' => [
                    'client_original_name' => $file->getClientOriginalName(),
                    'client_extension' => $file->getClientOriginalExtension(),
                    'client_mime' => $file->getClientMimeType(),
                ],
            ]);

            app(AssetThumbnailer::class)->generate($asset);
        }

        $this->uploadFiles = [];
        $this->showUploadModal = false;
        session()->flash('toast', [
            'message' => $uploadCount === 1 ? 'Asset uploaded' : "{$uploadCount} assets uploaded",
            'type' => 'success',
        ]);

        return $this->redirect(route('admin.assets.index'), navigate: true);
    }

    public function openAssetDetail($assetId)
    {
        $asset = Asset::with('tags', 'folder')->findOrFail($assetId);
        $this->selectedAssetId = $assetId;
        $this->editDisplayName = $asset->display_name ?? $asset->filename;
        $this->editDescription = $asset->description ?? '';
        $this->editAlt = $asset->alt ?? '';
        $this->editTitle = $asset->title ?? '';
        $this->editCredit = $asset->credit ?? '';
        $this->editCopyright = $asset->copyright ?? '';
        $this->editLicense = $asset->license ?? '';
        $this->editSourceUrl = $asset->source_url ?? '';
        $this->editExpiresAt = $asset->expires_at?->format('Y-m-d');
        $this->editFolderId = $asset->folder_id;
        $this->editTags = $asset->tags->pluck('name')->join(', ');
        $this->editFocalX = $asset->focalX();
        $this->editFocalY = $asset->focalY();
        $this->showDetailSlideOver = true;
    }

    public function closeAssetDetail()
    {
        $this->showDetailSlideOver = false;
        $this->selectedAssetId = null;
    }

    public function saveAssetDetails()
    {
        $asset = Asset::findOrFail($this->selectedAssetId);

        $this->validate([
            'editDisplayName' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:5000',
            'editAlt' => 'nullable|string|max:500',
            'editTitle' => 'nullable|string|max:500',
            'editCredit' => 'nullable|string|max:255',
            'editCopyright' => 'nullable|string|max:255',
            'editLicense' => 'nullable|string|max:255',
            'editSourceUrl' => 'nullable|url|max:2048',
            'editExpiresAt' => 'nullable|date',
        ]);

        $asset->update([
            'display_name' => $this->editDisplayName ?: null,
            'description' => $this->editDescription ?: null,
            'alt' => $this->translatableValue($this->editAlt),
            'title' => $this->translatableValue($this->editTitle),
            'credit' => $this->editCredit ?: null,
            'copyright' => $this->editCopyright ?: null,
            'license' => $this->editLicense ?: null,
            'source_url' => $this->editSourceUrl ?: null,
            'expires_at' => $this->editExpiresAt ?: null,
            'folder_id' => $this->editFolderId ?: null,
            'focal_x' => $this->editFocalX,
            'focal_y' => $this->editFocalY,
        ]);

        // Sync tags
        $space = $asset->space;
        $tagNames = array_filter(array_map('trim', explode(',', $this->editTags)));
        $tags = AssetTag::findOrCreateFromNames($space, $tagNames);
        $asset->tags()->sync(collect($tags)->pluck('id'));

        $this->closeAssetDetail();
        $this->dispatch('toast', message: 'Asset details saved');
    }

    public function setFocalPoint(float $x, float $y): void
    {
        $this->editFocalX = max(0.0, min(100.0, round($x, 2)));
        $this->editFocalY = max(0.0, min(100.0, round($y, 2)));
    }

    public function createFolder()
    {
        $this->validate(['newFolderName' => 'required|string|max:255']);

        AssetFolder::create([
            'space_id' => $this->spaceId,
            'parent_id' => null,
            'name' => $this->newFolderName,
        ]);

        $this->newFolderName = '';
        $this->showNewFolderModal = false;
        $this->dispatch('toast', message: 'Folder created');
    }

    public function deleteAsset($id)
    {
        $asset = Asset::findOrFail($id);
        $usageCount = app(AssetUsageFinder::class)->countForAsset($asset);

        if ($usageCount > 0) {
            $this->addError('deleteAsset', "This asset is used in {$usageCount} place(s). Remove those references before deleting it.");

            return;
        }

        if ($asset->hasConfiguredDisk()) {
            Storage::disk($asset->disk)->delete(array_filter([
                $asset->path,
                $asset->thumbnail_path,
            ]));
        }

        $asset->delete();
        $this->closeAssetDetail();
        $this->dispatch('toast', message: 'Asset deleted');
    }

    public function selectFolder($folderId)
    {
        $this->folderId = ($folderId === null || $folderId === 'null' || $folderId === '') ? null : $folderId;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function setSort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $space = Space::find($this->spaceId);
        $folder = $this->folderId ? AssetFolder::find($this->folderId) : null;

        $assetsQuery = Asset::with('tags', 'folder')
            ->where('space_id', $this->spaceId)
            ->when($this->folderId !== null && $this->folderId !== '', fn ($q) => $q->where('folder_id', $this->folderId))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($query): void {
                    $query->where('filename', 'like', "%{$this->search}%")
                        ->orWhere('display_name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('mime', 'like', "%{$this->search}%")
                        ->orWhere('credit', 'like', "%{$this->search}%")
                        ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->typeFilter === 'images', fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->when($this->typeFilter === 'videos', fn ($q) => $q->where('mime', 'like', 'video/%'))
            ->when($this->typeFilter === 'documents', fn ($q) => $q->where(function ($query): void {
                $query->where('mime', 'like', 'application/%')
                    ->orWhere('mime', 'like', 'text/%');
            }))
            ->when($this->typeFilter === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now()));

        $assets = match ($this->sortBy) {
            'filename' => $assetsQuery->orderBy('filename', $this->sortDir)->paginate(24),
            'size' => $assetsQuery->orderBy('size', $this->sortDir)->paginate(24),
            default => $assetsQuery->orderBy('created_at', $this->sortDir)->paginate(24),
        };

        $folders = AssetFolder::where('space_id', $this->spaceId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allFolders = AssetFolder::where('space_id', $this->spaceId)
            ->orderBy('name')
            ->get();

        $selectedAsset = $this->selectedAssetId ? Asset::with('tags', 'folder')->find($this->selectedAssetId) : null;
        $selectedAssetUsage = $selectedAsset ? app(AssetUsageFinder::class)->forAsset($selectedAsset) : collect();

        return view('livewire.admin.assets.index', [
            'space' => $space,
            'folder' => $folder,
            'assets' => $assets,
            'folders' => $folders,
            'allFolders' => $allFolders,
            'selectedAsset' => $selectedAsset,
            'selectedAssetUsage' => $selectedAssetUsage,
        ])->layout('layouts.admin');
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected function imageDimensions(string $path): array
    {
        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            return [null, null];
        }

        return [(int) $dimensions[0], (int) $dimensions[1]];
    }

    /**
     * @return array{en: string}|null
     */
    protected function translatableValue(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return ['en' => $value];
    }
}
