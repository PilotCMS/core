<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Block Types" subtitle="Reusable content components for your pages." top="0px" as="header" scroll-target="#blocks-list-scroll" aria-label="Page header">
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <button type="button" wire:click="$set('showNewFolderModal', true)" class="cms-btn cms-btn-secondary">
                <x-jaunt.icon name="folder-plus" size="sm" />
                New folder
            </button>
            <a href="{{ route('admin.blocks.create') }}" wire:navigate class="cms-btn cms-btn-primary">
                <x-jaunt.icon name="plus" size="sm" />
                New block type
            </a>
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="flex flex-1 min-h-0">

    {{-- Main content: flex-1, fills space left of aside --}}
    <main id="blocks-list-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">

        {{-- Table card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Filter bar --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Search --}}
                    <div class="relative mr-3">
                        <x-jaunt.icon name="search" size="xs" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or key..." class="pl-8 pr-3 py-1.5 text-sm border border-slate-200 rounded-md bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-48 placeholder-slate-400" />
                    </div>

                    {{-- Folder filter tabs --}}
                    <div class="cms-seg" role="group" aria-label="Filter by folder">
                        <button type="button" wire:click="setFolderFilter('all')" class="cms-seg-btn" aria-pressed="{{ $folderFilter === 'all' ? 'true' : 'false' }}">All</button>
                        <button type="button" wire:click="setFolderFilter('none')" class="cms-seg-btn" aria-pressed="{{ $folderFilter === 'none' ? 'true' : 'false' }}">No folder</button>
                        @foreach($folders as $folder)
                            <button type="button" wire:click="setFolderFilter('{{ $folder->id }}')" class="cms-seg-btn" aria-pressed="{{ $folderFilter == $folder->id ? 'true' : 'false' }}">{{ $folder->name }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="uppercase tracking-wide font-semibold">Sort by</span>
                    <select wire:model.live="sortBy" class="text-sm text-slate-700 border border-slate-200 rounded-md px-2 py-1 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="name">Name</option>
                        <option value="key">Key</option>
                        <option value="updated_at">Last Updated</option>
                        <option value="created_at">Created</option>
                    </select>
                    <select wire:model.live="sortDir" class="text-sm text-slate-700 border border-slate-200 rounded-md px-2 py-1 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>
            </div>

            {{-- Table header --}}
            <div class="grid grid-cols-[auto_1fr_120px_100px_100px_140px_120px] items-center gap-4 px-5 py-3 border-b border-slate-100 bg-slate-50/50 text-xs font-semibold text-slate-400 uppercase tracking-wider dark:border-strong dark:bg-hover dark:text-tertiary">
                <div class="w-5"><input type="checkbox" class="rounded border-slate-300 text-blue-500 focus:ring-blue-500" disabled /></div>
                <div>Name</div>
                <div>Folder</div>
                <div>Fields</div>
                <div>Type</div>
                <div>Updated</div>
                <div class="text-right">Actions</div>
            </div>

            {{-- Table rows --}}
            @forelse($blockTypes as $blockType)
                <div class="grid grid-cols-[auto_1fr_120px_100px_100px_140px_120px] items-center gap-4 px-5 py-4 border-b border-subtle transition-colors hover:bg-slate-50/60 dark:border-white/10 dark:hover:bg-white/[0.04] group" wire:key="block-type-{{ $blockType->id }}">
                    <div class="w-5"><input type="checkbox" class="rounded border-slate-300 text-blue-500 focus:ring-blue-500" /></div>

                    {{-- Name + key --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                            <x-jaunt.icon name="layout-grid" size="xs" />
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('admin.blocks.edit', $blockType) }}" wire:navigate class="block truncate text-sm font-semibold text-primary transition-colors hover:text-secondary">{{ $blockType->name }}</a>
                            <span class="text-xs text-slate-400 truncate block font-mono">{{ $blockType->key }}</span>
                        </div>
                    </div>

                    {{-- Folder --}}
                    <div>
                        <select wire:change="setFolder({{ $blockType->id }}, $event.target.value)" class="w-full text-sm border border-slate-200 rounded-md px-2 py-1.5 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">None</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}" {{ $blockType->folder_id == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Field count --}}
                    <div class="text-sm text-slate-700">
                        {{ count($blockType->schema['fields'] ?? []) }} field(s)
                    </div>

                    {{-- Type badge --}}
                    <div>
                        @if($blockType->is_global)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-600">Global</span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </div>

                    {{-- Updated --}}
                    <div class="text-sm text-slate-600">
                        {{ $blockType->updated_at?->diffForHumans() ?? '—' }}
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('admin.blocks.edit', $blockType) }}" wire:navigate class="cms-iconbtn" aria-label="Edit block type" title="Edit block type">
                            <x-jaunt.icon name="pencil" size="sm" />
                        </a>
                        <button type="button" wire:click="deleteBlockType({{ $blockType->id }})" wire:confirm="Are you sure you want to delete this block type? This cannot be undone." class="cms-iconbtn cms-iconbtn-danger" aria-label="Delete block type" title="Delete block type">
                            <x-jaunt.icon name="trash-2" size="sm" />
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-20 px-4">
                    <div class="w-14 h-14 rounded-full bg-violet-100 flex items-center justify-center mb-4">
                        <x-jaunt.icon name="layout-grid" size="lg" class="text-violet-500" />
                    </div>
                    <p class="text-sm font-medium text-slate-700">No block types yet</p>
                    <p class="text-xs text-slate-400 mt-1">Block types define reusable content structures. Create your first one to build pages with custom components.</p>
                    <a href="{{ route('admin.blocks.create') }}" wire:navigate class="mt-5 inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-on-accent shadow-sm hover:bg-accent-hover transition-colors">
                        <x-jaunt.icon name="plus" size="sm" />
                        Create block type
                    </a>
                </div>
            @endforelse
        </div>

        </div>{{-- /max-w centered --}}
    </main>

    </div>

    {{-- New folder modal --}}
    @if($showNewFolderModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-500/30" wire:click="$set('showNewFolderModal', false)"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">New folder</h3>
            <form wire:submit="createFolder" class="space-y-4">
                <div>
                    <label for="new-folder-name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input type="text" id="new-folder-name" wire:model="newFolderName" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="e.g. Layout components" />
                    @error('newFolderName')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showNewFolderModal', false)" class="cms-btn cms-btn-secondary">Cancel</button>
                    <button type="submit" class="cms-btn cms-btn-primary">Create folder</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
