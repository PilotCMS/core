{{--
    jaunt.screens.listings — Listings workspace. Ported from screens.jsx
    `ListingsScreen`. A card grid (`.grid-cards` in ui_kits-source/app/app.css
    -> `grid-cols-[repeat(auto-fill,minmax(240px,1fr))]` here), each tile a
    jaunt.data.card with a gradient media placeholder, status badge, view
    count, and row-menu — NOT a kanban board (the source uses Kanban for
    Campaigns, cards for Listings; see docs/05-workspace-pattern.md for why
    each workspace picked its layout).

    The diagonal gradient media placeholder has no Tier-1 equivalent (Card's
    `media` slot expects an image `src`) — source used `l.hue` as a raw CSS
    gradient background per listing, so it's reproduced with an inline style
    here rather than the Card media slot's <img>.

    Expects: $listings (ui_kits-source/app/data.js shape: id, name, cat,
    status, sv, views, hue)

    Usage: <x-jaunt.screens.listings :listings="$listings" />
--}}
@props([
    'listings' => [],
])

@php
$draftCount = 2; // matches screens.jsx's hardcoded draft count (mock data has no explicit "Draft" tally field)
$pubCount = collect($listings)->where('status', 'Published')->count();
@endphp

<div x-data="{ tab: 'all' }">
    {{-- view__head --}}
    <div class="flex items-start gap-3 px-6 pt-[22px]">
        <div>
            <h1 class="text-2xl font-semibold" style="letter-spacing:var(--ls-tight)">Listings</h1>
            <p class="text-sm text-secondary mt-1">Everything visitors can discover in your destination</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-jaunt.forms.button variant="secondary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="upload" size="sm" /></x-slot:iconLeft>
                Import
            </x-jaunt.forms.button>
            <x-jaunt.forms.button variant="primary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="plus" size="sm" /></x-slot:iconLeft>
                New listing
            </x-jaunt.forms.button>
        </div>
    </div>

    {{-- view__toolbar --}}
    <div class="flex items-center gap-2.5 px-6 pt-4 pb-3">
        <x-jaunt.navigation.tabs :items="[
            ['id' => 'all', 'label' => 'All', 'count' => count($listings)],
            ['id' => 'pub', 'label' => 'Published', 'count' => $pubCount],
            ['id' => 'draft', 'label' => 'Draft', 'count' => $draftCount],
        ]" default-value="all" @tab-change="tab = $event.detail.id" />
        <div class="flex-1"></div>
        <x-jaunt.forms.input size="sm" placeholder="Search listings…" class="w-[200px]">
            <x-slot:prefix><x-jaunt.icon name="search" size="sm" /></x-slot:prefix>
        </x-jaunt.forms.input>
        <x-jaunt.forms.icon-button label="Grid view" active solid><x-jaunt.icon name="layout-grid" size="sm" /></x-jaunt.forms.icon-button>
        <x-jaunt.forms.icon-button label="List view" solid><x-jaunt.icon name="list" size="sm" /></x-jaunt.forms.icon-button>
    </div>

    {{-- grid-cards --}}
    <div class="px-6 pb-10 pt-1">
        <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
            @foreach ($listings as $l)
                <x-jaunt.data.card hoverable>
                    <div class="w-full aspect-video" style="background: linear-gradient(135deg, {{ $l['hue'] }}, color-mix(in oklab, {{ $l['hue'] }} 55%, #000));"></div>
                    <x-slot:header title="{{ $l['name'] }}" subtitle="{{ $l['cat'] }}"></x-slot:header>
                    <x-slot:action>
                        <x-jaunt.feedback.badge :variant="$l['sv']" dot>{{ $l['status'] }}</x-jaunt.feedback.badge>
                    </x-slot:action>
                    <x-slot:footer>
                        <span class="inline-flex items-center gap-1.5 text-xs text-tertiary">
                            <x-jaunt.icon name="eye" size="sm" />
                            {{ number_format($l['views']) }}
                        </span>
                        <span class="ml-auto">
                            <x-jaunt.navigation.menu align="right" :items="[
                                ['icon' => 'external-link', 'label' => 'Preview'],
                                ['icon' => 'sparkles', 'label' => 'Improve with AI', 'ai' => true],
                                ['type' => 'separator'],
                                ['icon' => 'trash-2', 'label' => 'Delete', 'danger' => true],
                            ]">
                                <x-slot:trigger>
                                    <x-jaunt.forms.icon-button label="More" size="sm"><x-jaunt.icon name="more-horizontal" size="sm" /></x-jaunt.forms.icon-button>
                                </x-slot:trigger>
                            </x-jaunt.navigation.menu>
                        </span>
                    </x-slot:footer>
                </x-jaunt.data.card>
            @endforeach
        </div>
    </div>
</div>
