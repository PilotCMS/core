<div class="cms-shell flex h-full w-full min-w-0 flex-col">
    <x-jaunt.shell.dynamic-header
        title="Activity"
        subtitle="A chronological history of changes across Pilot."
        top="0px"
        as="header"
        scroll-target="#activity-list-scroll"
        aria-label="Activity header"
    />

    <main id="activity-list-scroll" class="min-h-0 min-w-0 flex-1 overflow-y-auto">
        <div class="flex min-h-full flex-col gap-4 px-[var(--pad-view)] pb-10 pt-1">
            <div class="cms-toolbar">
                <label class="cms-input w-full max-w-sm">
                    <x-jaunt.icon name="search" size="sm" class="text-tertiary" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search people, actions, or pages" aria-label="Search activity" />
                </label>

                <div class="relative ml-auto">
                    <select wire:model.live="actionFilter" class="cms-select min-w-44" aria-label="Filter by action">
                        <option value="all">All actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ str($action)->headline() }}</option>
                        @endforeach
                    </select>
                    <x-jaunt.icon name="chevron-down" size="sm" class="pointer-events-none absolute right-2 top-1.5 text-tertiary" />
                </div>
            </div>

            <section class="cms-panel" aria-label="Activity history">
                <div class="cms-panel-head">
                    <div>
                        <h2 class="cms-panel-title">Recent changes</h2>
                        <p class="mt-0.5 text-2xs text-tertiary">{{ number_format($activities->total()) }} {{ str('event')->plural($activities->total()) }}</p>
                    </div>
                </div>

                <div class="divide-y divide-subtle">
                    @forelse($activities as $activity)
                        @php
                            $subject = $activity->subject;
                            $subjectName = $subject?->name
                                ?? $subject?->display_name
                                ?? $subject?->filename
                                ?? $subject?->key
                                ?? $activity->meta['subject_name']
                                ?? class_basename($activity->subject_type);
                            $subjectUrl = null;
                            $contextName = null;
                            $contextUrl = null;

                            if ($subject instanceof \Pilot\Core\Models\Content && $subject->isPage()) {
                                $subjectUrl = route('admin.content.editor', $subject);
                            } elseif ($subject instanceof \Pilot\Core\Models\Block) {
                                $subjectName = ($subject->blockType?->name ?? str($subject->type)->headline()).' block';
                                $contextName = $subject->content?->name;
                                $contextUrl = $subject->content?->isPage()
                                    ? route('admin.content.editor', $subject->content)
                                    : null;
                            } elseif ($subject instanceof \Pilot\Core\Models\BlockType) {
                                $subjectUrl = route('admin.blocks.edit', $subject);
                            }

                            $activityDetails = collect($activity->meta ?? [])
                                ->except('subject_name')
                                ->map(fn ($value) => is_array($value) ? json_encode($value) : $value)
                                ->filter(fn ($value) => $value !== null && $value !== '');
                            $actionClass = str_contains(strtolower($activity->action), 'publish')
                                ? 'cms-badge cms-badge-success'
                                : (str_contains(strtolower($activity->action), 'restore') ? 'cms-badge cms-badge-info' : 'cms-badge');
                        @endphp

                        <article class="flex gap-4 p-4 sm:p-5" wire:key="activity-{{ $activity->id }}">
                            <span class="cms-avatar mt-0.5 shrink-0">
                                {{ $activity->user ? collect(explode(' ', $activity->user->name))->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('') : 'P' }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                    <p class="text-sm leading-6 text-primary">
                                        <strong class="font-semibold">{{ $activity->user?->name ?? 'System' }}</strong>
                                        {{ $activity->action }}
                                        @if($subjectUrl)
                                            <a href="{{ $subjectUrl }}" wire:navigate class="font-medium text-accent-text hover:underline">{{ $subjectName }}</a>
                                        @else
                                            <span class="font-medium text-accent-text">{{ $subjectName }}</span>
                                        @endif
                                        @if($contextName)
                                            on
                                            @if($contextUrl)
                                                <a href="{{ $contextUrl }}" wire:navigate class="font-medium text-accent-text hover:underline">{{ $contextName }}</a>
                                            @else
                                                <span class="font-medium">{{ $contextName }}</span>
                                            @endif
                                        @endif
                                    </p>
                                    <span class="{{ $actionClass }} shrink-0">{{ str($activity->action)->headline() }}</span>
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-2xs text-tertiary">
                                    <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->toDayDateTimeString() }}">
                                        {{ $activity->created_at->format('M j, Y \a\t g:i A') }}
                                    </time>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $activity->created_at->diffForHumans() }}</span>
                                    @if($activity->space)
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $activity->space->name }}</span>
                                    @endif
                                    <span aria-hidden="true">·</span>
                                    <span>{{ class_basename($activity->subject_type) }}</span>
                                </div>

                                @if($activityDetails->isNotEmpty())
                                    <dl class="mt-3 flex flex-wrap gap-2">
                                        @foreach($activityDetails as $key => $value)
                                            <div class="rounded-md border border-subtle bg-sunken px-2 py-1 text-2xs">
                                                <dt class="inline font-medium text-secondary">{{ str($key)->headline() }}:</dt>
                                                <dd class="inline text-tertiary">{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-col items-center justify-center px-4 py-20 text-center">
                            <div class="cms-tile !h-14 !w-14 !rounded-lg"><x-jaunt.icon name="history" size="lg" /></div>
                            <h3 class="mt-4 text-sm font-semibold text-primary">No activity found</h3>
                            <p class="cms-subtitle">Try changing your search or action filter.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if($activities->hasPages())
                <div>{{ $activities->links() }}</div>
            @endif
        </div>
    </main>
</div>
