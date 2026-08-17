@php
    $workspaceItems = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.content.index', 'active' => 'admin.content.*', 'icon' => 'files', 'label' => 'Content'],
        ['route' => 'admin.assets.index', 'active' => 'admin.assets.*', 'icon' => 'image', 'label' => 'Assets'],
        ['route' => 'admin.blocks.index', 'active' => 'admin.blocks.*', 'icon' => 'boxes', 'label' => 'Blocks'],
        ['route' => 'admin.content-types.index', 'active' => 'admin.content-types.*', 'icon' => 'panels-top-left', 'label' => 'Content types'],
        ['route' => 'admin.datasources.index', 'active' => 'admin.datasources.*', 'icon' => 'database', 'label' => 'Datasources'],
    ];

    $adminItems = [
        ['route' => 'admin.spaces.index', 'active' => 'admin.spaces.*', 'icon' => 'layers-3', 'label' => 'Spaces'],
        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users', 'label' => 'Users', 'can' => 'manage users'],
        ['route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'settings', 'label' => 'Settings'],
    ];

    $navLinkClasses = function (string $activePattern): string {
        $base = 'cms-nav-item group flex items-center gap-2 px-[9px] text-[13px] leading-[19.5px] tracking-[-0.154px] transition-colors duration-100';

        return request()->routeIs($activePattern)
            ? $base . ' cms-nav-item--active bg-selected text-primary font-medium'
            : $base . ' cms-nav-item--inactive hover:bg-hover hover:text-primary';
    };
@endphp

<nav
    x-data="{ workspaceOpen: true, adminOpen: true }"
    class="cms-sidebar h-full min-h-0 bg-card flex flex-col shrink-0 z-sidebar"
    aria-label="Main"
    style="width: var(--admin-nav-width);"
>
    <div class="flex h-[46px] items-center border-b border-subtle pl-3 pr-2">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2 rounded-sm px-0 py-1 text-left transition-colors duration-100 hover:bg-hover" wire:navigate title="Pilot CMS">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-sm bg-accent text-on-accent shadow-xs">
                <img src="{{ asset('img/logo.svg') }}" alt="" class="h-[15px] w-[15px] object-contain" />
            </span>
            <span class="min-w-0 flex-1 text-[13px] font-medium leading-[15px] tracking-[-0.154px] text-primary">
                <span class="block truncate">Pilot CMS</span>
                <span class="block truncate text-[10px] font-normal leading-3 text-tertiary">Jaunt workspace</span>
            </span>
            <x-jaunt.icon name="chevrons-up-down" size="xs" class="text-tertiary" />
        </a>
    </div>

    <div class="cms-nav-search-wrap px-2">
        <button type="button" x-data x-on:click="$dispatch('open-command-palette')" class="cms-nav-search flex w-full items-center gap-2 rounded-sm px-[9px] text-[13px] leading-[19.5px] tracking-[-0.154px] text-secondary transition-colors duration-100 hover:bg-hover hover:text-primary">
            <x-jaunt.icon name="search" size="sm" class="!h-[15px] !w-[15px]" />
            <span>Search</span>
            <kbd class="ml-auto rounded-xs border border-subtle bg-sunken px-1.5 py-0.5 font-mono text-2xs text-tertiary">⌘K</kbd>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto pb-3">
        <button type="button" class="cms-nav-section" x-on:click="workspaceOpen = ! workspaceOpen" x-bind:aria-expanded="workspaceOpen">
            <x-jaunt.icon name="chevron-down" size="xs" x-bind:class="workspaceOpen ? '' : '-rotate-90'" />
            <span>Workspace</span>
        </button>
        <div x-show="workspaceOpen" class="px-2">
            @foreach ($workspaceItems as $item)
                @php $isActive = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="{{ $navLinkClasses($item['active']) }}" wire:navigate>
                    <x-jaunt.icon :name="$item['icon']" size="sm" class="!h-[15px] !w-[15px] {{ $isActive ? 'text-primary' : 'text-tertiary group-hover:text-secondary' }}" />
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <button type="button" class="cms-nav-section" x-on:click="adminOpen = ! adminOpen" x-bind:aria-expanded="adminOpen">
            <x-jaunt.icon name="chevron-down" size="xs" x-bind:class="adminOpen ? '' : '-rotate-90'" />
            <span>Admin</span>
        </button>
        <div x-show="adminOpen" class="px-2">
            @foreach ($adminItems as $item)
                @continue(isset($item['can']) && auth()->user()->cannot($item['can']))
                @php $isActive = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="{{ $navLinkClasses($item['active']) }}" wire:navigate>
                    <x-jaunt.icon :name="$item['icon']" size="sm" class="!h-[15px] !w-[15px] {{ $isActive ? 'text-primary' : 'text-tertiary group-hover:text-secondary' }}" />
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="border-t border-subtle p-2">
        <flux:dropdown position="top" align="start">
            <button type="button" class="flex w-full items-center gap-2 rounded-sm px-[9px] py-1.5 text-left transition-colors duration-100 hover:bg-hover" aria-label="User menu">
                <span class="relative inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-subtle text-xs font-semibold text-accent-text">
                    {{ str(auth()->user()->name)->trim()->substr(0, 1)->upper() }}
                    <span class="absolute -bottom-px -right-px h-2 w-2 rounded-full border-2 border-app bg-success"></span>
                </span>
                <span class="min-w-0 flex-1 text-[13px] font-medium leading-[15px] tracking-[-0.154px] text-primary">
                    <span class="block truncate">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-2xs font-normal text-tertiary">{{ auth()->user()->email }}</span>
                </span>
                <x-jaunt.icon name="settings" size="xs" class="text-tertiary" />
            </button>
            <flux:menu>
                <div class="p-2 text-sm">
                    <div class="font-medium text-primary">{{ auth()->user()->name }}</div>
                    <div class="text-tertiary text-xs">{{ auth()->user()->email }}</div>
                </div>
                <flux:menu.separator />
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">{{ __('Log Out') }}</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</nav>
