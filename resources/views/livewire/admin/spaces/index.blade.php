<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Spaces" subtitle="Organize content, assets, and datasources by project or site." top="0px" as="header" scroll-target="#spaces-list-scroll" aria-label="Page header">
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <a href="{{ route('admin.spaces.create') }}" wire:navigate class="cms-btn cms-btn-primary">
                <x-jaunt.icon name="plus" size="sm" />
                New space
            </a>
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="flex flex-1 min-h-0">
    <main id="spaces-list-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
        <flux:card class="overflow-hidden rounded-2xl">
            <flux:table>
                <flux:table.head>
                    <flux:table.row>
                        <flux:table.header>Name</flux:table.header>
                        <flux:table.header>Slug</flux:table.header>
                        <flux:table.header>Contents</flux:table.header>
                        <flux:table.header>Assets</flux:table.header>
                        <flux:table.header>Datasources</flux:table.header>
                        <flux:table.header>Created</flux:table.header>
                        <flux:table.header class="text-right">Actions</flux:table.header>
                    </flux:table.row>
                </flux:table.head>
                <flux:table.body>
                    @forelse($spaces as $space)
                        <flux:table.row class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell>
                                <div class="font-medium">{{ $space->name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <code class="text-sm">{{ $space->slug }}</code>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->contents_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->assets_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->datasources_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm text-muted-foreground">
                                    {{ $space->created_at->format('M d, Y') }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('admin.spaces.edit', $space) }}" wire:navigate size="sm" variant="ghost">
                                        <flux:icon.pencil class="size-4" />
                                        Edit
                                    </flux:button>
                                    <flux:button
                                        wire:click="deleteSpace({{ $space->id }})"
                                        wire:confirm="Are you sure you want to delete this space? This will delete all associated content, assets, and datasources."
                                        size="sm"
                                        variant="ghost"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <flux:icon.trash class="size-4" />
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-16">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                                        <flux:icon.squares-plus class="size-7 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <flux:heading size="sm" class="mt-4">No spaces yet</flux:heading>
                                    <flux:text class="mt-2 text-sm text-muted-foreground max-w-sm">Spaces organize your content, assets, and datasources. Create one to get started.</flux:text>
                                    <flux:button href="{{ route('admin.spaces.create') }}" variant="primary" class="mt-6" wire:navigate>
                                        <flux:icon.plus class="size-4" />
                                        Create space
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.body>
            </flux:table>
        </flux:card>
        </div>
    </main>
    </div>
</div>
