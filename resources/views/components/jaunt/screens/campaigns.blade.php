{{--
    jaunt.screens.campaigns — Campaigns workspace. Ported from screens.jsx
    `CampaignsScreen`. Thin wrapper: a page head + jaunt.data.kanban — the
    lightest of the five screens, since almost all the interesting behavior
    (drag/drop, tags, avatar clusters) already lives in the Kanban primitive
    itself (see docs/03-component-library.md).

    Expects: $kanban (ui_kits-source/app/data.js shape: id, title, color,
    cards: [{ id, title, tag: {v, t}, people: [name, ...] }])

    Usage: <x-jaunt.screens.campaigns :kanban="$kanban" />
--}}
@props([
    'kanban' => [],
])

@php
$columns = collect($kanban)->map(fn ($col) => [
    'id' => $col['id'],
    'title' => $col['title'],
    'color' => $col['color'],
    'cards' => collect($col['cards'])->map(fn ($c) => [
        'id' => $c['id'],
        'title' => $c['title'],
        'tags' => [['variant' => $c['tag']['v'], 'label' => $c['tag']['t']]],
        'people' => collect($c['people'])->map(fn ($n) => ['name' => $n])->all(),
    ])->all(),
])->all();
@endphp

<div>
    {{-- view__head --}}
    <div class="flex items-start gap-3 px-6 pt-[22px]">
        <div>
            <h1 class="text-2xl font-semibold" style="letter-spacing:var(--ls-tight)">Campaigns</h1>
            <p class="text-sm text-secondary mt-1">Plan, review, and ship destination marketing</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-jaunt.forms.button variant="primary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="plus" size="sm" /></x-slot:iconLeft>
                New campaign
            </x-jaunt.forms.button>
        </div>
    </div>

    <div class="px-6 pb-10 pt-4">
        <x-jaunt.data.kanban :columns="$columns" />
    </div>
</div>
