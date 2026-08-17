{{--
    jaunt.shell.topbar — breadcrumbs + global actions (Ask Jaunt, search,
    notifications, theme). Ported from Shell.jsx (Topbar) + app.css (.topbar*).
    Uses backdrop-blur over a translucent surface, per docs/00-philosophy.md
    Visual Philosophy ("used sparingly: command palette + top bar").
--}}
@props([
    'crumbs' => [],
    'hasNotifications' => false,
])

<header class="j-chrome-edge flex items-center gap-2.5 h-topbar shrink-0 px-4">
    <x-jaunt.navigation.breadcrumbs :items="$crumbs" />

    <div class="ml-auto flex items-center gap-1">
        <x-jaunt.feedback.tooltip label="Ask Jaunt" kbd="⌘J">
            <x-jaunt.forms.icon-button label="Ask Jaunt" @click="$store.aiRail.open = true">
                <x-jaunt.icon name="sparkles" size="sm" />
            </x-jaunt.forms.icon-button>
        </x-jaunt.feedback.tooltip>

        <x-jaunt.feedback.tooltip label="Search" kbd="⌘K">
            <x-jaunt.forms.icon-button label="Search" @click="$store.commandPalette.open = true">
                <x-jaunt.icon name="search" size="sm" />
            </x-jaunt.forms.icon-button>
        </x-jaunt.feedback.tooltip>

        <span class="relative">
            <x-jaunt.forms.icon-button label="Notifications">
                <x-jaunt.icon name="bell" size="sm" />
            </x-jaunt.forms.icon-button>
            @if ($hasNotifications)
                <span class="absolute top-1 right-1 w-[7px] h-[7px] rounded-full bg-danger border-[1.5px] border-app"></span>
            @endif
        </span>

        {{-- Static "Toggle theme" label (not dynamic light/dark text) — the icon itself
             already signals the target state, and aria-label covers accessibility. --}}
        <x-jaunt.feedback.tooltip label="Toggle theme">
            <x-jaunt.forms.icon-button label="Toggle theme" @click="$store.theme.toggle()">
                <template x-if="$store.theme.dark"><x-jaunt.icon name="sun" size="sm" /></template>
                <template x-if="!$store.theme.dark"><x-jaunt.icon name="moon" size="sm" /></template>
            </x-jaunt.forms.icon-button>
        </x-jaunt.feedback.tooltip>
    </div>
</header>
