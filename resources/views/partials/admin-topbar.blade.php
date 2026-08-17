@php
    $workspaceName = 'Pilot CMS';

    $currentLabel = match (true) {
        request()->routeIs('admin.dashboard') => 'Dashboard',
        request()->routeIs('admin.content.index') => 'Content',
        request()->routeIs('admin.content.create') => 'New content',
        request()->routeIs('admin.content-types.*') => 'Content types',
        request()->routeIs('admin.blocks.create') => 'New block type',
        request()->routeIs('admin.blocks.edit') => 'Edit block type',
        request()->routeIs('admin.blocks.*') => 'Blocks',
        request()->routeIs('admin.assets.*') => 'Assets',
        request()->routeIs('admin.datasources.*') => 'Datasources',
        request()->routeIs('admin.spaces.create') => 'New space',
        request()->routeIs('admin.spaces.edit') => 'Edit space',
        request()->routeIs('admin.spaces.*') => 'Spaces',
        request()->routeIs('admin.users.*') => 'Users',
        request()->routeIs('admin.settings.*') => 'Settings',
        default => 'Dashboard',
    };
@endphp

<header class="cms-global-topbar" aria-label="Application toolbar">
    <nav class="cms-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}" wire:navigate>{{ $workspaceName }}</a>
        <x-jaunt.icon name="chevron-right" size="xs" />
        <span aria-current="page">{{ $currentLabel }}</span>
    </nav>

    <div class="cms-global-actions" x-data="{ aiEnabled: false }">
        @if(request()->routeIs('admin.dashboard'))
            <span class="cms-ai-label">AI</span>
            <button
                type="button"
                class="cms-ai-switch"
                role="switch"
                x-bind:aria-checked="aiEnabled"
                x-on:click="aiEnabled = ! aiEnabled; $dispatch('pilot-ai-toggle', { enabled: aiEnabled })"
                aria-label="Toggle AI features"
            >
                <span x-bind:class="aiEnabled ? 'translate-x-2.5' : 'translate-x-0'"></span>
            </button>

            <button
                type="button"
                class="cms-iconbtn"
                aria-label="Ask Jaunt"
                title="Ask Jaunt"
                x-on:click="aiEnabled = true; $dispatch('pilot-ai-toggle', { enabled: true }); setTimeout(() => document.getElementById('dashboard-jaunt-insight')?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50)"
            >
                <x-jaunt.icon name="sparkles" size="sm" />
            </button>
        @endif

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-command-palette')"
            class="cms-iconbtn"
            aria-label="Search"
            title="Search (⌘K)"
        >
            <x-jaunt.icon name="search" size="sm" />
        </button>

        <button type="button" class="cms-iconbtn" aria-label="Notifications" title="Notifications">
            <x-jaunt.icon name="bell" size="sm" />
        </button>

        <button
            type="button"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            x-on:click="$flux.appearance = dark ? 'light' : 'dark'"
            x-on:pilot-theme-changed.window="dark = $event.detail.isDark"
            x-on:livewire:navigated.window="dark = document.documentElement.classList.contains('dark')"
            class="cms-iconbtn"
            x-bind:aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
            x-bind:title="dark ? 'Light mode' : 'Dark mode'"
        >
            <span x-show="dark"><x-jaunt.icon name="sun" size="sm" /></span>
            <span x-show="! dark"><x-jaunt.icon name="moon" size="sm" /></span>
        </button>
    </div>
</header>
