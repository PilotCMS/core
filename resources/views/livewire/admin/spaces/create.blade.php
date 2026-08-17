<div class="cms-drawer-page flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Create Space" subtitle="Set up a new workspace." top="0px" as="header" scroll-target="#space-create-scroll" aria-label="Page header" />

    <div class="flex flex-1 min-h-0">

    <main id="space-create-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            <div class="max-w-2xl">
                <div class="mb-6">
                    <a href="{{ route('admin.spaces.index') }}" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 mb-4 transition-colors" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Spaces
                    </a>
                </div>

                <flux:card>
                    <form wire:submit="save" class="space-y-6">
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

                        <div class="flex items-center justify-end gap-3">
                            <flux:button href="{{ route('admin.spaces.index') }}" wire:navigate variant="ghost">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Create Space</span>
                                <span wire:loading wire:target="save">Creating…</span>
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            </div>
        </div>
    </main>

    <aside class="cms-drawer" aria-label="Details">
        <div class="cms-drawer-header">
            <h2 class="cms-drawer-title">Details</h2>
        </div>
        <div class="cms-drawer-body flex items-center justify-center text-sm text-secondary">
            <p>Define a name and slug for your space.</p>
        </div>
    </aside>
    </div>
</div>
