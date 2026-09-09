<flux:modal wire:model="show">
    @if($editingAsset)
        <div class="pr-10">
            <flux:heading size="lg">Edit asset details</flux:heading>
            <flux:text class="mt-1 text-sm text-tertiary">Changes apply everywhere this asset is used.</flux:text>
        </div>

        <div class="mt-5 max-h-[70vh] space-y-5 overflow-y-auto pr-1">
            <div
                class="relative mx-auto aspect-[4/3] max-h-72 overflow-hidden rounded-lg bg-sunken {{ $editingAsset->isImage() ? 'cursor-crosshair' : '' }}"
                @if($editingAsset->isImage())
                    x-on:click="
                        const rect = $el.getBoundingClientRect();
                        $wire.setFocalPoint(
                            (($event.clientX - rect.left) / rect.width) * 100,
                            (($event.clientY - rect.top) / rect.height) * 100
                        );
                    "
                @endif
            >
                @if($editingAsset->isImage())
                    <img src="{{ $editingAsset->url() }}" alt="{{ $editingAsset->displayName() }}" class="h-full w-full object-cover" style="object-position: {{ $editFocalX }}% {{ $editFocalY }}%;" />
                    <div class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $editFocalX }}%; top: {{ $editFocalY }}%;">
                        <div class="size-4 rounded-full border-2 border-white bg-accent shadow"></div>
                    </div>
                @endif
            </div>

            @if($editingAsset->isImage())
                <p class="text-xs text-tertiary">Click the preview to set the global focal point. X: {{ number_format($editFocalX, 1) }}% · Y: {{ number_format($editFocalY, 1) }}%</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Display name</flux:label>
                    <flux:input wire:model="editDisplayName" />
                    <flux:error name="editDisplayName" />
                </flux:field>

                <flux:field>
                    <flux:label>Credit</flux:label>
                    <flux:input wire:model="editCredit" />
                    <flux:error name="editCredit" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Alt text</flux:label>
                <flux:input wire:model="editAlt" placeholder="Describe the image" />
                <flux:error name="editAlt" />
            </flux:field>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="editTitle" placeholder="Optional public title" />
                <flux:error name="editTitle" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="editDescription" rows="3" />
                <flux:error name="editDescription" />
            </flux:field>

            <flux:field>
                <flux:label>Tags</flux:label>
                <flux:input wire:model="editTags" placeholder="hero, landscape, summer" />
                <flux:description>Comma-separated. These tags are shared globally.</flux:description>
            </flux:field>
        </div>

        <div class="mt-5 flex flex-wrap justify-between gap-3 border-t border-subtle pt-4">
            <flux:button type="button" wire:click="closeAssetDetails" variant="ghost">Back to assets</flux:button>
            <div class="flex gap-3">
                <flux:button type="button" wire:click="saveAssetDetails" variant="primary">Save globally</flux:button>
                <flux:button type="button" wire:click="saveAndSelectAsset">Save &amp; use image</flux:button>
            </div>
        </div>
    @elseif($showUpload)
        <div class="pr-10">
            <flux:heading size="lg">Upload images</flux:heading>
            <flux:text class="mt-1 text-sm text-tertiary">Add images to the shared asset library, then edit or use them here.</flux:text>
        </div>

        <form
            wire:submit="uploadAssets"
            class="mt-5 space-y-5"
            x-data="{ preparingFiles: false, dragging: false }"
            x-on:livewire-upload-start="preparingFiles = true"
            x-on:livewire-upload-finish="preparingFiles = false"
            x-on:livewire-upload-error="preparingFiles = false"
            x-on:livewire-upload-cancel="preparingFiles = false"
            x-init="$nextTick(() => {
                if (window.PilotDroppedAssetFiles?.length) {
                    $refs.files.files = window.PilotDroppedAssetFiles;
                    delete window.PilotDroppedAssetFiles;
                    $refs.files.dispatchEvent(new Event('change', { bubbles: true }));
                }
            })"
        >
            <label
                class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-subtle bg-sunken px-6 py-10 text-center transition-colors hover:border-accent"
                x-bind:class="dragging ? 'border-accent bg-accent/5' : ''"
                x-on:dragenter.prevent="dragging = true"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent.stop="
                    dragging = false;
                    $refs.files.files = $event.dataTransfer.files;
                    $refs.files.dispatchEvent(new Event('change', { bubbles: true }));
                "
            >
                <input wire:key="picker-upload-input-{{ $uploadInputKey }}" x-ref="files" type="file" wire:model="uploadFiles" accept="image/*" multiple class="sr-only" />
                <span class="grid size-12 place-items-center rounded-full bg-card text-secondary shadow-sm">
                    <flux:icon.arrow-up-tray class="size-5" />
                </span>
                <span class="mt-4 text-sm font-semibold text-primary">Drop images here or choose files</span>
                <span class="mt-1 text-xs text-tertiary">Maximum 50MB per image</span>
            </label>

            <flux:error name="uploadFiles" />
            <flux:error name="uploadFiles.*" />

            @if($uploadFiles)
                <div class="max-h-32 space-y-2 overflow-y-auto rounded-lg border border-subtle p-3">
                    @foreach($uploadFiles as $file)
                        <div class="flex items-center gap-2 text-sm text-primary">
                            <flux:icon.photo class="size-4 text-tertiary" />
                            <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <p x-show="preparingFiles" x-cloak class="text-sm text-secondary">Preparing selected images...</p>

            <div class="flex justify-end gap-3 border-t border-subtle pt-4">
                <flux:button type="button" wire:click="closeUpload" variant="ghost">Back</flux:button>
                <flux:button type="submit" variant="primary" :disabled="empty($uploadFiles)" x-bind:disabled="preparingFiles" wire:loading.attr="disabled" wire:target="uploadAssets">
                    <span wire:loading.remove wire:target="uploadAssets">Upload images</span>
                    <span wire:loading wire:target="uploadAssets">Uploading...</span>
                </flux:button>
            </div>
        </form>
    @else
        <div
            x-data="{
                viewMode: localStorage.getItem('pilot-asset-picker-view') || @js($viewMode),
                setViewMode(mode) {
                    this.viewMode = mode;
                    localStorage.setItem('pilot-asset-picker-view', mode);
                },
            }"
        >
        <div class="pr-10">
            <flux:heading size="lg">Select image</flux:heading>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search assets..." icon="magnifying-glass" class="flex-1" />
            <flux:select wire:model.live="typeFilter" class="sm:max-w-40">
                <option value="images">Images</option>
                <option value="videos">Videos</option>
                <option value="documents">Documents</option>
                <option value="all">All types</option>
            </flux:select>
            <flux:button type="button" wire:click="openUpload" variant="primary">
                <flux:icon.arrow-up-tray class="size-4" />
                Upload
            </flux:button>
        </div>

        <div class="mt-3 flex justify-end">
            <div class="cms-seg" role="group" aria-label="Asset view">
                <button type="button" x-on:click="setViewMode('grid')" class="cms-seg-btn !w-[30px] !px-0" aria-label="Grid view" x-bind:aria-pressed="viewMode === 'grid'">
                    <flux:icon.squares-2x2 class="size-4" />
                </button>
                <button type="button" x-on:click="setViewMode('list')" class="cms-seg-btn !w-[30px] !px-0" aria-label="List view" x-bind:aria-pressed="viewMode === 'list'">
                    <flux:icon.list-bullet class="size-4" />
                </button>
            </div>
        </div>

        <div class="mt-4 flex min-h-[300px] gap-4">
            <div class="w-40 shrink-0 border-r border-subtle pr-4 sm:w-48">
                <button type="button" wire:click="selectFolder(null)" class="cms-choice-row" aria-current="{{ $folderId === null ? 'true' : 'false' }}">All</button>
                @foreach($folders as $folder)
                    <button type="button" wire:click="selectFolder({{ $folder->id }})" class="cms-choice-row" aria-current="{{ $folderId == $folder->id ? 'true' : 'false' }}">
                        <flux:icon.folder class="size-4" />
                        {{ $folder->name }}
                    </button>
                @endforeach
            </div>

            <div class="max-h-[430px] flex-1 overflow-y-auto pr-1">
                <template x-if="viewMode === 'grid'">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($assets as $asset)
                            <div class="group relative overflow-hidden rounded-xl bg-card shadow-xs dark:shadow-sm outline outline-1 -outline-offset-1 outline-[color:var(--border-subtle)] transition-[box-shadow,transform] duration-fast hover:-translate-y-px hover:shadow-md dark:hover:shadow-md">
                                <button type="button" wire:click="selectAsset({{ $asset->id }})" class="block w-full text-left focus-visible:outline-none focus-visible:shadow-ring" aria-label="Select {{ $asset->displayName() }}">
                                    @if($asset->isImage())
                                        <img src="{{ $asset->thumbnailRelativeUrl() }}" alt="{{ $asset->displayName() }}" class="aspect-[4/3] w-full object-cover transition-transform duration-slow group-hover:scale-[1.015]" loading="lazy" />
                                    @else
                                        <div class="flex aspect-[4/3] w-full items-center justify-center bg-sunken text-tertiary"><flux:icon.document class="size-6" /></div>
                                    @endif
                                    <div class="truncate px-2.5 py-2.5 pr-10 text-xs font-medium text-primary" title="{{ $asset->displayName() }}">{{ $asset->displayName() }}</div>
                                </button>
                                <button type="button" wire:click="editAsset({{ $asset->id }})" class="cms-iconbtn absolute bottom-1.5 right-1.5 bg-card text-tertiary shadow-sm hover:text-primary" aria-label="Edit {{ $asset->displayName() }} details" title="Edit asset details">
                                    <flux:icon.pencil-square class="size-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                </template>
                <template x-if="viewMode === 'list'">
                    <div class="divide-y divide-subtle">
                        @foreach($assets as $asset)
                            <div class="flex items-center gap-2 py-2">
                                <button type="button" wire:click="selectAsset({{ $asset->id }})" class="cms-choice-row !min-h-0 flex-1 !p-2">
                                    @if($asset->isImage())
                                        <img src="{{ $asset->thumbnailRelativeUrl() }}" alt="" class="size-12 rounded object-cover" loading="lazy" />
                                    @else
                                        <div class="flex size-12 items-center justify-center rounded bg-sunken"><flux:icon.document class="size-6 text-tertiary" /></div>
                                    @endif
                                    <div class="min-w-0 flex-1"><div class="truncate text-sm font-medium">{{ $asset->displayName() }}</div><div class="text-xs text-tertiary">{{ $asset->mime }}</div></div>
                                </button>
                                <button type="button" wire:click="editAsset({{ $asset->id }})" class="cms-iconbtn" aria-label="Edit {{ $asset->displayName() }} details"><flux:icon.pencil-square class="size-4" /></button>
                            </div>
                        @endforeach
                    </div>
                </template>

                @if($assets->isEmpty())
                    <div class="py-12 text-center text-sm text-tertiary">
                        <p>No assets found.</p>
                        <flux:button type="button" wire:click="openUpload" variant="ghost" size="sm" class="mt-3">Upload an image</flux:button>
                    </div>
                @endif
            </div>
        </div>
        </div>
    @endif
</flux:modal>
