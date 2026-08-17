<div class="flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Assets" subtitle="Manage media and files." top="0px" as="header" scroll-target="#assets-scroll" aria-label="Page header" />

    <div class="flex flex-1 min-h-0">
    <main class="flex-1 min-w-0 overflow-hidden">
<div class="flex flex-col lg:flex-row h-full">
    <!-- Left Sidebar: Folders (horizontal on mobile, vertical on desktop) -->
    <div class="lg:w-64 shrink-0 border-b lg:border-b-0 lg:border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 overflow-x-auto lg:overflow-x-visible lg:overflow-y-auto">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between gap-2">
            <flux:heading size="md">Folders</flux:heading>
            <flux:button wire:click="$set('showNewFolderModal', true)" variant="ghost" size="sm" square aria-label="New folder" title="New folder">
                <flux:icon.folder-plus class="size-4" />
            </flux:button>
        </div>
        <div class="p-3 flex lg:flex-col gap-0.5">
            <button
                type="button"
                wire:click="selectFolder(null)"
                class="cms-choice-row shrink-0 lg:w-full"
                aria-current="{{ $folderId === null ? 'true' : 'false' }}"
            >
                <div class="flex items-center gap-2">
                    <flux:icon.folder class="size-4" />
                    <span>All Assets</span>
                </div>
            </button>
            @foreach($folders as $f)
                <button
                    type="button"
                    wire:click="selectFolder({{ $f->id }})"
                    class="cms-choice-row shrink-0 lg:w-full"
                    aria-current="{{ $folderId === $f->id ? 'true' : 'false' }}"
                >
                    <div class="flex items-center gap-2">
                        <flux:icon.folder class="size-4" />
                        <span>{{ $f->name }}</span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <div id="assets-scroll" class="flex-1 overflow-y-auto min-w-0">
        <div class="p-6 md:p-8">
            <div class="mb-6">
                <flux:heading>
                    @if($folder)
                        {{ $folder->name }}
                    @else
                        All Assets
                    @endif
                </flux:heading>
                <flux:text class="text-muted-foreground text-sm mt-1">Images, videos, and documents for your content</flux:text>
            </div>
            <div class="flex flex-col gap-4 mb-8 pb-6 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search assets, tags, credit..." icon="magnifying-glass" class="sm:max-w-xs" />
                        <flux:select wire:model.live="typeFilter" class="sm:max-w-44">
                            <option value="all">All types</option>
                            <option value="images">Images</option>
                            <option value="videos">Videos</option>
                            <option value="documents">Documents</option>
                            <option value="expired">Expired rights</option>
                        </flux:select>
                    </div>
                    <flux:button wire:click="$set('showUploadModal', true)" variant="primary" size="sm">
                        <flux:icon.arrow-up-tray class="size-4" />
                        Upload
                    </flux:button>
                </div>

                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Sort:</span>
                    <div class="cms-seg" role="group" aria-label="Sort assets">
                        <button type="button" wire:click="setSort('created_at')" class="cms-seg-btn" aria-pressed="{{ $sortBy === 'created_at' ? 'true' : 'false' }}">Date</button>
                        <button type="button" wire:click="setSort('filename')" class="cms-seg-btn" aria-pressed="{{ $sortBy === 'filename' ? 'true' : 'false' }}">Name</button>
                        <button type="button" wire:click="setSort('size')" class="cms-seg-btn" aria-pressed="{{ $sortBy === 'size' ? 'true' : 'false' }}">Size</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-5 2xl:grid-cols-4" wire:key="assets-grid-{{ $assets->currentPage() }}-{{ $assets->count() }}">
                @forelse($assets as $asset)
                    @php
                        $assetSize = $asset->size >= 1048576
                            ? number_format($asset->size / 1048576, 1).' MB'
                            : number_format($asset->size / 1024, 1).' KB';
                        $assetExtension = strtoupper(pathinfo($asset->filename, PATHINFO_EXTENSION));
                        $remainingTagCount = max(0, $asset->tags->count() - 1);
                    @endphp
                    <button
                        type="button"
                        wire:click="openAssetDetail({{ $asset->id }})"
                        class="group relative block w-full overflow-hidden rounded-xl bg-card text-left shadow-xs outline outline-1 -outline-offset-1 outline-[color:var(--border-subtle)] transition-[box-shadow,transform] duration-fast ease-standard hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:shadow-ring"
                        aria-label="Open {{ $asset->displayName() }} details"
                    >
                        <div class="relative aspect-[4/3] overflow-hidden bg-sunken">
                            {{-- Preview: Image --}}
                            @if($asset->isImage())
                                <img
                                    src="{{ $asset->thumbnailUrl() }}"
                                    alt="{{ $asset->displayName() }}"
                                    class="h-full w-full object-cover transition-transform duration-slow ease-standard group-hover:scale-[1.015]"
                                    loading="lazy"
                                />
                            {{-- Preview: Video --}}
                            @elseif($asset->isVideo())
                                <div class="relative flex h-full w-full items-center justify-center overflow-hidden bg-gray-900">
                                    <video
                                        src="{{ $asset->url() }}"
                                        class="h-full w-full object-contain transition-transform duration-slow ease-standard group-hover:scale-[1.015]"
                                        muted
                                        preload="metadata"
                                    ></video>
                                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/15">
                                        <span class="grid size-11 place-items-center rounded-full bg-black/55 text-white shadow-md backdrop-blur-sm">
                                            <flux:icon.play class="size-5 translate-x-px" />
                                        </span>
                                    </div>
                                </div>
                            {{-- Preview: Document --}}
                            @else
                                <div class="flex h-full w-full flex-col items-center justify-center bg-sunken p-4 text-tertiary">
                                    <span class="grid size-14 place-items-center rounded-xl border border-subtle bg-card shadow-xs">
                                        <flux:icon.document class="size-7" />
                                    </span>
                                    <span class="mt-2 text-2xs font-semibold uppercase tracking-[var(--ls-caps)]">{{ $assetExtension ?: 'FILE' }}</span>
                                </div>
                            @endif

                            @if($asset->isExpired())
                                <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full border border-warning-border bg-warning-subtle px-2 py-1 text-2xs font-semibold text-warning shadow-xs">
                                    <x-jaunt.icon name="alert-triangle" size="xs" />
                                    Rights expired
                                </span>
                            @endif
                        </div>

                        <div class="min-h-[102px] p-3.5">
                            <span class="block truncate text-sm font-semibold text-primary" title="{{ $asset->displayName() }}">{{ $asset->displayName() }}</span>
                            <span class="mt-1 flex min-w-0 items-center gap-1.5 overflow-hidden whitespace-nowrap text-2xs text-tertiary">
                                <span>{{ $assetSize }}</span>
                                @if($asset->dimensions())
                                    <span aria-hidden="true">·</span>
                                    <span class="truncate">{{ str_replace(' x ', ' × ', $asset->dimensions()) }}</span>
                                @else
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $assetExtension ?: 'File' }}</span>
                                @endif
                            </span>

                            @if($asset->tags->isNotEmpty())
                                <span class="mt-3 flex min-w-0 items-center gap-1.5">
                                    <span class="max-w-full truncate rounded-full bg-sunken px-2 py-1 text-2xs font-medium text-secondary">{{ $asset->tags->first()->name }}</span>
                                    @if($remainingTagCount > 0)
                                        <span class="shrink-0 text-2xs text-tertiary">+{{ $remainingTagCount }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-16 px-4">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                            <flux:icon.photo class="size-7 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <flux:heading size="md" class="mt-4">No assets in this folder</flux:heading>
                        <flux:text class="mt-2 text-center text-sm text-muted-foreground max-w-sm">Upload images, videos, or documents to use in your content.</flux:text>
                        <flux:button wire:click="$set('showUploadModal', true)" variant="primary" class="mt-6">
                            <flux:icon.arrow-up-tray class="size-4" />
                            Upload assets
                        </flux:button>
                    </div>
                @endforelse
            </div>

            @if($assets->hasPages())
                <div class="mt-8">
                    {{ $assets->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</main>

    </div>

{{-- Asset Detail Slide-over --}}
@if($showDetailSlideOver && $selectedAsset)
<div
    x-data="{ open: @entangle('showDetailSlideOver'), tab: 'basics' }"
    x-show="open"
    x-transition:enter="transition ease-out duration-base"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-x-0 bottom-0 top-[var(--topbar-h)] z-50"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" wire:click="closeAssetDetail"></div>

    {{-- Panel --}}
    <div
        class="cms-drawer cms-drawer--overlay"
        x-transition:enter="transition ease-out duration-base"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
    >
        <div class="cms-drawer-header">
            <div class="min-w-0">
                <h2 class="cms-drawer-title truncate">{{ $selectedAsset->displayName() }}</h2>
                <p class="cms-drawer-subtitle">{{ $selectedAsset->filename }}</p>
            </div>
            <button type="button" wire:click="closeAssetDetail" class="cms-iconbtn text-tertiary hover:bg-hover hover:text-primary" aria-label="Close asset details">
                <x-jaunt.icon name="x" size="sm" />
            </button>
        </div>

        <div class="cms-drawer-tabs px-3 pt-2" role="tablist" aria-label="Asset details sections" data-cms-tabs>
            @foreach(['basics' => 'Basics', 'rights' => 'Rights', 'usage' => 'Usage'] as $assetTab => $assetTabLabel)
                <button type="button" id="asset-tab-{{ $assetTab }}" x-on:click="tab = '{{ $assetTab }}'" class="cms-tab flex-1" role="tab" x-bind:aria-selected="tab === '{{ $assetTab }}'" aria-controls="asset-panel-{{ $assetTab }}" x-bind:tabindex="tab === '{{ $assetTab }}' ? 0 : -1">
                    {{ $assetTabLabel }}
                    @if($assetTab === 'usage')
                        <span class="ml-1 text-2xs text-tertiary">{{ $selectedAssetUsage->count() }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="cms-drawer-body">
            <div id="asset-panel-basics" x-show="tab === 'basics'" class="space-y-5" role="tabpanel" aria-labelledby="asset-tab-basics">
            {{-- Preview --}}
            <div class="aspect-[4/3] max-h-64 overflow-hidden rounded-lg bg-sunken">
                @if($selectedAsset->isImage())
                    <div
                        class="relative h-full w-full cursor-crosshair"
                        x-on:click="
                            const rect = $el.getBoundingClientRect();
                            const x = ((($event.clientX - rect.left) / rect.width) * 100);
                            const y = ((($event.clientY - rect.top) / rect.height) * 100);
                            $wire.call('setFocalPoint', x, y);
                        "
                    >
                        <img
                            src="{{ $selectedAsset->url() }}"
                            alt="{{ $selectedAsset->displayName() }}"
                            class="h-full w-full object-cover"
                            style="object-position: {{ $editFocalX }}% {{ $editFocalY }}%;"
                        />
                        <div class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $editFocalX }}%; top: {{ $editFocalY }}%;">
                            <div class="h-4 w-4 rounded-full border-2 border-white bg-accent shadow"></div>
                        </div>
                    </div>
                @elseif($selectedAsset->isVideo())
                    <video src="{{ $selectedAsset->url() }}" controls class="h-full w-full object-contain"></video>
                @else
                    <div class="flex h-full w-full items-center justify-center text-tertiary">
                        <flux:icon.document class="size-20" />
                    </div>
                @endif
            </div>

            @if($selectedAsset->isImage())
            <flux:field>
                <flux:label>Focal Point</flux:label>
                <flux:description>Click the image preview to set the image focus used by blocks on the website.</flux:description>
                <div class="mt-2 text-xs text-tertiary">X: {{ number_format($editFocalX, 1) }}% · Y: {{ number_format($editFocalY, 1) }}%</div>
            </flux:field>
            @endif

            {{-- Display Name --}}
            <flux:field>
                <flux:label>Display Name</flux:label>
                <flux:input wire:model="editDisplayName" placeholder="Custom name for this asset" />
                <flux:error name="editDisplayName" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="editDescription" rows="3" placeholder="Internal notes, campaign context, or usage guidance" />
                <flux:error name="editDescription" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Alt Text</flux:label>
                    <flux:input wire:model="editAlt" placeholder="Describe the asset" />
                    <flux:error name="editAlt" />
                </flux:field>

                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="editTitle" placeholder="Optional public title" />
                    <flux:error name="editTitle" />
                </flux:field>
            </div>

            {{-- Tags --}}
            <flux:field>
                <flux:label>Tags</flux:label>
                <flux:input wire:model="editTags" placeholder="tag1, tag2, tag3" />
                <flux:description>Comma-separated tags for organizing</flux:description>
            </flux:field>

            {{-- Folder --}}
            <flux:field>
                <flux:label>Folder</flux:label>
                <flux:select wire:model="editFolderId">
                    <option value="">Root (no folder)</option>
                    @foreach($allFolders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            {{-- Link --}}
            <flux:field>
                <flux:label>Asset URL</flux:label>
                <div class="flex gap-2" x-data="{ copied: false, url: {{ \Illuminate\Support\Js::from($selectedAsset->relativeUrl()) }} }">
                    <flux:input value="{{ $selectedAsset->relativeUrl() }}" readonly class="font-mono text-sm" />
                    <flux:button type="button" x-on:click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" variant="ghost" size="sm" square x-bind:aria-label="copied ? 'Link copied' : 'Copy asset link'" x-bind:title="copied ? 'Copied' : 'Copy asset link'">
                        <flux:icon.clipboard class="size-5" x-show="!copied" />
                        <flux:icon.check class="size-5 text-success" x-show="copied" x-cloak />
                    </flux:button>
                </div>
            </flux:field>
            </div>

            <div id="asset-panel-rights" x-show="tab === 'rights'" x-cloak class="space-y-5" role="tabpanel" aria-labelledby="asset-tab-rights">
                <div class="rounded-md bg-warning-subtle p-3 text-sm text-warning">
                    Keep attribution and expiration current so editors can reuse this asset confidently.
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Credit</flux:label>
                    <flux:input wire:model="editCredit" placeholder="Photographer or source" />
                    <flux:error name="editCredit" />
                </flux:field>

                <flux:field>
                    <flux:label>License</flux:label>
                    <flux:input wire:model="editLicense" placeholder="Owned, stock, CC BY..." />
                    <flux:error name="editLicense" />
                </flux:field>
                </div>

            <flux:field>
                <flux:label>Copyright</flux:label>
                <flux:input wire:model="editCopyright" placeholder="Copyright owner or rights note" />
                <flux:error name="editCopyright" />
            </flux:field>

            <flux:field>
                <flux:label>Source URL</flux:label>
                <flux:input wire:model="editSourceUrl" placeholder="https://..." />
                <flux:error name="editSourceUrl" />
            </flux:field>

            <flux:field>
                <flux:label>Rights Expiration</flux:label>
                <flux:input type="date" wire:model="editExpiresAt" />
                <flux:error name="editExpiresAt" />
            </flux:field>

            </div>

            <div id="asset-panel-usage" x-show="tab === 'usage'" x-cloak class="space-y-5" role="tabpanel" aria-labelledby="asset-tab-usage">
            {{-- Meta --}}
            <dl class="divide-y divide-subtle rounded-md border border-subtle text-sm">
                <div class="flex justify-between gap-4 px-3 py-2"><dt class="text-tertiary">Filename</dt><dd class="truncate text-primary">{{ $selectedAsset->filename }}</dd></div>
                <div class="flex justify-between gap-4 px-3 py-2"><dt class="text-tertiary">Size</dt><dd class="text-primary">{{ $selectedAsset->size >= 1048576 ? number_format($selectedAsset->size / 1048576, 1) . ' MB' : number_format($selectedAsset->size / 1024, 1) . ' KB' }}</dd></div>
                @if($selectedAsset->dimensions())
                    <div class="flex justify-between gap-4 px-3 py-2"><dt class="text-tertiary">Dimensions</dt><dd class="text-primary">{{ $selectedAsset->dimensions() }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 px-3 py-2"><dt class="text-tertiary">Type</dt><dd class="text-primary">{{ $selectedAsset->mime }}</dd></div>
                @if($selectedAsset->checksum)
                    <div class="px-3 py-2"><dt class="text-tertiary">Checksum</dt><dd class="mt-1 break-all font-mono text-2xs text-secondary">{{ $selectedAsset->checksum }}</dd></div>
                @endif
            </dl>

            <div class="rounded-md border border-subtle">
                <div class="border-b border-subtle px-3 py-2 text-sm font-medium text-primary">
                    Used in {{ $selectedAssetUsage->count() }} {{ \Illuminate\Support\Str::plural('place', $selectedAssetUsage->count()) }}
                </div>
                <div class="max-h-48 divide-y divide-subtle overflow-y-auto">
                    @forelse($selectedAssetUsage as $usage)
                        <a href="{{ route('admin.content.edit', $usage['content']) }}" wire:navigate class="block px-3 py-2 hover:bg-hover">
                            <div class="text-sm font-medium text-primary">{{ $usage['content']->name }}</div>
                            <div class="text-xs text-tertiary">
                                {{ $usage['block'] ? 'Block: '.$usage['block']->type : 'Content meta' }} / {{ $usage['location'] }}
                            </div>
                        </a>
                    @empty
                        <div class="px-3 py-4 text-sm text-tertiary">No current content references found.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-md border border-danger-border bg-danger-subtle p-4">
                <h3 class="text-sm font-semibold text-danger">Danger zone</h3>
                <p class="mt-1 text-xs text-secondary">Deletion permanently removes the file and is available only when it has no content references.</p>
                <flux:button wire:click="deleteAsset({{ $selectedAsset->id }})" wire:confirm="Delete this asset? The file will be permanently removed." variant="danger" size="sm" class="mt-3">
                    <flux:icon.trash class="size-4" />
                    Delete asset
                </flux:button>
                <flux:error name="deleteAsset" />
            </div>
            </div>
        </div>

        <div class="cms-drawer-footer">
                <flux:button wire:click="closeAssetDetail" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveAssetDetails" variant="primary">Save</flux:button>
        </div>
    </div>
</div>
@endif

{{-- Upload Modal --}}
<flux:modal wire:model="showUploadModal">
    <flux:heading size="lg">Upload Assets</flux:heading>
    <form wire:submit="uploadAssets" class="mt-4 space-y-4">
        <flux:field>
            <flux:label>Files</flux:label>
            <input type="file" wire:model="uploadFiles" multiple class="block w-full text-sm text-zinc-500 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:text-zinc-800 dark:file:bg-zinc-700 dark:file:text-zinc-200 file:cursor-pointer">
            <flux:error name="uploadFiles.*" />
            <flux:description>Photos, videos, and documents. Max 50MB per file. Wait for files to finish uploading before clicking Upload.</flux:description>
        </flux:field>

        @if($uploadFiles)
            <div class="space-y-2 max-h-32 overflow-y-auto">
                @foreach($uploadFiles as $file)
                    <flux:text class="text-sm">{{ $file->getClientOriginalName() }}</flux:text>
                @endforeach
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <flux:button type="button" wire:click="$set('showUploadModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="uploadFiles,uploadAssets">
                <span wire:loading.remove wire:target="uploadFiles,uploadAssets">Upload</span>
                <span wire:loading wire:target="uploadFiles,uploadAssets">Uploading...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

{{-- New Folder Modal --}}
<flux:modal wire:model="showNewFolderModal">
    <flux:heading size="lg">New Folder</flux:heading>
    <form wire:submit="createFolder" class="mt-4 space-y-4">
        <flux:field>
            <flux:label>Folder Name</flux:label>
            <flux:input wire:model="newFolderName" placeholder="My folder" />
            <flux:error name="newFolderName" />
        </flux:field>
        <div class="flex justify-end gap-3">
            <flux:button type="button" wire:click="$set('showNewFolderModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create</flux:button>
        </div>
    </form>
</flux:modal>
</div>
