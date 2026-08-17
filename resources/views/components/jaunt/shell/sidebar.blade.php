{{--
    jaunt.shell.sidebar — the universal left nav every workspace inherits.
    Ported from ui_kits-source/app/Shell.jsx (Sidebar) + app.css (.sb*).

    workspace: { name, plan, initial }
    user: { name, role, avatarSrc? }
    navItems: [{ id, label, icon, count? }]
    pinned: [{ id, label, icon }]
    active: string — id of the active nav item
--}}
@props([
    'workspace',
    'user',
    'navItems' => [],
    'pinned' => [],
    'active' => null,
])

<aside class="flex flex-col min-h-0 w-sidebar shrink-0 border-r border-subtle bg-app">
    <div class="flex items-center gap-2 h-topbar pl-3.5 pr-2.5">
        <button
            type="button"
            class="flex items-center gap-2.5 flex-1 min-w-0 -mx-0.5 px-1.5 py-1.5 rounded-sm text-left
                transition-colors duration-instant hover:bg-hover"
        >
            <span class="w-[26px] h-[26px] shrink-0 rounded-sm bg-accent text-on-accent grid place-items-center font-bold text-sm">
                {{ $workspace['initial'] }}
            </span>
            <span class="flex-1 min-w-0 text-sm font-medium truncate">
                {{ $workspace['name'] }}
                <small class="block text-2xs font-normal text-tertiary truncate">{{ $workspace['plan'] }}</small>
            </span>
            <x-jaunt.icon name="chevrons-up-down" size="sm" class="text-tertiary" />
        </button>
    </div>

    <div class="mx-3 mb-2 mt-1">
        <button
            type="button"
            @click="$store.commandPalette.open = true"
            class="flex items-center gap-2 w-full h-[30px] px-2.5 rounded-sm border text-sm text-tertiary
                transition-colors duration-instant hover:border-strong"
        >
            <x-jaunt.icon name="search" size="sm" />
            Search…
            <kbd class="ml-auto font-mono text-2xs border rounded-xs px-1.5 py-0.5">⌘K</kbd>
        </button>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto px-2 pb-3">
        <div class="pt-0.5 pb-1 px-2 text-2xs font-semibold uppercase tracking-wide text-tertiary">Workspaces</div>
        @foreach ($navItems as $item)
            @php $isActive = $active === $item['id']; @endphp
            <a
                href="{{ $item['href'] ?? '#' }}"
                class="flex items-center gap-2.5 w-full h-[30px] px-2.5 mb-px rounded-sm text-sm transition-colors duration-instant
                    {{ $isActive ? 'bg-active text-primary font-medium' : 'text-secondary hover:bg-hover hover:text-primary' }}"
            >
                <x-jaunt.icon :name="$item['icon']" size="sm" class="{{ $isActive ? 'text-accent' : 'text-tertiary' }}" />
                <span class="flex-1 min-w-0 truncate">{{ $item['label'] }}</span>
                @isset($item['count'])
                    <span class="text-2xs text-tertiary tabular-nums">{{ $item['count'] }}</span>
                @endisset
            </a>
        @endforeach

        @if (count($pinned))
            <div class="pt-3 pb-1 px-2 text-2xs font-semibold uppercase tracking-wide text-tertiary">Pinned</div>
            @foreach ($pinned as $item)
                <a href="{{ $item['href'] ?? '#' }}" class="flex items-center gap-2.5 w-full h-[30px] px-2.5 mb-px rounded-sm text-sm text-secondary transition-colors duration-instant hover:bg-hover hover:text-primary">
                    <x-jaunt.icon :name="$item['icon']" size="sm" class="text-tertiary" />
                    <span class="flex-1 min-w-0 truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endif
    </nav>

    <div class="border-t border-subtle p-2">
        <button type="button" class="flex items-center gap-2.5 w-full p-1.5 rounded-sm text-left transition-colors duration-instant hover:bg-hover">
            <x-jaunt.data.avatar :name="$user['name']" :src="$user['avatarSrc'] ?? null" size="sm" status="online" />
            <span class="flex-1 min-w-0 text-sm font-medium truncate">
                {{ $user['name'] }}
                <small class="block text-2xs font-normal text-tertiary truncate">{{ $user['role'] }}</small>
            </span>
            <x-jaunt.icon name="settings" size="sm" class="text-tertiary" />
        </button>
    </div>
</aside>
