<div class="cms-shell h-screen w-full relative overflow-hidden selection:bg-accent-subtle selection:text-accent-text">
<div
    x-data="{
        rightPanelTab: @entangle('rightPanelTab'),
        savedJustNow: @entangle('savedJustNow'),
        blockLibraryOpen: @entangle('blockLibraryOpen'),
        selectedBlockId: @entangle('selectedBlockId'),
        canvasMode: 'preview',
        previewDevice: 'desktop',
        previewTargetOrigins: @js($this->previewTargetOrigins),
        previewFrameSrc: @js($this->previewFrameUrl),
        previewRefreshTimer: null,
        pendingPreviewUrl: null,
        pendingPreviewScroll: null,
        pendingPreviewScrollBlockId: null,
        saveState: @entangle('saveState'),
        conflictMessage: @entangle('conflictMessage'),
        drawerOpen: @entangle('drawerOpen').live,
        leftSidebarCollapsed: @entangle('leftSidebarCollapsed').live,
        contentSearch: '',
        expandedFolderIds: @if($content->parent_id) [{{ (int) $content->parent_id }}] @else [] @endif,
        compactWorkspace: false,
        get inspectorOpen() {
            return this.drawerOpen;
        },
        set inspectorOpen(value) {
            this.drawerOpen = value;
        },
        get leftCollapsed() {
            return this.leftSidebarCollapsed;
        },
        set leftCollapsed(value) {
            this.leftSidebarCollapsed = value;
        },
        isFolderExpanded(folderId) {
            return this.contentSearch.trim() !== '' || this.expandedFolderIds.includes(Number(folderId));
        },
        toggleFolder(folderId) {
            const id = Number(folderId);

            if (this.expandedFolderIds.includes(id)) {
                this.expandedFolderIds = this.expandedFolderIds.filter((expandedId) => expandedId !== id);
            } else {
                this.expandedFolderIds.push(id);
            }
        },
        contentNameMatches(name) {
            return String(name).toLocaleLowerCase().includes(this.contentSearch.trim().toLocaleLowerCase());
        },
        folderMatches(name, childNames) {
            return this.contentSearch.trim() === ''
                || this.contentNameMatches(name)
                || childNames.some((childName) => this.contentNameMatches(childName));
        },
        applyWorkspaceWidth() {
            this.compactWorkspace = window.matchMedia('(max-width: 1499px)').matches;

            if (this.compactWorkspace && this.drawerOpen && ! this.leftSidebarCollapsed) {
                this.leftSidebarCollapsed = true;
            }
        },
        openPages() {
            this.leftSidebarCollapsed = false;

            if (this.compactWorkspace) {
                this.drawerOpen = false;
            }
        },
        openInspector() {
            this.drawerOpen = true;

            if (this.compactWorkspace) {
                this.leftSidebarCollapsed = true;
            }
        },
        previewWidth() {
            const desktopWidth = ! this.drawerOpen && this.leftSidebarCollapsed
                ? 'min(100%, 1600px)'
                : 'min(100%, 1280px)';

            return {
                desktop: desktopWidth,
                tablet: '768px',
                mobile: '390px'
            }[this.previewDevice];
        },
        previewFrameOrigin() {
            try {
                return new URL(this.$refs.previewFrame?.src || window.location.href).origin;
            } catch (error) {
                return '*';
            }
        },
        postToPreview(message) {
            const frame = this.$refs.previewFrame;

            if (! frame?.contentWindow) {
                return;
            }

            frame.contentWindow.postMessage(message, this.previewFrameOrigin());
        },
        applyPreviewSelectionDirectly() {
            const frame = this.$refs.previewFrame;

            try {
                const doc = frame?.contentDocument;

                if (! doc) {
                    return;
                }

                doc.querySelectorAll('[data-pilot-selected=true]').forEach((element) => {
                    element.removeAttribute('data-pilot-selected');
                });

                if (! this.selectedBlockId) {
                    return;
                }

                const selected = doc.querySelector(`[data-pilot-editable=block][data-pilot-block-id='${Number(this.selectedBlockId)}']`);

                if (selected) {
                    selected.setAttribute('data-pilot-selected', 'true');
                }
            } catch (error) {
                // Cross-origin preview targets are handled by the postMessage bridge.
            }
        },
        syncPreviewEditorMode() {
            this.postToPreview({
                type: 'pilot-preview-editor-mode',
                inContextPanel: false,
            });
        },
        syncPreviewWorkspace() {
            this.syncPreviewEditorMode();
            window.setTimeout(() => this.syncPreviewEditorMode(), 100);
            window.setTimeout(() => this.syncPreviewEditorMode(), 500);
        },
        postPreviewSelection() {
            this.postToPreview({
                type: 'pilot-preview-sync-selected-block',
                blockId: this.selectedBlockId ? Number(this.selectedBlockId) : null,
            });
            this.applyPreviewSelectionDirectly();
        },
        syncPreviewSelection() {
            this.syncPreviewWorkspace();
            this.postPreviewSelection();
            window.setTimeout(() => this.postPreviewSelection(), 100);
            window.setTimeout(() => this.postPreviewSelection(), 500);
        },
        refreshPreviewFrame(url) {
            if (! url || this.previewFrameSrc === url) {
                return;
            }

            this.pendingPreviewScrollBlockId = this.selectedBlockId ? Number(this.selectedBlockId) : null;
            this.capturePreviewScroll();
            this.previewFrameSrc = url;
        },
        queuePreviewFrameRefresh(url) {
            if (! url) {
                return;
            }

            this.pendingPreviewUrl = url;
            clearTimeout(this.previewRefreshTimer);

            if (this.canvasMode !== 'preview') {
                return;
            }

            this.previewRefreshTimer = setTimeout(() => {
                this.refreshPreviewFrame(this.pendingPreviewUrl);
                this.pendingPreviewUrl = null;
            }, 700);
        },
        previewScrollPosition() {
            const frame = this.$refs.previewFrame;

            try {
                const frameWindow = frame?.contentWindow;
                const doc = frame?.contentDocument;

                if (! frameWindow || ! doc) {
                    return null;
                }

                return {
                    x: frameWindow.scrollX ?? doc.documentElement?.scrollLeft ?? doc.body?.scrollLeft ?? 0,
                    y: frameWindow.scrollY ?? doc.documentElement?.scrollTop ?? doc.body?.scrollTop ?? 0,
                };
            } catch (error) {
                return null;
            }
        },
        capturePreviewScroll() {
            this.pendingPreviewScroll = this.previewScrollPosition();
        },
        scrollPreviewBlockIntoView(blockId = this.selectedBlockId) {
            const targetBlockId = blockId ? Number(blockId) : null;

            if (! targetBlockId) {
                return false;
            }

            this.postToPreview({
                type: 'pilot-preview-scroll-to-block',
                blockId: targetBlockId,
            });

            try {
                const doc = this.$refs.previewFrame?.contentDocument;
                const block = doc?.querySelector(`[data-pilot-editable=block][data-pilot-block-id='${targetBlockId}']`);

                if (! block) {
                    return false;
                }

                block.scrollIntoView({ block: 'center', inline: 'nearest' });

                return true;
            } catch (error) {
                return false;
            }
        },
        restorePreviewScroll() {
            const blockId = this.pendingPreviewScrollBlockId;

            this.pendingPreviewScrollBlockId = null;

            if (this.scrollPreviewBlockIntoView(blockId)) {
                this.pendingPreviewScroll = null;

                return;
            }

            const position = this.pendingPreviewScroll;

            if (! position) {
                return;
            }

            this.pendingPreviewScroll = null;

            try {
                this.$refs.previewFrame?.contentWindow?.scrollTo(position.x, position.y);
            } catch (error) {
                // Cross-origin preview targets cannot be scrolled directly.
            }
        },
        saveLabel() {
            if (this.conflictMessage) {
                return 'Changed elsewhere';
            }

            if (this.saveState === 'saving') {
                return 'Saving...';
            }

            return this.savedJustNow ? 'Saved just now' : 'Saved';
        },
        init() {
            this.applyWorkspaceWidth();
            window.addEventListener('resize', () => this.applyWorkspaceWidth());

            $wire.on('saved', () => {
                this.savedJustNow = true;
                setTimeout(() => { this.savedJustNow = false; }, 2000);
            });

            $wire.on('preview-frame-refresh', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;

                this.queuePreviewFrameRefresh(payload?.url);
            });

            $wire.on('preview-selection-sync', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;

                this.selectedBlockId = payload?.blockId ? Number(payload.blockId) : null;
                this.syncPreviewSelection();
                this.scrollPreviewBlockIntoView();
            });

            this.$watch('selectedBlockId', () => {
                if (this.selectedBlockId) {
                    this.openInspector();
                }

                this.$nextTick(() => {
                    this.syncPreviewSelection();
                    this.scrollPreviewBlockIntoView();
                });
            });

            this.$watch('canvasMode', (mode) => {
                if (mode === 'preview' && this.pendingPreviewUrl) {
                    this.queuePreviewFrameRefresh(this.pendingPreviewUrl);
                }
            });

            this.$nextTick(() => {
                this.syncPreviewSelection();
                this.scrollPreviewBlockIntoView();
            });

            window.addEventListener('message', (event) => {
                const allowedOrigins = [window.location.origin, ...this.previewTargetOrigins];

                if (! allowedOrigins.includes(event.origin)) {
                    return;
                }

                if (event.data?.type === 'pilot-preview-ready') {
                    this.syncPreviewSelection();
                    this.scrollPreviewBlockIntoView();
                }

                if (event.data?.type === 'pilot-preview-select-block' && event.data?.blockId) {
                    this.selectedBlockId = Number(event.data.blockId);
                    this.syncPreviewSelection();
                    $wire.call('setSelectedBlockFromPreview', Number(event.data.blockId));
                }

                if (event.data?.type === 'pilot-preview-block-action' && event.data?.blockId && event.data?.action) {
                    this.selectedBlockId = Number(event.data.blockId);
                    this.syncPreviewSelection();

                    const actions = {
                        'move-up': 'moveBlockUp',
                        'move-down': 'moveBlockDown',
                        duplicate: 'duplicateBlock',
                        delete: 'deleteBlock',
                    };

                    if (actions[event.data.action]) {
                        if (event.data.action === 'delete' && ! confirm('Delete this block?')) {
                            return;
                        }

                        $wire.call(actions[event.data.action], Number(event.data.blockId));
                    }
                }

                if (event.data?.type === 'pilot-in-context-field-updated' && event.data?.blockId && event.data?.fieldKey) {
                    $wire.call(
                        'updateBlock',
                        Number(event.data.blockId),
                        event.data.fieldKey,
                        event.data.value ?? ''
                    );
                }
            });

            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
                    e.preventDefault();
                    this.blockLibraryOpen = true;
                }

                if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                    e.preventDefault();
                    $wire.call('saveCheckpoint');
                }
            });
        }
    }"
    class="contents"
>
    <livewire:admin.content.content-sync-poller
        :content-id="$content->id"
        :key="'content-sync-poller-' . $content->id"
    />

    <header class="cms-editor-toolbar fixed top-0 z-[45] flex h-topbar items-center gap-3 border-b border-subtle bg-app px-3 transition-[right]" aria-label="Editor toolbar" style="left: var(--admin-nav-width); right: 0;">
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <a href="{{ route('admin.content.index') }}" wire:navigate class="cms-iconbtn text-tertiary hover:bg-hover hover:text-primary" title="Back to content" aria-label="Back to content">
                <x-jaunt.icon name="arrow-left" size="sm" />
            </a>
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-primary">{{ $content->name }}</div>
                <div class="flex items-center gap-1.5 text-2xs text-tertiary">
                    <span>{{ $content->space?->name ?? 'Space' }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $content->status === 'draft' ? 'Unpublished changes' : 'Published' }}</span>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <div class="cms-seg" role="group" aria-label="Editor view">
                <button type="button" x-on:click="canvasMode = 'compose'" x-bind:aria-pressed="canvasMode === 'compose'" class="cms-seg-btn">Compose</button>
                <button type="button" x-on:click="canvasMode = 'preview'" x-bind:aria-pressed="canvasMode === 'preview'" class="cms-seg-btn">Preview</button>
            </div>
            <div x-show="canvasMode === 'preview'" class="cms-seg hidden lg:flex" role="group" aria-label="Preview device">
                <button type="button" x-on:click="previewDevice = 'desktop'" x-bind:aria-pressed="previewDevice === 'desktop'" class="cms-seg-btn !w-[30px] !px-0" aria-label="Desktop preview"><x-jaunt.icon name="monitor" size="sm" /></button>
                <button type="button" x-on:click="previewDevice = 'tablet'" x-bind:aria-pressed="previewDevice === 'tablet'" class="cms-seg-btn !w-[30px] !px-0" aria-label="Tablet preview"><x-jaunt.icon name="tablet" size="sm" /></button>
                <button type="button" x-on:click="previewDevice = 'mobile'" x-bind:aria-pressed="previewDevice === 'mobile'" class="cms-seg-btn !w-[30px] !px-0" aria-label="Mobile preview"><x-jaunt.icon name="smartphone" size="sm" /></button>
            </div>
        </div>

        <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
            <div class="hidden items-center gap-1.5 text-xs font-medium text-tertiary xl:flex" x-bind:class="conflictMessage ? 'text-warning' : ''">
                <span x-show="conflictMessage"><x-jaunt.icon name="circle-alert" size="sm" /></span>
                <span x-show="! conflictMessage && saveState === 'saving'"><x-jaunt.icon name="loader-circle" size="sm" class="animate-spin" /></span>
                <span x-show="! conflictMessage && saveState !== 'saving'"><x-jaunt.icon name="circle-check" size="sm" /></span>
                <span x-text="saveLabel()">Saved</span>
            </div>
            <div class="relative hidden 2xl:block">
                <select wire:model.live="selectedPreviewTargetId" class="cms-select min-w-28" aria-label="Preview target">
                    <option value="">Internal</option>
                    @foreach($this->previewTargets as $previewTarget)
                        <option value="{{ $previewTarget->id }}">{{ $previewTarget->name }}</option>
                    @endforeach
                </select>
                <x-jaunt.icon name="chevron-down" size="sm" class="pointer-events-none absolute right-2 top-1.5 text-tertiary" />
            </div>
            <a href="{{ $this->previewUrl }}" target="_blank" rel="noopener noreferrer" class="cms-iconbtn border border-default bg-card shadow-xs" title="Open preview" aria-label="Open preview">
                <x-jaunt.icon name="eye" size="sm" />
            </a>
            <button type="button" wire:click="undoLastChange" @disabled(! $this->undoRevision) class="cms-iconbtn hidden border border-default bg-card shadow-xs disabled:cursor-not-allowed disabled:opacity-45 lg:inline-flex" title="{{ $this->undoRevision ? 'Undo last change' : 'Nothing to undo' }}" aria-label="Undo last change">
                <x-jaunt.icon name="undo-2" size="md" />
            </button>
            @php
                $revisionTooltip = 'Revisions';

                if ($this->publishedRevisionComparison) {
                    $publishedBlockChanges = count($this->publishedRevisionComparison['block_changes']);
                    $publishedFieldChanges = count($this->publishedRevisionComparison['content_changes']);
                    $revisionTooltip = $this->publishedRevisionComparison['has_changes']
                        ? 'Since publish: '.$publishedBlockChanges.' '.Str::plural('block', $publishedBlockChanges).', '.$publishedFieldChanges.' '.Str::plural('field', $publishedFieldChanges)
                        : 'Matches published';
                }
            @endphp
            <div class="hidden lg:block">
                <x-jaunt.feedback.tooltip :label="$revisionTooltip" side="bottom-end" id="since-publish-tooltip">
                <button
                    type="button"
                    wire:click="openRevisionModal"
                    class="cms-iconbtn border border-default bg-card shadow-xs"
                    aria-label="Revisions"
                    @if($this->publishedRevisionComparison) aria-describedby="since-publish-tooltip" @endif
                >
                    <x-jaunt.icon name="layers-3" size="md" />
                </button>
                </x-jaunt.feedback.tooltip>
            </div>
            <div class="min-w-[84px]">
                <button
                    type="button"
                    wire:click="openCheckpointModal"
                    x-cloak
                    x-show="saveState !== 'saved'"
                    x-bind:disabled="saveState === 'saving'"
                    class="cms-btn cms-btn-primary h-9 w-full disabled:cursor-wait disabled:opacity-75"
                    title="Save checkpoint"
                >
                    Save
                </button>
                <button
                    type="button"
                    wire:click="publish"
                    x-show="saveState === 'saved'"
                    class="cms-btn cms-btn-primary h-9 w-full"
                >
                    Publish
                </button>
            </div>
        </div>
    </header>

    <div class="contents">
    {{-- Content area: begins exactly below the fixed editor toolbar. --}}
    <div class="cms-editor-body absolute inset-x-0 top-[var(--topbar-h)] bottom-0 flex min-w-0 transition-[margin] duration-base ease-standard" x-bind:style="{ marginRight: inspectorOpen ? 'var(--admin-rail-width)' : '44px' }">
    {{-- Left: Content Tree — Figma node 3:797 --}}
    <aside
        id="content-tree"
        class="cms-editor-tree bg-app border-r border-subtle flex flex-col shrink-0 z-40 hidden xl:flex overflow-hidden transition-[width] duration-base ease-standard"
        x-bind:style="{ width: leftCollapsed ? '44px' : '263px' }"
        aria-label="Content tree"
    >
        <div x-cloak x-show="leftCollapsed" class="flex h-full w-11 flex-col items-center gap-2 py-2">
            <button type="button" x-on:click="openPages()" class="cms-iconbtn text-tertiary" title="Expand pages" aria-label="Expand pages" aria-expanded="false" aria-controls="content-tree">
                <x-jaunt.icon name="panel-left-open" size="sm" class="pointer-events-none" />
            </button>
            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-tertiary [writing-mode:vertical-rl] rotate-180">Pages</span>
        </div>
        <div x-cloak x-show="! leftCollapsed" class="flex h-full min-h-0 w-[263px] flex-col">
        <div class="flex h-[46px] shrink-0 items-center justify-between border-b border-subtle bg-card pl-3 pr-2">
            <span class="text-[13px] font-medium leading-[17.55px] tracking-[-0.154px] text-primary">Pages</span>
            <div class="flex items-center gap-0.5">
                <a href="{{ route('admin.content.create', ['type' => 'page', 'parent_id' => $content->parent_id ?? null]) }}" wire:navigate class="cms-iconbtn !h-[30px] !w-[30px] text-tertiary hover:bg-hover hover:text-primary" title="New page" aria-label="New page"><x-jaunt.icon name="plus" size="sm" /></a>
                <button type="button" x-on:click="leftCollapsed = true" class="cms-iconbtn text-tertiary" title="Collapse pages" aria-label="Collapse pages" aria-expanded="true" aria-controls="content-tree"><x-jaunt.icon name="panel-left-close" size="sm" class="pointer-events-none" /></button>
            </div>
        </div>
        <div class="shrink-0 border-b border-subtle bg-card p-2">
            <label class="cms-input w-full" aria-label="Search pages">
                <x-jaunt.icon name="search" size="sm" class="shrink-0 text-tertiary" />
                <input
                    type="search"
                    x-model.debounce.150ms="contentSearch"
                    placeholder="Search pages"
                    autocomplete="off"
                />
            </label>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-2">
            @foreach($this->contentTree as $item)
                @if($item->isFolder())
                    <div
                        wire:key="editor-tree-folder-{{ $item->id }}"
                        x-show="folderMatches(@js($item->name), @js(($item->children ?? collect())->pluck('name')->all()))"
                    >
                        <div class="group flex h-8 w-full items-center rounded-sm text-[13px] leading-[19.5px] tracking-[-0.154px] text-secondary transition-colors duration-fast hover:bg-hover hover:text-primary">
                            <button
                                type="button"
                                x-on:click="toggleFolder({{ $item->id }})"
                                x-bind:aria-expanded="isFolderExpanded({{ $item->id }})"
                                aria-controls="editor-folder-{{ $item->id }}"
                                class="flex h-8 w-7 shrink-0 items-center justify-center rounded-sm text-tertiary hover:text-primary"
                                title="Toggle {{ $item->name }}"
                                aria-label="Toggle {{ $item->name }}"
                            >
                                <span class="transition-transform duration-fast ease-standard" x-bind:class="isFolderExpanded({{ $item->id }}) && 'rotate-90'">
                                    <x-jaunt.icon name="chevron-right" size="xs" class="pointer-events-none" />
                                </span>
                            </button>
                            <a href="{{ route('admin.content.index', ['folder' => $item->id]) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2 self-stretch pr-[9px]">
                                <x-jaunt.icon name="folder" size="sm" class="!h-[15px] !w-[15px] shrink-0 text-tertiary group-hover:text-secondary" />
                                <span class="min-w-0 flex-1 truncate">{{ $item->name }}</span>
                            </a>
                        </div>
                        <div id="editor-folder-{{ $item->id }}" x-show="isFolderExpanded({{ $item->id }})">
                            @foreach($item->children ?? [] as $child)
                                <a
                                    href="{{ route('admin.content.editor', $child) }}"
                                    wire:navigate
                                    x-show="contentNameMatches(@js($child->name))"
                                    class="group flex h-8 w-full cursor-pointer items-center gap-2 rounded-sm pl-[27px] pr-[9px] text-[13px] leading-[19.5px] tracking-[-0.154px] transition-colors duration-fast {{ $child->id === $content->id ? 'bg-selected font-medium text-primary' : 'font-normal text-secondary hover:bg-hover hover:text-primary' }}"
                                >
                                    <x-jaunt.icon name="file-text" size="sm" class="!h-[15px] !w-[15px] shrink-0 {{ $child->id === $content->id ? 'text-primary' : 'text-tertiary group-hover:text-secondary' }}" />
                                    <span class="min-w-0 flex-1 truncate">{{ $child->name }}</span>
                                    @if($child->status === 'published')
                                        <span class="h-[7px] w-[7px] shrink-0 rounded-full bg-success" aria-label="Published"></span>
                                    @else
                                        <span class="h-[7px] w-[7px] shrink-0 rounded-full bg-strong" aria-label="Draft"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route('admin.content.editor', $item) }}" wire:navigate x-show="contentNameMatches(@js($item->name))" class="group flex h-8 w-full cursor-pointer items-center gap-2 rounded-sm px-[9px] text-[13px] leading-[19.5px] tracking-[-0.154px] transition-colors duration-fast {{ $item->id === $content->id ? 'bg-selected font-medium text-primary' : 'font-normal text-secondary hover:bg-hover hover:text-primary' }}">
                        <x-jaunt.icon name="file-text" size="sm" class="!h-[15px] !w-[15px] shrink-0 {{ $item->id === $content->id ? 'text-primary' : 'text-tertiary group-hover:text-secondary' }}" />
                        <span class="min-w-0 flex-1 truncate">{{ $item->name }}</span>
                        @if($item->status === 'published')
                            <span class="h-[7px] w-[7px] shrink-0 rounded-full bg-success" aria-label="Published"></span>
                        @else
                            <span class="h-[7px] w-[7px] shrink-0 rounded-full bg-strong" aria-label="Draft"></span>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
        </div>
    </aside>

    {{-- Center: Canvas only (header is fixed above) --}}
    <main class="flex-1 min-w-0 flex flex-col bg-sunken relative" role="main" aria-label="Page canvas">
        <div x-show="canvasMode === 'preview'" x-cloak class="relative flex-1 min-h-0 overflow-hidden bg-sunken p-4">
            <iframe
                x-ref="previewFrame"
                x-on:load="restorePreviewScroll(); syncPreviewSelection()"
                wire:ignore
                name="pilot-cms-preview"
                x-bind:src="previewFrameSrc"
                x-bind:style="`width: ${previewWidth()}`"
                class="mx-auto h-full max-w-full rounded-lg border border-default bg-card shadow-lg transition-[width]"
                title="Live preview"
            ></iframe>

            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="cms-fab absolute bottom-8 left-1/2 z-50 -translate-x-1/2">
                <x-jaunt.icon name="plus" size="sm" class="text-white" />
                <span class="text-sm font-medium text-white">Add Block</span>
                <div class="h-4 w-px bg-white/30"></div>
                <span class="font-mono text-xs text-white">⌘B</span>
            </button>
        </div>

        <div x-show="canvasMode === 'compose'" class="flex min-h-0 flex-1 overflow-hidden">
        <div class="relative flex min-h-0 flex-1 flex-col items-center overflow-hidden bg-slate-100" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
            <div class="w-full flex justify-between items-center px-4 py-2 text-xs text-slate-400 font-mono select-none shrink-0">
                <span x-text="previewDevice === 'desktop' ? '1280px canvas' : previewDevice === 'tablet' ? '768px canvas' : '390px canvas'">1280px canvas</span>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <span>Connected</span>
                </div>
            </div>

            <div x-bind:style="`width: ${previewWidth()}`" class="h-full min-h-0 max-w-full overflow-y-auto bg-white pb-20 shadow-2xl ring-1 ring-slate-900/5 transition-[width]">
                <div class="min-h-[500px] p-10 lg:p-14">
                    <h1 class="text-3xl lg:text-4xl font-bold mb-8 text-slate-900">{{ $content->name }}</h1>

                    @if(empty($blocks))
                        <div
                            class="text-center py-24 text-slate-500 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50/30 transition-colors"
                            wire:click="$set('blockLibraryOpen', true)"
                            role="button"
                            tabindex="0"
                        >
                            <x-jaunt.icon name="circle-plus" size="lg" class="mx-auto mb-6 text-slate-300" />
                            <p class="font-medium text-lg text-slate-700">No blocks yet</p>
                            <p class="text-sm mt-1">Click to add your first block</p>
                        </div>
                    @else
                        @foreach($blocks as $index => $block)
                            @include('livewire.admin.content.partials.canvas-block', [
                                'block' => $block,
                                'blockTypes' => $blockTypes,
                                'selectedBlockId' => $selectedBlockId,
                                'depth' => 0,
                            ])
                        @endforeach
                        <div class="flex justify-center py-4">
                            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="cms-btn cms-btn-secondary">
                                <x-jaunt.icon name="plus" size="sm" />
                                Add block
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Floating Add Block button --}}
            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="cms-fab absolute bottom-8 z-50">
                <x-jaunt.icon name="plus" size="sm" class="text-white" />
                <span class="text-sm font-medium text-white">Add Block</span>
                <div class="h-4 w-px bg-white/30"></div>
                <span class="font-mono text-xs text-white">⌘B</span>
            </button>
        </div>
        </div>
    </main>
    </div>{{-- /content area --}}

    {{-- Right: Edit Panel — fixed top 0, bottom 0, right 0, 500px, 100% view height --}}
    @php
        $editPanelTabs = ['content' => 'Content', 'comments' => 'Comments', 'validation' => 'Checks', 'seo' => 'Advanced'];
        $hasSelectedBlock = $selectedBlockId !== null;
        $sel = $hasSelectedBlock ? $this->selectedBlock : null;
        $bt = $sel ? ($blockTypes[$sel['type']] ?? null) : null;
    @endphp
    <aside
        id="content-inspector"
        x-bind:class="{
            'is-richtext-expanded': $store.pilotRichTextWorkspace?.expanded ?? false,
            'shadow-xl': inspectorOpen,
            'shadow-none': ! inspectorOpen,
        }"
        x-bind:style="{ width: inspectorOpen ? 'var(--admin-rail-width)' : '44px' }"
        class="cms-drawer cms-editor-inspector fixed top-[var(--topbar-h)] bottom-0 right-0 transition-[width,box-shadow] duration-base ease-standard z-40"
        aria-label="Edit panel"
    >
        <div x-cloak x-show="! inspectorOpen" class="flex h-full w-11 flex-col items-center gap-2 py-2">
            <button type="button" x-on:click="openInspector()" class="cms-iconbtn text-tertiary" title="Expand inspector" aria-label="Expand inspector" aria-expanded="false" aria-controls="content-inspector">
                <x-jaunt.icon name="panel-right-open" size="sm" class="pointer-events-none" />
            </button>
            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-tertiary [writing-mode:vertical-rl] rotate-180">Inspector</span>
        </div>
        <div x-cloak x-show="inspectorOpen" class="flex h-full min-h-0 flex-col">
        {{-- Header: breadcrumb nav (Page > Block Name) with actions --}}
        <div class="cms-drawer-header">
            <div class="flex items-center gap-1.5 min-w-0">
                {{-- "Page" is always the root, clickable to deselect block --}}
                <button type="button" wire:click="$set('selectedBlockId', null)" class="cms-text-btn shrink-0 {{ $hasSelectedBlock ? '' : 'text-primary' }}" aria-current="{{ $hasSelectedBlock ? 'false' : 'page' }}">
                    <span class="w-6 h-6 rounded bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-100">P</span>
                    <span class="text-sm {{ $hasSelectedBlock ? 'font-medium' : 'font-bold' }}">Page</span>
                </button>
                @if($hasSelectedBlock)
                    <x-jaunt.icon name="chevron-right" size="xs" class="text-slate-300 shrink-0 mx-0.5" />
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-6 h-6 rounded bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-100 shrink-0">{{ $bt ? strtoupper(mb_substr($bt->name, 0, 1)) : 'B' }}</span>
                        <span class="font-bold text-slate-800 text-sm truncate">{{ $bt ? $bt->name : 'Block' }}</span>
                    </div>
                @endif
            </div>
            <div class="flex gap-1 shrink-0">
                @if($hasSelectedBlock)
                <button type="button" wire:click="duplicateBlock({{ $selectedBlockId }})" class="cms-iconbtn" aria-label="Duplicate block" title="Duplicate block"><x-jaunt.icon name="copy" size="sm" /></button>
                <button type="button" wire:click="deleteBlock({{ $selectedBlockId }})" wire:confirm="Delete this block?" class="cms-iconbtn cms-iconbtn-danger" aria-label="Delete block" title="Delete block"><x-jaunt.icon name="trash-2" size="sm" /></button>
                @endif
                <button type="button" x-on:click="inspectorOpen = false" class="cms-iconbtn text-tertiary" title="Collapse inspector" aria-label="Collapse inspector" aria-expanded="true" aria-controls="content-inspector">
                    <x-jaunt.icon name="panel-right-close" size="sm" class="pointer-events-none" />
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="cms-drawer-tabs" role="tablist" aria-label="Inspector sections" data-cms-tabs>
            @foreach($editPanelTabs as $tab => $label)
            <button type="button" id="inspector-tab-{{ $tab }}" wire:click="$wire.set('rightPanelTab', '{{ $tab }}')" class="cms-tab flex-1" role="tab" aria-selected="{{ $rightPanelTab === $tab ? 'true' : 'false' }}" aria-controls="inspector-panel-{{ $tab }}" tabindex="{{ $rightPanelTab === $tab ? '0' : '-1' }}">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Scrollable body --}}
        <div class="cms-drawer-body space-y-7" data-editor-inspector-body>

            {{-- CONTENT TAB --}}
            <div id="inspector-panel-content" class="{{ $rightPanelTab === 'content' ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="inspector-tab-content">

                @if($hasSelectedBlock && $bt)
                    {{-- When a block is selected: show ONLY the block fields --}}
                    <livewire:admin.content.block-editor
                        :block="$sel"
                        :block-type="$bt"
                        :expanded-repeater-items="$expandedRepeaterItemsByBlock[(int) $selectedBlockId] ?? []"
                        :key="'block-editor-' . $selectedBlockId . '-' . $editorSyncVersion"
                    />
                @else
                    {{-- No block selected: show full page edit form --}}
                    <div class="space-y-7">

                    {{-- Name --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Name</label>
                        </div>
                        <input type="text" value="{{ $content->name }}" wire:change="updateContent('name', $event.target.value)" placeholder="Page title" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast" />
                    </div>

                    {{-- Slug --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Slug</label>
                        </div>
                        <input type="text" value="{{ $content->slug }}" wire:change="updateContent('slug', $event.target.value)" placeholder="page-slug" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Categories</label>
                        </div>
                        <input type="text" value="{{ implode(', ', $content->categories ?? []) }}" wire:change="updateTaxonomy('categories', $event.target.value)" placeholder="News, Destinations" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast" />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated categories for grouping content.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Tags</label>
                        </div>
                        <input type="text" value="{{ implode(', ', $content->tags ?? []) }}" wire:change="updateTaxonomy('tags', $event.target.value)" placeholder="family travel, hiking, summer" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm transition-[border-color,box-shadow,background-color] duration-fast" />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated tags for filtering and discovery.</p>
                    </div>

                    {{-- Parent Folder (pages only) --}}
                    @if($content->isPage())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Content Type</label>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('content_type_id', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="">Generic Page</option>
                                @foreach($this->contentTypes as $contentType)
                                    <option value="{{ $contentType->id }}" {{ $content->content_type_id === $contentType->id ? 'selected' : '' }}>{{ $contentType->name }}</option>
                                @endforeach
                            </select>
                            <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Parent Folder</label>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('parent_id', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="">None (Root)</option>
                                @foreach($this->folders as $folder)
                                    <option value="{{ $folder->id }}" {{ $content->parent_id == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                @endforeach
                            </select>
                            <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                    @endif

                    {{-- Status --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Status</label>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('status', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="draft" {{ $content->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $content->status === 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                            <x-jaunt.icon name="chevron-down" size="sm" class="absolute right-3 top-3 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    </div>{{-- /space-y-7 --}}

                    {{-- Blocks list --}}
                    <div class="pt-5 mt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Blocks</span>
                            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="cms-text-btn">Add block</button>
                        </div>
                        <div wire:sort="sortItem" class="space-y-0.5">
                            @foreach($blocks as $block)
                            <div wire:sort:item="{{ $block['id'] }}" wire:key="block-item-{{ $block['id'] }}" data-block-tree-item="{{ $block['id'] }}" class="flex items-center gap-2 rounded-md px-3 py-2.5 {{ $selectedBlockId === $block['id'] ? 'bg-selected text-primary shadow-[inset_2px_0_0_var(--accent)]' : 'hover:bg-hover' }}">
                                <span class="cursor-grab active:cursor-grabbing touch-none text-slate-400 hover:text-slate-600" wire:sort:handle aria-label="Drag to reorder"><x-jaunt.icon name="grip-vertical" size="sm" /></span>
                                <div class="flex-1 min-w-0 py-1 cursor-pointer" wire:click="$set('selectedBlockId', {{ $block['id'] }})">
                                    <span class="font-medium text-sm truncate block text-slate-700">{{ $blockTypes[$block['type']]->name ?? $block['type'] }}</span>
                                </div>
                                <button type="button" wire:click="deleteBlock({{ $block['id'] }})" wire:confirm="Delete this block?" wire:sort:ignore class="cms-iconbtn cms-iconbtn-danger" aria-label="Delete block"><x-jaunt.icon name="trash-2" size="sm" /></button>
                            </div>
                            @endforeach
                        </div>
                        @if(empty($blocks))
                        <p class="py-6 text-center text-sm text-slate-400">No blocks. Click Add to insert one.</p>
                        @endif
                    </div>
                @endif

                <div class="h-10"></div>
            </div>

            {{-- COMMENTS TAB --}}
            <div id="inspector-panel-comments" class="{{ $rightPanelTab === 'comments' ? '' : 'hidden' }} space-y-5" role="tabpanel" aria-labelledby="inspector-tab-comments">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Presence</span>
                    <div wire:poll.visible.15000ms="touchPresence" class="space-y-2">
                        @forelse($this->activePresences as $presence)
                            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white">
                                    {{ $presence->user?->initials() ?? '?' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-700">{{ $presence->user?->name ?? 'Collaborator' }}</div>
                                    <div class="truncate text-xs text-slate-400">
                                        {{ $presence->status === 'editing' ? 'Editing' : 'Viewing' }}
                                        @if($presence->selectedBlock)
                                            {{ $presence->selectedBlock->reusable_name ?? $presence->selectedBlock->type }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No other editors are active.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Block comments</span>
                    @if($hasSelectedBlock)
                        <div class="space-y-3">
                            @forelse($this->selectedBlockComments as $comment)
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <span class="text-xs font-semibold text-slate-600">{{ $comment->user?->name ?? 'System' }}</span>
                                        <button type="button" wire:click="resolveBlockComment({{ $comment->id }})" class="cms-text-btn">Resolve</button>
                                    </div>
                                    <p class="text-sm text-slate-600">{{ $comment->body }}</p>
                                </div>
                            @empty
                                <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No open comments on this block.</p>
                            @endforelse
                            <textarea rows="3" wire:model="newCommentBody" placeholder="Leave a comment for reviewers" class="w-full resize-none rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                            @error('newCommentBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="button" wire:click="addBlockComment" class="cms-btn cms-btn-primary w-full">Add comment</button>
                        </div>
                    @else
                        <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">Select a block to view or add comments.</p>
                    @endif
                </div>
            </div>

            {{-- VALIDATION TAB --}}
            <div id="inspector-panel-validation" class="{{ $rightPanelTab === 'validation' ? '' : 'hidden' }} space-y-5" role="tabpanel" aria-labelledby="inspector-tab-validation">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Validation panel</span>
                    <div class="space-y-2">
                        @forelse($this->validationIssues as $issue)
                            <button
                                type="button"
                                @if($issue['block_id']) wire:click="setSelectedBlockFromPreview({{ $issue['block_id'] }})" @endif
                                class="flex w-full items-start gap-3 rounded-lg border {{ $issue['severity'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700' }} p-3 text-left"
                            >
                                <x-jaunt.icon :name="$issue['severity'] === 'error' ? 'octagon-alert' : 'circle-alert'" size="sm" class="mt-0.5" />
                                <span class="text-sm font-medium">{{ $issue['label'] }}</span>
                            </button>
                        @empty
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm font-medium text-blue-700">No validation issues found.</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Reusable blocks</span>
                    @if($hasSelectedBlock)
                        <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <input type="text" wire:model="reusableBlockName" placeholder="Reusable block name" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                            @error('reusableBlockName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="button" wire:click="makeSelectedBlockReusable" class="cms-btn cms-btn-secondary w-full">Save selected as reusable</button>
                        </div>
                    @endif
                    <div class="mt-3 space-y-2">
                        @forelse($this->reusableBlocks as $reusableBlock)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-700">{{ $reusableBlock->reusable_name }}</div>
                                    <div class="truncate text-xs text-slate-400">{{ $reusableBlock->type }} · {{ $reusableBlock->content?->name }}</div>
                                </div>
                                <button type="button" wire:click="insertReusableBlock({{ $reusableBlock->id }})" class="cms-btn cms-btn-sm cms-btn-ghost shrink-0">Insert</button>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No reusable blocks yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ADVANCED TAB (SEO + Status + History) --}}
            <div id="inspector-panel-seo" class="{{ $rightPanelTab === 'seo' ? '' : 'hidden' }} space-y-6" role="tabpanel" aria-labelledby="inspector-tab-seo">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">SEO</span>
                    <div class="group mb-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Meta title</label>
                        <input type="text" value="{{ $content->meta['meta_title'] ?? '' }}" wire:change="updateContentMeta('meta_title', $event.target.value)" placeholder="Page title for search engines" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm" />
                    </div>
                    <div class="group">
                        <label class="text-xs text-slate-600 block mb-1.5">Meta description</label>
                        <textarea rows="3" wire:change="updateContentMeta('meta_description', $event.target.value)" placeholder="Brief description" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-none">{{ $content->meta['meta_description'] ?? '' }}</textarea>
                    </div>
                    <div class="group mt-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Canonical URL</label>
                        <input type="text" value="{{ $content->meta['canonical_url'] ?? '' }}" wire:change="updateContentMeta('canonical_url', $event.target.value)" placeholder="https://example.com/page" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm" />
                    </div>
                    <div class="group mt-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Open Graph image</label>
                        <input type="text" value="{{ $content->meta['og_image'] ?? '' }}" wire:change="updateContentMeta('og_image', $event.target.value)" placeholder="/storage/social-card.jpg" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm" />
                    </div>
                    <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:change="updateContentMeta('noindex', $event.target.checked)" {{ ! empty($content->meta['noindex']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-500 focus:ring-blue-500" />
                        Hide from search engines
                    </label>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Workflow</span>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">State</span>
                            <span class="rounded bg-white px-2 py-0.5 text-xs text-slate-600">{{ str($content->workflow_status)->replace('_', ' ')->title() }}</span>
                        </div>
                        @if($content->scheduled_for)
                            <div class="mt-2 text-xs text-slate-500">Scheduled for {{ $content->scheduled_for->format('M j, Y g:i A') }}</div>
                        @endif
                        @if($content->reviewer)
                            <div class="mt-2 text-xs text-slate-500">Reviewer: {{ $content->reviewer->name }}</div>
                        @endif
                        @if($content->review_due_at)
                            <div class="mt-1 text-xs text-slate-500">Due {{ $content->review_due_at->format('M j, Y g:i A') }}</div>
                        @endif
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" wire:click="requestReview" class="cms-btn cms-btn-sm cms-btn-secondary">Request review</button>
                            <button type="button" wire:click="unpublish" class="cms-btn cms-btn-sm cms-btn-danger">Unpublish</button>
                            <button type="button" wire:click="approveReview" class="cms-btn cms-btn-sm cms-btn-primary">Approve</button>
                            <button type="button" wire:click="requestChanges" class="cms-btn cms-btn-sm cms-btn-secondary">Request changes</button>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                        <label class="text-xs text-slate-600 block">Assign reviewer</label>
                        <select wire:model="reviewerId" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">Unassigned</option>
                            @foreach($this->reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                            @endforeach
                        </select>
                        <label class="text-xs text-slate-600 block">Review due date</label>
                        <input type="datetime-local" wire:model="reviewDueAt" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                        <label class="text-xs text-slate-600 block">Review note</label>
                        <textarea rows="3" wire:model="reviewNote" class="w-full resize-none rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                        <button type="button" wire:click="assignReview" class="cms-btn cms-btn-sm cms-btn-primary w-full">Assign review</button>
                    </div>
                    <div class="mt-3 space-y-2">
                        <label class="text-xs text-slate-600 block">Schedule publish</label>
                        <input type="datetime-local" wire:model="scheduledFor" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm" />
                        <button type="button" wire:click="schedulePublishing" class="cms-btn cms-btn-sm cms-btn-secondary w-full">Schedule</button>
                        @error('scheduledFor') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer: Block ID + Type when block selected, otherwise Last updated --}}
        <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
            <div class="flex flex-col gap-1">
                @if($hasSelectedBlock)
                    <div class="flex justify-between items-center text-[10px] font-mono text-slate-400">
                        <span>Block ID</span>
                        <span>{{ Str::limit($selectedBlockId, 8) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-mono text-slate-400">
                        <span>Type</span>
                        <span>{{ $sel['type'] ?? '—' }}</span>
                    </div>
                @else
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Last updated</span>
                        <span x-text="savedJustNow ? 'Just now' : '{{ $lastSavedAt ? $lastSavedAt->diffForHumans() : '2 mins ago' }}'">{{ $lastSavedAt ? $lastSavedAt->diffForHumans() : '2 mins ago' }}</span>
                    </div>
                @endif
            </div>
        </div>
        </div>
    </aside>
    </div>

    @if($revisionModalOpen)
        <div
            wire:keydown.escape="closeRevisionModal"
            x-on:keydown.escape.window="$wire.closeRevisionModal()"
            class="fixed inset-0 z-50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="revision-modal-title"
        >
            <button type="button" wire:click="closeRevisionModal" class="absolute inset-0 h-full w-full bg-slate-500/25" aria-label="Close revisions"></button>
            <div class="relative flex h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="relative flex h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl ring-1 ring-slate-900/5 sm:h-[calc(100vh-3rem)]">
                    <button type="button" wire:click="closeRevisionModal" class="fixed z-[60] -translate-y-1/2 rounded border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] leading-[14px] text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-700" style="top: 3rem; right: max(1rem, calc((100vw - 72rem) / 2 + 1rem));" title="Close" aria-label="Close revisions">
                        esc
                    </button>
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 pr-16">
                        <div>
                            <h2 id="revision-modal-title" class="text-base font-semibold text-slate-800">Revisions</h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $this->revisions->count() }} of {{ $this->revisionTotalCount }} loaded</p>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4">
                        @include('livewire.admin.content.partials.revisions-panel')
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Block Library Modal --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="blockLibraryOpen"
            x-on:keydown.escape.window="blockLibraryOpen = false"
            x-on:click.self="blockLibraryOpen = false"
            class="fixed inset-0 z-overlay flex items-center justify-center bg-slate-950/30 p-4 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="block-library-title"
            x-transition.opacity
        >
        <button type="button" class="absolute inset-0 cursor-default" aria-label="Close block library" x-on:click="blockLibraryOpen = false"></button>
        <div
            x-on:click.stop
            x-data="{
                blockSearch: '',
                blockMatches(name, key, description) {
                    const query = this.blockSearch.trim().toLowerCase();

                    if (! query) {
                        return true;
                    }

                    return [name, key, description].some((value) => String(value || '').toLowerCase().includes(query));
                },
                hasBlockMatches() {
                    return Array.from(this.$refs.blockGrid?.querySelectorAll('[data-block-search-text]') || []).some((element) => {
                        return ! this.blockSearch.trim() || element.dataset.blockSearchText.includes(this.blockSearch.trim().toLowerCase());
                    });
                },
            }"
            x-effect="if (! blockLibraryOpen) blockSearch = ''"
            x-transition.scale.origin.center.duration.150ms
            class="relative z-10 flex h-[480px] max-h-[calc(100vh-2rem)] w-full max-w-[720px] flex-col overflow-hidden rounded-[14px] border border-slate-300 bg-white/95 shadow-[0_8px_16px_rgba(19,20,24,0.08),0_28px_64px_rgba(19,20,24,0.18)] ring-1 ring-slate-950/5 sm:max-h-[calc(100vh-3rem)]"
            data-block-library
        >
            <h2 id="block-library-title" class="sr-only">Add a block</h2>

            <div class="flex h-[54px] shrink-0 items-center gap-2.5 border-b border-slate-200 bg-white px-4">
                <x-jaunt.icon name="layout-grid" size="md" class="text-slate-500" />
                <input
                    x-ref="blockSearch"
                    type="search"
                    x-model.debounce.100ms="blockSearch"
                    placeholder="Add a block..."
                    aria-label="Search blocks"
                    class="h-full min-w-0 flex-1 border-0 bg-transparent px-0.5 py-0 text-[15px] font-normal text-slate-800 outline-none placeholder:text-slate-500 focus:ring-0"
                />
                <button type="button" x-on:click="blockLibraryOpen = false" class="rounded border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] leading-[14px] text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-700">
                    esc
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-[14px]">
                @if($addBlockPosition === 'inside' && $addBlockParentId)
                    <p class="mb-4 text-sm text-slate-500">Choose a block to nest inside the selected container.</p>
                @endif

                @if($blockTypes->isEmpty())
                    <div class="flex min-h-56 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
                        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <x-jaunt.icon name="layout-grid" size="lg" />
                        </span>
                        <p class="font-medium text-slate-800">You don't have any block types yet</p>
                        <p class="mt-1 text-sm text-slate-500">Create one to start building pages.</p>
                        <a href="{{ route('admin.blocks.create') }}" wire:navigate class="mt-5 inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-on-accent shadow-sm transition-colors hover:bg-accent-hover">Create Block Type</a>
                    </div>
                @else
                    <div x-ref="blockGrid" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($blockTypes as $blockType)
                        @php
                            $blockDescription = $blockType->schema['description'] ?? '';
                            $blockSearchText = strtolower(trim($blockType->name.' '.$blockType->key.' '.$blockDescription));
                            $blockIcon = match ($blockType->icon) {
                                'rectangle-stack' => 'panels-top-left',
                                'document-text' => 'align-left',
                                'photo' => 'image',
                                'squares-2x2' => 'grid-2x2',
                                'arrow-right' => 'mouse-pointer-click',
                                'columns' => 'columns-3',
                                'squares-plus' => 'layout-grid',
                                'map' => 'map',
                                'calendar' => 'calendar-days',
                                default => 'box',
                            };
                        @endphp
                        <button
                            type="button"
                            x-show="blockMatches(@js($blockType->name), @js($blockType->key), @js($blockDescription))"
                            wire:click="addBlock('{{ $blockType->key }}')"
                            data-block-search-text="{{ $blockSearchText }}"
                            class="group rounded-[10px] border border-slate-300 bg-white p-[15px] text-left shadow-[0_1px_1px_rgba(19,20,24,0.06),0_2px_3px_rgba(19,20,24,0.05)] outline-none transition-[border-color,box-shadow,transform] duration-fast hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                                <x-jaunt.icon :name="$blockIcon" size="md" />
                            </span>
                            <span class="mt-2.5 block text-[13px] font-medium leading-[18px] tracking-[-0.01em] text-slate-900">{{ $blockType->name }}</span>
                            @if($blockDescription)
                                <span class="mt-1 block text-xs leading-[17px] tracking-[-0.01em] text-slate-500">{{ $blockDescription }}</span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    <div x-show="! hasBlockMatches()" x-cloak class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                        No blocks match your search.
                    </div>
                @endif
            </div>
            </div>
        </div>
    </template>

    @livewire('admin.assets.asset-picker-modal')
</div>
</div>
