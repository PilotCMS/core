<div x-data="{ activityOpen: false }" class="cms-shell relative flex h-full w-full min-w-0 flex-col">
    <x-jaunt.shell.dynamic-header
        title="Content"
        subtitle="Pages, folders and global content for your site."
        top="0px"
        as="header"
        scroll-target="#content-list-scroll"
        aria-label="Page header"
    >
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <button type="button" x-on:click="activityOpen = true" class="cms-btn cms-btn-secondary" aria-haspopup="dialog" x-bind:aria-expanded="activityOpen">
                <x-jaunt.icon name="activity" size="sm" />
                Activity
            </button>
            @can('create content')
                <a href="{{ route('admin.content.create', ['type' => 'folder', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="cms-btn cms-btn-secondary">
                    <x-jaunt.icon name="folder-plus" size="sm" />
                    New folder
                </a>
                <a href="{{ route('admin.content.create', ['type' => 'page', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="cms-btn cms-btn-primary">
                    <x-jaunt.icon name="plus" size="sm" />
                    New page
                </a>
            @endcan
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="grid min-h-0 flex-1 grid-cols-1">
        <main id="content-list-scroll" class="min-w-0 overflow-y-auto">
            <div class="flex min-h-full flex-col gap-6 px-[var(--pad-view)] pb-10 pt-1">
                <div>
                    <div class="cms-toolbar">
                        <label class="cms-input w-52">
                            <x-jaunt.icon name="search" size="sm" class="text-tertiary" />
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search content" />
                        </label>

                        <div class="cms-seg" role="group" aria-label="Content type filter">
                            <button type="button" wire:click="setTypeFilter('all')" class="cms-seg-btn" aria-pressed="{{ $typeFilter === 'all' ? 'true' : 'false' }}">All</button>
                            <button type="button" wire:click="setTypeFilter('page')" class="cms-seg-btn" aria-pressed="{{ $typeFilter === 'page' ? 'true' : 'false' }}">Pages</button>
                            <button type="button" wire:click="setTypeFilter('folder')" class="cms-seg-btn" aria-pressed="{{ $typeFilter === 'folder' ? 'true' : 'false' }}">Folders</button>
                            <button type="button" wire:click="setTypeFilter('global')" class="cms-seg-btn" aria-pressed="{{ $typeFilter === 'global' ? 'true' : 'false' }}">Global</button>
                        </div>

                        <span class="flex-1"></span>

                        <div class="flex items-center gap-2">
                            <span class="text-2xs font-semibold uppercase tracking-[0.06em] text-tertiary">Sort</span>
                            <div class="relative">
                                <select wire:model.live="sortBy" class="cms-select">
                                    <option value="updated_at">Last updated</option>
                                    <option value="name">Name</option>
                                    <option value="created_at">Created</option>
                                    <option value="status">Status</option>
                                </select>
                                <x-jaunt.icon name="chevron-down" size="sm" class="pointer-events-none absolute right-2 top-1.5 text-tertiary" />
                            </div>
                        </div>
                    </div>

                    <div class="cms-panel min-w-[720px]">
                        <div class="cms-table-head cms-content-table-grid">
                            <div>Name</div>
                            <div>Type</div>
                            <div>Updated</div>
                            <div>Status</div>
                        </div>

                        @forelse($this->contentTree as $row)
                            @php $content = $row->content; $depth = $row->depth; @endphp
                            <div class="cms-table-row cms-content-table-grid group" wire:key="content-{{ $content->id }}">
                                <div class="flex min-w-0 items-center gap-2" style="padding-left: {{ $depth * 20 }}px;">
                                    @if($content->isFolder())
                                        <button type="button" wire:click="toggleFolder({{ $content->id }})" class="cms-iconbtn cms-iconbtn-sm" aria-label="{{ $this->isFolderExpanded($content->id) ? 'Collapse' : 'Expand' }}">
                                            <x-jaunt.icon :name="$this->isFolderExpanded($content->id) ? 'chevron-down' : 'chevron-right'" size="xs" />
                                        </button>
                                        <span class="cms-tile cms-tile-info"><x-jaunt.icon name="folder" size="sm" /></span>
                                    @else
                                        <span class="w-5 shrink-0" aria-hidden="true"></span>
                                        <span class="cms-tile"><x-jaunt.icon name="file-text" size="sm" /></span>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        @if($content->isFolder())
                                            <button type="button" wire:click="toggleFolder({{ $content->id }})" class="block w-full truncate text-left text-sm font-medium text-primary hover:text-accent-text">{{ $content->name }}</button>
                                        @else
                                            <a href="{{ route('admin.content.editor', $content) }}" wire:navigate class="block truncate text-sm font-medium text-primary hover:text-accent-text">{{ $content->name }}</a>
                                        @endif
                                        <span class="block truncate font-mono text-2xs text-tertiary">/{{ $content->slug }}</span>
                                    </div>
                                </div>

                                <div>
                                    @if($content->isFolder())
                                        <span class="cms-badge cms-badge-info">Folder</span>
                                    @elseif($content->type === 'global')
                                        <span class="cms-badge cms-badge-accent">Global</span>
                                    @else
                                        <span class="cms-badge cms-badge-accent">Page</span>
                                    @endif
                                </div>

                                <div>
                                    <div class="text-sm text-secondary">{{ $content->updated_at->diffForHumans() }}</div>
                                    <div class="text-2xs text-tertiary">by {{ $content->updater?->name ?? $content->creator?->name ?? 'System' }}</div>
                                </div>

                                <div>
                                    <span class="cms-status">
                                        @if($content->status === 'published')
                                            <span class="cms-status-dot cms-status-dot-success"></span>
                                            Published
                                        @elseif($content->status === 'draft')
                                            <span class="cms-status-dot"></span>
                                            Draft
                                        @else
                                            <span class="cms-status-dot cms-status-dot-warning"></span>
                                            {{ ucfirst($content->status) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center px-4 py-20 text-center">
                                <div class="cms-tile !h-14 !w-14 !rounded-lg"><x-jaunt.icon name="folder-open" size="lg" /></div>
                                <p class="mt-4 text-sm font-medium text-primary">No content found</p>
                                <p class="cms-subtitle">Get started by creating a folder or page.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 text-2xs text-tertiary">
                    <x-jaunt.icon name="info" size="sm" />
                    Drag folders to reorder your content structure.
                </div>
            </div>
        </main>

        <div x-show="activityOpen" x-cloak x-transition.opacity class="absolute inset-0 z-40 bg-black/20" x-on:click="activityOpen = false" aria-hidden="true"></div>
        <aside
            x-show="activityOpen"
            x-cloak
            x-transition:enter="transition duration-base ease-standard"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-fast ease-standard"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            x-on:keydown.escape.window="activityOpen = false"
            class="cms-rail absolute inset-y-0 right-0 z-50 flex w-80 shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-label="Space activity"
        >
            <div class="cms-rail-head">
                <x-jaunt.icon name="activity" size="sm" class="text-tertiary" />
                <h2 class="cms-rail-title">Space activity</h2>
                <button type="button" x-on:click="activityOpen = false" class="cms-iconbtn ml-auto" aria-label="Close activity"><x-jaunt.icon name="x" size="sm" /></button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse($this->recentActivity as $activity)
                    <div class="cms-rail-item">
                        <span class="cms-avatar">
                            @if($activity->user)
                                {{ strtoupper(substr($activity->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $activity->user->name)[1] ?? '', 0, 1)) }}
                            @else
                                API
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm leading-snug text-secondary">
                                <strong class="font-semibold text-primary">{{ $activity->user?->name ?? 'System' }}</strong>
                                {{ $activity->action }}
                                @if($activity->subject)
                                    @php
                                        $subjectName = $activity->subject->name ?? $activity->subject->key ?? class_basename($activity->subject_type);
                                        $subjectRoute = null;
                                        if ($activity->subject instanceof \Pilot\Core\Models\Content && $activity->subject->isPage()) {
                                            $subjectRoute = route('admin.content.editor', $activity->subject);
                                        }
                                    @endphp
                                    @if($subjectRoute)
                                        <a href="{{ $subjectRoute }}" wire:navigate class="font-medium text-accent-text">{{ $subjectName }}</a>.
                                    @else
                                        <span class="font-medium text-accent-text">{{ $subjectName }}</span>.
                                    @endif
                                @endif
                            </p>
                            <time class="mt-1 block text-2xs text-tertiary">{{ $activity->created_at->diffForHumans() }}</time>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <x-jaunt.icon name="history" size="lg" class="text-tertiary" />
                        <p class="mt-2 text-xs text-tertiary">No recent activity</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
