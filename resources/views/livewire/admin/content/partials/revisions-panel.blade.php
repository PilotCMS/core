<div class="grid min-h-full gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]">
    <div class="min-h-0 space-y-4">
        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <label class="mb-1.5 block text-xs font-semibold text-slate-600">Checkpoint label</label>
            <div class="flex gap-2">
                <input type="text" wire:model="checkpointLabel" placeholder="e.g. Before homepage rewrite" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                <button type="button" wire:click="saveCheckpoint" class="cms-btn cms-btn-sm cms-btn-primary shrink-0">Save</button>
            </div>
            @error('checkpointLabel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
            <div class="mb-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Filters</div>
            <div class="grid grid-cols-2 gap-2">
                <select wire:model.live="revisionTypeFilter" class="rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All types</option>
                    @foreach($this->revisionTypeOptions as $revisionType)
                        <option value="{{ $revisionType }}">{{ str_replace('_', ' ', $revisionType) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="revisionAuthorFilter" class="rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All authors</option>
                    @foreach($this->revisionAuthorOptions as $revisionAuthor)
                        <option value="{{ $revisionAuthor->id }}">{{ $revisionAuthor->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="min-h-0 rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-600">History</span>
                <span class="text-[11px] font-medium text-slate-400">{{ $this->revisions->count() }} of {{ $this->revisionTotalCount }}</span>
            </div>
            <div class="p-2">
                <div class="space-y-2">
                    @forelse($this->revisions as $revision)
                        <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 {{ $this->selectedRevisionId === $revision->id ? 'bg-blue-50 ring-1 ring-blue-200' : 'hover:bg-slate-50' }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-medium text-slate-700">{{ $revision->label ?? 'Revision' }}</span>
                                    <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $revision->revision_type ?? 'manual') }}</span>
                                </div>
                                <span class="block text-xs text-slate-400">{{ $revision->created_at->diffForHumans() }} · {{ $revision->user?->name ?? 'System' }}</span>
                                @if($revision->sourceRevision)
                                    <span class="block text-xs text-slate-400">From {{ $revision->sourceRevision->label ?? 'revision #'.$revision->sourceRevision->id }}</span>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" wire:click="selectRevision({{ $revision->id }})" class="cms-text-btn">Inspect</button>
                                <button type="button" wire:click="restoreRevision({{ $revision->id }})" wire:confirm="Restore {{ $revision->label ?? 'this revision' }} from {{ $revision->created_at->toDayDateTimeString() }}? A rollback checkpoint will be created first." class="cms-text-btn">Restore</button>
                            </div>
                        </div>
                    @empty
                        <p class="px-3 py-2 text-sm text-slate-400">No revisions yet.</p>
                    @endforelse
                </div>
                @if($this->revisionTotalCount > $this->revisions->count())
                    <button type="button" wire:click="loadMoreRevisions" class="cms-btn cms-btn-secondary mt-3 w-full">Load more revisions</button>
                @endif
            </div>
        </div>
    </div>

    <div class="min-h-0">
        @if($this->selectedRevision && $this->selectedRevisionComparison)
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-800">{{ $this->selectedRevision->label ?? 'Revision' }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">{{ $this->selectedRevision->created_at->format('M j, Y g:i A') }} · {{ $this->selectedRevision->user?->name ?? 'System' }}</div>
                        <div class="mt-1 inline-flex items-center gap-1 rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700">
                            <x-jaunt.icon name="search" size="sm" />
                            Inspecting revision
                        </div>
                    </div>
                    <button type="button" wire:click="clearSelectedRevision" class="cms-iconbtn shrink-0" aria-label="Close revision details" title="Close revision details">
                        <x-jaunt.icon name="x" size="sm" />
                    </button>
                </div>
                <div class="mt-3 rounded-md bg-white p-2">
                    <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Compare against</label>
                    <select wire:model.live="compareRevisionId" class="mt-1 w-full rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">Current draft</option>
                        @foreach($this->comparisonRevisionOptions as $comparisonRevision)
                            <option value="{{ $comparisonRevision->id }}">{{ $comparisonRevision->label ?? 'Revision #'.$comparisonRevision->id }} · {{ $comparisonRevision->created_at->format('M j') }}</option>
                        @endforeach
                    </select>
                </div>

                @if($this->selectedRevisionComparison['has_changes'])
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-md bg-white p-2">
                            <div class="text-base font-bold text-slate-800">{{ count($this->selectedRevisionComparison['content_changes']) }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Fields</div>
                        </div>
                        <div class="rounded-md bg-white p-2">
                            <div class="text-base font-bold text-slate-800">{{ $this->selectedRevisionComparison['block_summary']['revision_total'] }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Blocks</div>
                        </div>
                        <div class="rounded-md bg-white p-2">
                            <div class="text-base font-bold text-slate-800">{{ $this->selectedRevisionComparison['block_summary']['changed'] + $this->selectedRevisionComparison['block_summary']['added'] + $this->selectedRevisionComparison['block_summary']['removed'] }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Block diffs</div>
                        </div>
                    </div>

                    @if(count($this->selectedRevisionComparison['content_changes']) > 0)
                        <div class="mt-3 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Changed fields</div>
                            @foreach($this->selectedRevisionComparison['content_changes'] as $change)
                                <div class="rounded-md bg-white p-2 text-xs">
                                    <div class="font-semibold text-slate-700">{{ $change['label'] }}</div>
                                    <div class="mt-1 grid grid-cols-2 gap-2 text-slate-500">
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-slate-400">Current</div>
                                            <div class="break-words">{{ $change['current'] }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-slate-400">Revision</div>
                                            <div class="break-words">{{ $change['revision'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(count($this->selectedRevisionComparison['block_changes']) > 0)
                        <div class="mt-3 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Block changes</div>
                            @foreach(array_slice($this->selectedRevisionComparison['block_changes'], 0, 8) as $change)
                                <div class="flex items-start justify-between gap-3 rounded-md bg-white p-2 text-xs">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $change['action'] === 'added' ? 'bg-green-50 text-green-700' : ($change['action'] === 'removed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ $change['action'] }}</span>
                                            <span class="font-semibold text-slate-700">{{ $change['label'] }}</span>
                                        </div>
                                        @if(count($change['fields']) > 0)
                                            <div class="mt-1 text-slate-500">Changed {{ implode(', ', $change['fields']) }}</div>
                                        @endif
                                        @if(count($change['field_changes']) > 0)
                                            <div class="mt-2 space-y-1.5">
                                                @foreach(array_slice($change['field_changes'], 0, 3) as $fieldChange)
                                                    <div class="rounded border border-slate-100 bg-slate-50 p-2">
                                                        <div class="font-semibold text-slate-700">{{ $fieldChange['label'] }}</div>
                                                        <div class="mt-1 grid grid-cols-2 gap-2 text-slate-500">
                                                            <div>
                                                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Current</div>
                                                                <div class="break-words">{{ $fieldChange['current'] }}</div>
                                                            </div>
                                                            <div>
                                                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Revision</div>
                                                                <div class="break-words">{{ $fieldChange['revision'] }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                @if(count($change['field_changes']) > 3)
                                                    <div class="text-slate-400">And {{ count($change['field_changes']) - 3 }} more field changes.</div>
                                                @endif
                                            </div>
                                        @endif
                                        @if($change['action'] !== 'removed')
                                            <button type="button" wire:click="restoreSelectedRevisionBlock('{{ $change['path'] }}')" wire:confirm="Restore this block from the selected revision? A rollback checkpoint will be created first." class="cms-btn cms-btn-sm cms-btn-secondary mt-2">Restore this block</button>
                                        @endif
                                    </div>
                                    <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">{{ $change['path'] }}</span>
                                </div>
                            @endforeach
                            @if(count($this->selectedRevisionComparison['block_changes']) > 8)
                                <div class="rounded-md bg-white p-2 text-xs text-slate-500">And {{ count($this->selectedRevisionComparison['block_changes']) - 8 }} more block changes.</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-3 rounded-md bg-white p-2 text-xs text-slate-600">
                        Comparing against {{ $this->selectedRevisionComparison['base_label'] }}. Restoring fully will replace the current block tree of {{ $this->selectedRevisionComparison['block_summary']['current_total'] }} blocks with {{ $this->selectedRevisionComparison['block_summary']['revision_total'] }} blocks from this revision.
                    </div>
                @else
                    <div class="mt-3 rounded-md bg-white p-3 text-sm font-medium text-slate-600">This revision matches the current draft.</div>
                @endif

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="restoreSelectedRevisionContent" wire:confirm="Restore only page fields from this revision? A rollback checkpoint will be created first." class="cms-btn cms-btn-secondary">Restore fields</button>
                    <button type="button" wire:click="restoreRevision({{ $this->selectedRevision->id }})" wire:confirm="Restore {{ $this->selectedRevision->label ?? 'this revision' }} from {{ $this->selectedRevision->created_at->toDayDateTimeString() }}? A rollback checkpoint will be created first." class="cms-btn cms-btn-primary">Restore all</button>
                </div>
            </div>
        @else
            <div class="flex min-h-80 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
                <div>
                    <x-jaunt.icon name="layers-3" size="lg" class="text-slate-300" />
                    <div class="mt-2 text-sm font-semibold text-slate-600">Select a revision</div>
                    <div class="mt-1 text-xs text-slate-400">Inspect, compare, or restore from history.</div>
                </div>
            </div>
        @endif
    </div>
</div>
