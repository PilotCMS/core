<div class="cms-shell flex h-full w-full min-w-0 flex-col bg-app">
    <x-jaunt.shell.dynamic-header
        :title="$type === 'folder' ? 'New folder' : 'New page'"
        :subtitle="$type === 'folder' ? 'Create a place to organize related content.' : 'Name the page now; refine its structure in the editor.'"
        top="0px"
        as="header"
        scroll-target="#content-create-scroll"
        aria-label="Page header"
    />

    <main id="content-create-scroll" class="min-h-0 flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-3xl px-[var(--pad-view)] pb-12 pt-4">
            <a href="{{ route('admin.content.index') }}" wire:navigate class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-secondary transition-colors hover:text-primary">
                <x-jaunt.icon name="arrow-left" size="sm" />
                Back to content
            </a>

            <form wire:submit="save" x-data="{ advanced: false }" class="cms-panel overflow-hidden">
                <div class="space-y-6 p-6 sm:p-8">
                    @if($parent)
                        <div class="flex items-center gap-2 rounded-md bg-info-subtle px-3 py-2 text-sm text-info">
                            <x-jaunt.icon name="folder" size="sm" />
                            Creating in <strong>{{ $parent->name }}</strong>
                        </div>
                    @endif

                    <flux:field>
                        <flux:label>{{ $type === 'folder' ? 'Folder name' : 'Page name' }}</flux:label>
                        <flux:input wire:model.live.debounce.250ms="name" autofocus placeholder="{{ $type === 'folder' ? 'Campaigns' : 'Summer travel guide' }}" />
                        <flux:description>
                            @if($name !== '')
                                Will be created as <span class="font-mono text-primary">/{{ $slug }}</span>
                            @else
                                The URL slug is generated automatically.
                            @endif
                        </flux:description>
                        <flux:error name="name" />
                        <flux:error name="slug" />
                    </flux:field>

                    <button type="button" x-on:click="advanced = ! advanced" class="flex w-full items-center justify-between rounded-md border border-subtle bg-sunken px-3 py-2 text-left text-sm font-medium text-secondary transition-colors hover:bg-hover hover:text-primary" x-bind:aria-expanded="advanced">
                        <span>Advanced options</span>
                        <x-jaunt.icon name="chevron-down" size="sm" x-bind:class="advanced ? 'rotate-180' : ''" class="transition-transform" />
                    </button>

                    <div x-show="advanced" x-cloak class="space-y-5 border-t border-subtle pt-5">
                        <flux:field>
                            <flux:label>Entry type</flux:label>
                            <div class="cms-seg w-fit" role="group" aria-label="Entry type">
                                <button type="button" wire:click="$set('type', 'page')" class="cms-seg-btn" data-active="{{ $type === 'page' ? 'true' : 'false' }}" aria-pressed="{{ $type === 'page' ? 'true' : 'false' }}">Page</button>
                                <button type="button" wire:click="$set('type', 'folder')" class="cms-seg-btn" data-active="{{ $type === 'folder' ? 'true' : 'false' }}" aria-pressed="{{ $type === 'folder' ? 'true' : 'false' }}">Folder</button>
                            </div>
                            <flux:error name="type" />
                        </flux:field>

                        @if($type === 'page')
                            <flux:field>
                                <flux:label>Content type</flux:label>
                                <flux:select wire:model="contentTypeId">
                                    <option value="">Generic page</option>
                                    @foreach($contentTypes as $contentType)
                                        <option value="{{ $contentType->id }}">{{ $contentType->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="contentTypeId" />
                            </flux:field>
                        @endif

                        @if($spaces->count() > 1)
                            <flux:field>
                                <flux:label>Space</flux:label>
                                <flux:select wire:model="spaceId">
                                    @foreach($spaces as $space)
                                        <option value="{{ $space->id }}">{{ $space->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="spaceId" />
                            </flux:field>
                        @endif

                        <flux:field>
                            <flux:label>URL slug</flux:label>
                            <flux:input wire:model="slug" placeholder="summer-travel-guide" />
                            <flux:description>Edit only when this page needs a specific URL.</flux:description>
                            <flux:error name="slug" />
                        </flux:field>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-subtle bg-sunken px-6 py-4 sm:px-8">
                    <span class="text-xs text-tertiary">New content starts as a draft.</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.content.index') }}" wire:navigate class="cms-btn cms-btn-secondary">Cancel</a>
                        <button type="submit" class="cms-btn cms-btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Create {{ $type }}</span>
                            <span wire:loading wire:target="save">Creating…</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>
