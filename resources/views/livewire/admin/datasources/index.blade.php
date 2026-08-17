<div class="cms-drawer-page flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Datasources" subtitle="Manage reusable option lists for block fields." top="0px" as="header" scroll-target="#datasources-scroll" aria-label="Page header">
        <x-slot:actions>
        @can('manage datasources')
            <div class="cms-actions pb-0.5">
                <button type="button" wire:click="openCreateDatasource" class="cms-btn cms-btn-primary">
                    <x-jaunt.icon name="plus" size="sm" />
                    New datasource
                </button>
            </div>
        @endcan
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="flex min-h-0 flex-1">
        <main id="datasources-scroll" class="min-w-0 flex-1 overflow-y-auto">
            <div class="w-full space-y-8 p-6 md:p-8">
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Datasources</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($datasources->count()) }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Entries</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($datasources->sum('entries_count')) }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Selected Space</p>
                        <p class="mt-2 truncate text-2xl font-bold text-slate-900">{{ $spaces->firstWhere('id', $spaceId)?->name ?? 'None' }}</p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <flux:heading size="md">Option lists</flux:heading>
                            <flux:text class="mt-1 text-sm text-slate-500">Reference these slugs from block select fields using a `datasource` key.</flux:text>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <flux:select wire:model.live="spaceId" class="sm:w-56">
                                @foreach($spaces as $space)
                                    <option value="{{ $space->id }}">{{ $space->name }}</option>
                                @endforeach
                            </flux:select>

                            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search datasources..." icon="magnifying-glass" class="sm:w-72" />
                        </div>
                    </div>

                    <flux:table>
                        <flux:table.head>
                            <flux:table.row>
                                <flux:table.header>Name</flux:table.header>
                                <flux:table.header>Slug</flux:table.header>
                                <flux:table.header>Entries</flux:table.header>
                                <flux:table.header>Updated</flux:table.header>
                                <flux:table.header class="text-right">Actions</flux:table.header>
                            </flux:table.row>
                        </flux:table.head>

                        <flux:table.body>
                            @forelse($datasources as $datasource)
                                <flux:table.row wire:key="datasource-row-{{ $datasource->id }}" class="transition-colors hover:bg-slate-50">
                                    <flux:table.cell>
                                        <button type="button" wire:click="selectDatasource({{ $datasource->id }})" class="cms-text-btn !h-auto !min-h-0 !p-0 text-left">
                                            <span class="block font-medium text-slate-900">{{ $datasource->name }}</span>
                                            <span class="block text-sm text-slate-500">{{ $datasource->space->name }}</span>
                                        </button>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $datasource->slug }}</code>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm text-slate-600">{{ $datasource->entries_count }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm text-slate-500">{{ $datasource->updated_at->format('M d, Y') }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        <flux:button wire:click="selectDatasource({{ $datasource->id }})" size="sm" variant="ghost">
                                            <flux:icon.pencil class="size-4" />
                                            Manage
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="py-16">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                                                <x-jaunt.icon name="database" size="lg" class="text-blue-600" />
                                            </div>
                                            <flux:heading size="sm" class="mt-4">No datasources found</flux:heading>
                                            <flux:text class="mt-2 max-w-sm text-sm text-slate-500">Create an option list for select fields, labels, statuses, themes, or reusable content choices.</flux:text>
                                            @can('manage datasources')
                                                <flux:button wire:click="openCreateDatasource" variant="primary" class="mt-6">
                                                    <flux:icon.plus class="size-4" />
                                                    Create datasource
                                                </flux:button>
                                            @endcan
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.body>
                    </flux:table>
                </section>
            </div>
        </main>

        <aside class="cms-drawer" aria-label="Details">
            <div class="cms-drawer-header">
                <h2 class="cms-drawer-title">{{ $selectedDatasource ? 'Manage datasource' : 'Details' }}</h2>
            </div>

            <div class="cms-drawer-body">
                @if($selectedDatasource)
                    <div class="space-y-6">
                        <section class="space-y-4 border-b border-slate-100 pb-6">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Block schema reference</p>
                                <code class="mt-2 block rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-700">'datasource' => '{{ $selectedDatasource->slug }}'</code>
                            </div>

                            @can('manage datasources')
                                <form wire:submit="saveDatasource" class="space-y-4">
                                    <flux:field>
                                        <flux:label>Name</flux:label>
                                        <flux:input wire:model="editName" />
                                        <flux:error name="editName" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Slug</flux:label>
                                        <flux:input wire:model="editSlug" />
                                        <flux:error name="editSlug" />
                                    </flux:field>

                                    <div class="flex items-center justify-between gap-3">
                                        <flux:button
                                            type="button"
                                            wire:click="deleteDatasource"
                                            wire:confirm="Delete this datasource and all of its entries?"
                                            variant="ghost"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            Delete
                                        </flux:button>

                                        <flux:button type="submit" variant="primary">
                                            Save
                                        </flux:button>
                                    </div>
                                </form>
                            @else
                                <dl class="space-y-3 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">Name</dt>
                                        <dd class="font-medium text-slate-900">{{ $selectedDatasource->name }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">Slug</dt>
                                        <dd class="font-mono text-slate-900">{{ $selectedDatasource->slug }}</dd>
                                    </div>
                                </dl>
                            @endcan
                        </section>

                        <section class="space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Entries</h3>
                                <p class="mt-1 text-sm text-slate-500">Entry keys are stored in content. Labels are shown to editors.</p>
                            </div>

                            @can('manage datasources')
                                <form wire:submit="createEntry" class="space-y-3 rounded-lg border border-slate-200 p-3">
                                    <div class="grid grid-cols-1 gap-3">
                                        <flux:field>
                                            <flux:label>Key</flux:label>
                                            <flux:input wire:model="newEntryKey" placeholder="primary" />
                                            <flux:error name="newEntryKey" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Label</flux:label>
                                            <flux:input wire:model="newEntryValue" placeholder="Primary" />
                                            <flux:error name="newEntryValue" />
                                        </flux:field>
                                    </div>

                                    <div class="flex justify-end">
                                        <flux:button type="submit" variant="primary" size="sm">
                                            Add entry
                                        </flux:button>
                                    </div>
                                </form>
                            @endcan

                            <div class="space-y-2">
                                @forelse($entries as $entry)
                                    <div wire:key="datasource-entry-{{ $entry->id }}" class="rounded-lg border border-slate-200 p-3">
                                        @if($editingEntryId === $entry->id)
                                            <form wire:submit="saveEntry" class="space-y-3">
                                                <flux:field>
                                                    <flux:label>Key</flux:label>
                                                    <flux:input wire:model="editEntryKey" />
                                                    <flux:error name="editEntryKey" />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Label</flux:label>
                                                    <flux:input wire:model="editEntryValue" />
                                                    <flux:error name="editEntryValue" />
                                                </flux:field>

                                                <div class="flex justify-end gap-2">
                                                    <flux:button type="button" wire:click="cancelEntryEdit" variant="ghost" size="sm">Cancel</flux:button>
                                                    <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $entry->value['en'] ?? $entry->key }}</p>
                                                    <p class="mt-1 truncate font-mono text-xs text-slate-500">{{ $entry->key }}</p>
                                                </div>

                                                @can('manage datasources')
                                                    <div class="flex shrink-0 items-center gap-1">
                                                        <button type="button" wire:click="moveEntryUp({{ $entry->id }})" class="cms-iconbtn" aria-label="Move entry up" title="Move entry up">
                                                            <x-jaunt.icon name="arrow-up" size="xs" />
                                                        </button>
                                                        <button type="button" wire:click="moveEntryDown({{ $entry->id }})" class="cms-iconbtn" aria-label="Move entry down" title="Move entry down">
                                                            <x-jaunt.icon name="arrow-down" size="xs" />
                                                        </button>
                                                        <button type="button" wire:click="editEntry({{ $entry->id }})" class="cms-iconbtn" aria-label="Edit entry" title="Edit entry">
                                                            <x-jaunt.icon name="pencil" size="xs" />
                                                        </button>
                                                        <button type="button" wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Delete this entry?" class="cms-iconbtn cms-iconbtn-danger" aria-label="Delete entry" title="Delete entry">
                                                            <x-jaunt.icon name="trash-2" size="xs" />
                                                        </button>
                                                    </div>
                                                @endcan
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-slate-200 p-6 text-center">
                                        <p class="text-sm font-medium text-slate-700">No entries yet</p>
                                        <p class="mt-1 text-sm text-slate-500">Add entries to make this datasource available in select fields.</p>
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                @else
                    <div class="flex h-full items-center justify-center text-center">
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                <x-jaunt.icon name="database" size="lg" class="text-slate-500" />
                            </div>
                            <p class="mt-4 text-sm font-medium text-slate-800">Select a datasource</p>
                            <p class="mt-1 max-w-xs text-sm text-slate-500">Manage entries and copy the datasource slug for block schema fields.</p>
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>

    <flux:modal wire:model="showCreateModal">
        <div class="space-y-1">
            <flux:heading size="lg">Create datasource</flux:heading>
            <flux:text class="text-sm text-slate-500">Create a reusable option list for select fields.</flux:text>
        </div>

        <form wire:submit="createDatasource" class="mt-5 space-y-4">
            <flux:field>
                <flux:label>Space</flux:label>
                <flux:select wire:model="spaceId">
                    @foreach($spaces as $space)
                        <option value="{{ $space->id }}">{{ $space->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="spaceId" />
            </flux:field>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model.live="name" placeholder="CTA Styles" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="slug" placeholder="cta-styles" />
                <flux:error name="slug" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    Create datasource
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
