<?php

namespace Pilot\Core\Livewire\Admin\Assets;

use Livewire\Component;
use Livewire\WithFileUploads;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\AssetFolder;
use Pilot\Core\Models\AssetTag;
use Pilot\Core\Models\Space;
use Pilot\Core\Support\Cms\AssetUploader;

class AssetPickerModal extends Component
{
    use WithFileUploads;

    public $show = false;

    public $fieldKey = '';

    public $spaceId = null;

    public $folderId = null;

    public $search = '';

    public string $typeFilter = 'images';

    public $viewMode = 'grid'; // grid|list

    public $uploadFiles = [];

    public int $uploadInputKey = 0;

    public bool $showUpload = false;

    public ?int $editingAssetId = null;

    public string $editDisplayName = '';

    public string $editDescription = '';

    public string $editAlt = '';

    public string $editTitle = '';

    public string $editCredit = '';

    public string $editTags = '';

    public float $editFocalX = 50.0;

    public float $editFocalY = 50.0;

    protected $listeners = ['open-asset-picker' => 'open'];

    public function open($fieldKey = '', bool $upload = false, $spaceId = null)
    {
        $this->fieldKey = $fieldKey;
        $this->spaceId = $spaceId ?: Space::first()?->id;
        $this->folderId = null;
        $this->search = '';
        $this->typeFilter = 'images';
        $this->resetPickerPanels();
        $this->showUpload = $upload;
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
        $this->fieldKey = '';
        $this->resetPickerPanels();
    }

    public function selectAsset($assetId)
    {
        $asset = $this->assetQuery()->findOrFail($assetId);
        $this->dispatch('asset-selected', [
            'fieldKey' => $this->fieldKey,
            'asset' => [
                'id' => $asset->id,
                'url' => $asset->deliveryUrl(),
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
        if (in_array($mode, ['grid', 'list'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function openUpload(): void
    {
        $this->resetValidation('uploadFiles');
        $this->reset('uploadFiles');
        $this->uploadInputKey++;
        $this->editingAssetId = null;
        $this->showUpload = true;
    }

    public function closeUpload(): void
    {
        $this->resetValidation('uploadFiles');
        $this->reset('uploadFiles');
        $this->uploadInputKey++;
        $this->showUpload = false;
    }

    public function uploadAssets(): void
    {
        if (! $this->spaceId) {
            $this->addError('uploadFiles', 'No space available. Create a space first.');

            return;
        }

        if (empty($this->uploadFiles)) {
            $this->addError('uploadFiles', 'Please select at least one image to upload.');

            return;
        }

        $this->validate([
            'uploadFiles.*' => 'image|max:51200',
        ]);

        $lastUploadedAsset = null;

        foreach ($this->uploadFiles as $file) {
            $lastUploadedAsset = app(AssetUploader::class)->store(
                $file,
                (int) $this->spaceId,
                $this->folderId ?: null,
            );
        }

        $uploadCount = count($this->uploadFiles);
        $this->closeUpload();

        if ($lastUploadedAsset) {
            $this->editAsset($lastUploadedAsset->id);
        }

        $this->dispatch('toast', message: $uploadCount === 1 ? 'Image uploaded' : "{$uploadCount} images uploaded");
    }

    public function editAsset(int $assetId): void
    {
        $asset = $this->assetQuery()->with('tags')->findOrFail($assetId);

        $this->showUpload = false;
        $this->editingAssetId = $asset->id;
        $this->editDisplayName = $asset->display_name ?? $asset->filename;
        $this->editDescription = $asset->description ?? '';
        $this->editAlt = $this->localizedValue($asset->alt);
        $this->editTitle = $this->localizedValue($asset->title);
        $this->editCredit = $asset->credit ?? '';
        $this->editTags = $asset->tags->pluck('name')->join(', ');
        $this->editFocalX = $asset->focalX();
        $this->editFocalY = $asset->focalY();
    }

    public function closeAssetDetails(): void
    {
        $this->editingAssetId = null;
        $this->resetValidation();
    }

    public function saveAssetDetails(): void
    {
        $asset = $this->assetQuery()->findOrFail($this->editingAssetId);

        $this->validate([
            'editDisplayName' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:5000',
            'editAlt' => 'nullable|string|max:500',
            'editTitle' => 'nullable|string|max:500',
            'editCredit' => 'nullable|string|max:255',
        ]);

        $asset->update([
            'display_name' => $this->editDisplayName ?: null,
            'description' => $this->editDescription ?: null,
            'alt' => $this->translatableValue($this->editAlt),
            'title' => $this->translatableValue($this->editTitle),
            'credit' => $this->editCredit ?: null,
            'focal_x' => $this->editFocalX,
            'focal_y' => $this->editFocalY,
        ]);

        $tagNames = array_filter(array_map('trim', explode(',', $this->editTags)));
        $tags = AssetTag::findOrCreateFromNames($asset->space, $tagNames);
        $asset->tags()->sync(collect($tags)->pluck('id'));

        $this->closeAssetDetails();
        $this->dispatch('toast', message: 'Asset details saved globally');
    }

    public function saveAndSelectAsset(): void
    {
        $assetId = $this->editingAssetId;

        $this->saveAssetDetails();

        if ($assetId) {
            $this->selectAsset($assetId);
        }
    }

    public function setFocalPoint(float $x, float $y): void
    {
        $this->editFocalX = max(0.0, min(100.0, round($x, 2)));
        $this->editFocalY = max(0.0, min(100.0, round($y, 2)));
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
            'editingAsset' => $this->editingAssetId ? $this->assetQuery()->find($this->editingAssetId) : null,
        ]);
    }

    protected function assetQuery()
    {
        return Asset::query()->when($this->spaceId, fn ($query) => $query->where('space_id', $this->spaceId));
    }

    protected function resetPickerPanels(): void
    {
        $this->resetValidation();
        $this->reset('uploadFiles');
        $this->uploadInputKey++;
        $this->showUpload = false;
        $this->editingAssetId = null;
    }

    protected function localizedValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value[config('cms.default_locale', 'en')] ?? reset($value) ?: '');
        }

        return (string) ($value ?? '');
    }

    /**
     * @return array{en: string}|null
     */
    protected function translatableValue(string $value): ?array
    {
        $value = trim($value);

        return $value === '' ? null : ['en' => $value];
    }
}
