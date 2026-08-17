<flux:modal wire:model="show">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Select asset</flux:heading>
        <div class="cms-seg" role="group" aria-label="Asset view">
            <button type="button" wire:click="setViewMode('grid')" class="cms-seg-btn !w-[30px] !px-0" aria-label="Grid view" aria-pressed="{{ $viewMode === 'grid' ? 'true' : 'false' }}">
                <flux:icon.squares-2x2 class="size-4" />
            </button>
            <button type="button" wire:click="setViewMode('list')" class="cms-seg-btn !w-[30px] !px-0" aria-label="List view" aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}">
                <flux:icon.list-bullet class="size-4" />
            </button>
        </div>
    </div>

    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search assets..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="typeFilter" class="max-w-40">
            <option value="images">Images</option>
            <option value="videos">Videos</option>
            <option value="documents">Documents</option>
            <option value="all">All types</option>
        </flux:select>
    </div>

    <div class="flex gap-4 min-h-[300px]">
        {{-- Folders sidebar --}}
        <div class="w-48 shrink-0 border-r border-zinc-200 dark:border-zinc-700 pr-4">
            <button type="button" wire:click="selectFolder(null)" class="cms-choice-row" aria-current="{{ $folderId === null ? 'true' : 'false' }}">
                All
            </button>
            @foreach($folders as $folder)
                <button type="button" wire:click="selectFolder({{ $folder->id }})" class="cms-choice-row" aria-current="{{ $folderId == $folder->id ? 'true' : 'false' }}">
                    <flux:icon.folder class="size-4" />
                    {{ $folder->name }}
                </button>
            @endforeach
        </div>

        {{-- Assets grid/list --}}
        <div class="flex-1 overflow-y-auto max-h-[400px]">
            @if($viewMode === 'grid')
            <div class="grid grid-cols-3 gap-3">
                @foreach($assets as $asset)
                    <button type="button" wire:click="selectAsset({{ $asset->id }})" class="group overflow-hidden rounded-xl bg-card text-left shadow-xs outline outline-1 -outline-offset-1 outline-[color:var(--border-subtle)] transition-[box-shadow,transform] duration-fast ease-standard hover:-translate-y-px hover:shadow-md focus-visible:outline-none focus-visible:shadow-ring" aria-label="Select {{ $asset->displayName() }}">
                        @if($asset->isImage())
                            <img src="{{ $asset->thumbnailRelativeUrl() }}" alt="{{ $asset->displayName() }}" class="aspect-[4/3] w-full object-cover transition-transform duration-slow ease-standard group-hover:scale-[1.015]" loading="lazy" />
                        @else
                            <div class="flex aspect-[4/3] w-full items-center justify-center bg-sunken text-tertiary">
                                <span class="grid size-11 place-items-center rounded-lg border border-subtle bg-card shadow-xs">
                                    <flux:icon.document class="size-6" />
                                </span>
                            </div>
                        @endif
                        <div class="truncate px-2.5 py-2.5 text-xs font-medium text-primary" title="{{ $asset->displayName() }}">{{ $asset->displayName() }}</div>
                    </button>
                @endforeach
            </div>
            @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($assets as $asset)
                    <button type="button" wire:click="selectAsset({{ $asset->id }})" class="cms-choice-row !min-h-0 !rounded-none !p-3">
                        @if($asset->isImage())
                            <img src="{{ $asset->thumbnailRelativeUrl() }}" alt="" class="w-12 h-12 object-cover rounded" loading="lazy" />
                        @else
                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center">
                                <flux:icon.document class="size-6 text-muted-foreground" />
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">{{ $asset->displayName() }}</div>
                            <div class="text-xs text-muted-foreground">{{ $asset->mime }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
            @endif

            @if($assets->isEmpty())
                <div class="py-12 text-center text-muted-foreground text-sm">
                    No assets found. Upload some in the Assets section.
                </div>
            @endif
        </div>
    </div>
</flux:modal>
