<div class="cms-drawer-page flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Edit Space" subtitle="Update workspace settings." top="0px" as="header" scroll-target="#space-edit-scroll" aria-label="Page header" />

    <div class="flex flex-1 min-h-0">

    <main id="space-edit-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            <div class="max-w-2xl">
                <div class="mb-6">
                    <a href="{{ route('admin.spaces.index') }}" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 mb-4 transition-colors" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Spaces
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                <flux:card>
                    <div class="space-y-6">
                        <flux:field>
                            <flux:label>Name</flux:label>
                            <flux:input wire:model="name" placeholder="My Space" />
                            <flux:error name="name" />
                            <flux:description>The display name for this space</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Slug</flux:label>
                            <flux:input wire:model="slug" placeholder="my-space" />
                            <flux:error name="slug" />
                            <flux:description>Used in URLs and API endpoints</flux:description>
                        </flux:field>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="space-y-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="md">Preview URLs</flux:heading>
                                <flux:text class="mt-1 text-sm text-slate-500">Add live frontend URLs that editors can preview against.</flux:text>
                            </div>
                            <flux:button type="button" wire:click="addPreviewTarget" variant="ghost" size="sm">
                                <flux:icon.plus class="size-4" />
                                Add URL
                            </flux:button>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <x-jaunt.icon name="key" size="sm" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-amber-950">Frontend preview secret</p>
                                    <p class="mt-1 text-sm text-amber-900">Copy this line into the <span class="font-mono">.env</span> file for every frontend Laravel app that should render live previews for this space, then run <span class="font-mono">php artisan optimize:clear</span> in that frontend app.</p>
                                    <div class="mt-3 rounded-md border border-amber-200 bg-white p-3">
                                        <code class="break-all text-xs font-semibold text-slate-900">PILOT_PREVIEW_SECRET={{ $previewSecret }}</code>
                                    </div>
                                    <p class="mt-2 text-xs text-amber-800">Pilot uses this same value to sign preview links. If the frontend app has a different value, preview URLs will return 403.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse($previewTargets as $index => $target)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1.5fr_auto]">
                                        <flux:field>
                                            <flux:label>Name</flux:label>
                                            <flux:input wire:model="previewTargets.{{ $index }}.name" placeholder="Production" />
                                            <flux:error name="previewTargets.{{ $index }}.name" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>URL</flux:label>
                                            <flux:input wire:model="previewTargets.{{ $index }}.url" placeholder="https://mysite.test" />
                                            <flux:error name="previewTargets.{{ $index }}.url" />
                                        </flux:field>

                                        <div class="flex items-end gap-2">
                                            <button type="button" wire:click="markDefaultPreviewTarget({{ $index }})" class="cms-btn cms-btn-secondary" aria-pressed="{{ ! empty($target['is_default']) ? 'true' : 'false' }}">
                                                Default
                                            </button>
                                            <button type="button" wire:click="removePreviewTarget({{ $index }})" class="cms-iconbtn cms-iconbtn-danger" aria-label="Remove preview URL">
                                                <x-jaunt.icon name="trash-2" size="sm" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border-2 border-dashed border-slate-200 p-8 text-center">
                                    <p class="text-sm font-medium text-slate-700">No preview URLs configured</p>
                                    <p class="mt-1 text-sm text-slate-500">Add Production, Staging, or Local URLs for live previews.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </flux:card>

                <div class="flex items-center justify-end gap-3">
                    <flux:button href="{{ route('admin.spaces.index') }}" wire:navigate variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </flux:button>
                </div>
                </form>
            </div>
        </div>
    </main>

    <aside class="cms-drawer" aria-label="Details">
        <div class="cms-drawer-header">
            <h2 class="cms-drawer-title">Details</h2>
        </div>
        <div class="cms-drawer-body text-sm text-secondary">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preview URL format</p>
                    <p class="mt-2">Pilot will open the selected frontend URL at:</p>
                    <p class="mt-2 rounded bg-slate-50 p-2 font-mono text-xs text-slate-700">/_pilot/preview/{content}</p>
                </div>
                <p>The frontend app must have the <span class="font-mono">pilot/laravel</span> package installed and share the same preview secret.</p>
                <p>After copying the secret to the frontend app, open a fresh preview URL from the page editor. Older links may have been signed with a previous value.</p>
            </div>
        </div>
    </aside>
    </div>
</div>
