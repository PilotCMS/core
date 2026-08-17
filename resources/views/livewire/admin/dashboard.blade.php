<div class="cms-shell flex h-full w-full min-w-0 flex-col">
    @php
        $dashboardTitle = 'Good afternoon, '.str(auth()->user()->name)->before(' ');
        $dashboardSubtitle = "Here's what's happening across ".($space?->name ?? 'Pilot CMS').'.';
    @endphp
    <x-jaunt.shell.dynamic-header
        :title="$dashboardTitle"
        :subtitle="$dashboardSubtitle"
        top="0px"
        as="header"
        scroll-target="#dashboard-scroll"
        aria-label="Dashboard header"
    >
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <a href="{{ route('admin.assets.index') }}" wire:navigate class="cms-btn cms-btn-secondary">
                <x-jaunt.icon name="upload" size="xs" />
                Import
            </a>
            @can('create content')
                <a href="{{ route('admin.content.create', ['type' => 'page']) }}" wire:navigate class="cms-btn cms-btn-primary">
                    <x-jaunt.icon name="plus" size="xs" />
                    New page
                </a>
            @endcan
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="grid min-h-0 flex-1 grid-cols-1">
        <main id="dashboard-scroll" class="min-w-0 overflow-y-auto">
            <div class="cms-dashboard-body flex min-h-full flex-col gap-6 px-[var(--pad-view)] pb-10">
                <div class="cms-kpis">
                    <div class="cms-panel cms-kpi">
                        <div class="cms-kpi-label"><x-jaunt.icon name="files" size="xs" /> Pages</div>
                        <div class="cms-kpi-value">{{ number_format($pagesCount) }}</div>
                        <div class="cms-kpi-meta">Total pages</div>
                    </div>
                    <div class="cms-panel cms-kpi">
                        <div class="cms-kpi-label"><x-jaunt.icon name="image" size="xs" /> Assets</div>
                        <div class="cms-kpi-value">{{ number_format($assetsCount) }}</div>
                        <div class="cms-kpi-meta">Media library items</div>
                    </div>
                    <div class="cms-panel cms-kpi">
                        <div class="cms-kpi-label"><x-jaunt.icon name="globe-2" size="xs" /> Published</div>
                        <div class="cms-kpi-value">{{ number_format(max(0, $pagesCount - $draftsCount)) }}</div>
                        <div class="cms-kpi-meta cms-kpi-meta--success"><x-jaunt.icon name="trending-up" size="xs" /> {{ $pagesCount > 0 ? round((($pagesCount - $draftsCount) / $pagesCount) * 100) : 0 }}% of pages</div>
                    </div>
                    <div class="cms-panel cms-kpi">
                        <div class="cms-kpi-label"><x-jaunt.icon name="pencil-line" size="xs" /> Drafts</div>
                        <div class="cms-kpi-value">{{ number_format($draftsCount) }}</div>
                        <div class="cms-kpi-meta cms-kpi-meta--danger"><x-jaunt.icon name="trending-down" size="xs" /> {{ number_format($draftsCount) }} need review</div>
                    </div>
                </div>

                <section class="grid gap-4 lg:grid-cols-[minmax(0,1.55fr)_minmax(300px,1fr)]">
                    <div class="cms-panel">
                        <div class="cms-panel-head">
                            <h2 class="cms-panel-title">Recent activity</h2>
                            <a href="{{ route('admin.content.index') }}" wire:navigate class="cms-panel-link">View all</a>
                        </div>

                        <div class="cms-activity-body">
                            @if($recentActivities->isNotEmpty())
                                <div class="flex flex-col">
                                    @foreach($recentActivities->take(6) as $activity)
                                        <div class="cms-activity-row">
                                            <span class="cms-avatar">
                                                {{ $activity->user ? collect(explode(' ', $activity->user->name))->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('') : 'P' }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm text-primary">
                                                    <strong class="font-semibold">{{ $activity->user?->name ?? 'System' }}</strong>
                                                    {{ $activity->action }}
                                                    @if($activity->subject)
                                                        <span class="font-medium text-accent-text">{{ $activity->subject->name ?? 'Unknown' }}</span>
                                                    @else
                                                        {{ class_basename($activity->subject_type) }}
                                                    @endif
                                                </div>
                                                <time class="mt-0.5 block text-2xs text-tertiary">{{ $activity->created_at->diffForHumans() }}</time>
                                            </div>
                                            @php
                                                $activityBadgeClass = str_contains(strtolower($activity->action), 'publish')
                                                    ? 'cms-badge cms-badge-success'
                                                    : (str_contains(strtolower($activity->action), 'restore') ? 'cms-badge cms-badge-info' : 'cms-badge');
                                            @endphp
                                            <span class="{{ $activityBadgeClass }}">{{ str($activity->action)->headline() }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="cms-tile !h-14 !w-14 !rounded-lg"><x-jaunt.icon name="zap" size="lg" /></div>
                                    <h3 class="mt-4 text-sm font-semibold text-primary">No recent activity</h3>
                                    <p class="cms-subtitle">Activity from you and your team will appear here.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="cms-panel">
                            <div class="cms-panel-head"><h2 class="cms-panel-title">Needs attention</h2></div>
                            <div class="divide-y divide-subtle">
                                <a href="{{ route('admin.content.index', ['type' => 'page']) }}" wire:navigate class="flex items-center gap-3 p-4 transition-colors hover:bg-hover">
                                    <span class="cms-tile cms-tile-info"><x-jaunt.icon name="list-checks" size="sm" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-primary">Awaiting review</span>
                                        <span class="block text-2xs text-tertiary">Pages ready for an editorial decision</span>
                                    </span>
                                    <span class="cms-badge {{ $reviewsCount ? 'cms-badge-warning' : '' }}">{{ number_format($reviewsCount) }}</span>
                                </a>
                                <a href="{{ route('admin.content.index', ['type' => 'page']) }}" wire:navigate class="flex items-center gap-3 p-4 transition-colors hover:bg-hover">
                                    <span class="cms-tile"><x-jaunt.icon name="circle-alert" size="sm" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-primary">Changes requested</span>
                                        <span class="block text-2xs text-tertiary">Pages returned to their authors</span>
                                    </span>
                                    <span class="cms-badge {{ $changesRequestedCount ? 'cms-badge-warning' : '' }}">{{ number_format($changesRequestedCount) }}</span>
                                </a>
                                <div class="flex items-center gap-3 p-4">
                                    <span class="cms-tile"><x-jaunt.icon name="calendar-days" size="sm" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-primary">Scheduled</span>
                                        <span class="block text-2xs text-tertiary">Upcoming automatic publications</span>
                                    </span>
                                    <span class="cms-badge">{{ number_format($scheduledCount) }}</span>
                                </div>
                                <a href="{{ route('admin.assets.index', ['type' => 'expired']) }}" wire:navigate class="flex items-center gap-3 p-4 transition-colors hover:bg-hover">
                                    <span class="cms-tile"><x-jaunt.icon name="copyright" size="sm" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-primary">Expired rights</span>
                                        <span class="block text-2xs text-tertiary">Assets that need rights review</span>
                                    </span>
                                    <span class="cms-badge {{ $expiredAssetsCount ? 'cms-badge-warning' : '' }}">{{ number_format($expiredAssetsCount) }}</span>
                                </a>
                            </div>
                        </div>

                        <div
                            id="dashboard-jaunt-insight"
                            x-data="{ visible: false }"
                            x-show="visible"
                            x-on:pilot-ai-toggle.window="visible = $event.detail.enabled"
                            x-cloak
                            class="cms-panel p-4"
                        >
                            <div class="flex items-center gap-2 text-sm font-semibold text-primary">
                                <x-jaunt.icon name="sparkles" size="xs" class="text-ai" />
                                Jaunt insight
                            </div>
                            <p class="mt-2 text-sm text-secondary">Draft volume is {{ number_format($draftsCount) }}. Review recently edited pages before the next publishing window.</p>
                            <a href="{{ route('admin.content.index', ['type' => 'draft']) }}" wire:navigate class="cms-btn cms-btn-secondary mt-4 !h-control-sm">
                                <x-jaunt.icon name="list-checks" size="xs" />
                                Review drafts
                            </a>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-primary">Continue editing</h2>
                        <a href="{{ route('admin.content.index') }}" wire:navigate class="text-sm font-medium text-accent-text">View all content</a>
                    </div>

                    @if($recentPages->isNotEmpty())
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            @foreach($recentPages as $page)
                                <a href="{{ route('admin.content.editor', $page) }}" wire:navigate class="cms-panel block transition-shadow hover:shadow-md">
                                    <div class="flex items-center gap-3 border-b border-subtle p-4">
                                        <span class="cms-tile cms-tile-accent"><x-jaunt.icon name="file-text" size="sm" /></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-medium text-primary">{{ $page->name }}</div>
                                            <div class="mt-0.5 truncate font-mono text-2xs text-tertiary">/{{ $page->slug }}</div>
                                        </div>
                                        <span class="{{ $page->status === 'published' ? 'cms-badge cms-badge-success' : 'cms-badge' }}">{{ ucfirst($page->status) }}</span>
                                    </div>
                                    <dl class="px-4 py-3 text-sm">
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-tertiary">Updated</dt>
                                            <dd class="text-primary">{{ $page->updated_at->format('M j, Y') }}</dd>
                                        </div>
                                    </dl>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="cms-panel flex flex-col items-center justify-center py-16 text-center">
                            <div class="cms-tile !h-14 !w-14 !rounded-lg"><x-jaunt.icon name="file-text" size="lg" /></div>
                            <h3 class="mt-4 text-sm font-semibold text-primary">No recent pages</h3>
                            <p class="cms-subtitle">Pages you've edited recently will appear here.</p>
                        </div>
                    @endif
                </section>
            </div>
        </main>

    </div>
</div>
