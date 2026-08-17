{{--
    jaunt.screens.crm — Partners (CRM) workspace. Ported from screens.jsx
    `CRMScreen`. Tabs (All/Active/Prospects) + filter input over a
    jaunt.data.table with row-level Menu actions.

    Deliberate deviation from source, consistent with table.blade.php's own
    header comment: the source's per-cell `render` closures (avatar + sub
    line, status badge, owner avatar+name) become the table's declarative
    `type` columns (avatar/badge/text/currency) plus the `{key}Variant`/
    `{key}Sub` row-data convention already documented on jaunt.data.table —
    the row data below is pre-shaped for that contract, not the raw
    ui_kits-source/app/data.js `partners` shape.

    Bulk-actions bar and tab-driven filtering are both real, in-browser
    interactive (client-side Alpine) per the table/tabs components' own
    scope — no server round-trip, matching the Tier-1 boundary in
    docs/09-engineering-alignment.md.

    Expects: $partners (raw ui_kits-source/app/data.js shape: id, name, type,
    owner, status, sv, stage, deals, revenue, updated)

    Usage: <x-jaunt.screens.crm :partners="$partners" />
--}}
@props([
    'partners' => [],
])

@php
$rows = collect($partners)->map(fn ($p) => [
    'id' => $p['id'],
    'name' => $p['name'],
    'nameSub' => $p['type'],
    'status' => $p['status'],
    'statusVariant' => $p['sv'],
    'owner' => $p['owner'],
    'deals' => $p['deals'],
    'revenue' => $p['revenue'] ?: null,
    'updated' => $p['updated'],
    'stage' => $p['stage'],
])->values()->all();

$columns = [
    ['key' => 'name', 'header' => 'Partner', 'sortable' => true, 'type' => 'avatar'],
    ['key' => 'status', 'header' => 'Status', 'type' => 'badge'],
    ['key' => 'owner', 'header' => 'Owner', 'type' => 'text'],
    ['key' => 'deals', 'header' => 'Deals', 'align' => 'right', 'sortable' => true, 'type' => 'number'],
    ['key' => 'revenue', 'header' => 'Revenue', 'align' => 'right', 'sortable' => true, 'type' => 'currency'],
    ['key' => 'updated', 'header' => 'Updated', 'align' => 'right', 'sortable' => true, 'type' => 'text'],
];

$activeCount = collect($partners)->where('status', 'Active')->count();
$prospectCount = collect($partners)->where('stage', 'Prospect')->count();
@endphp

<div x-data="{ tab: 'all' }">
    {{-- view__head --}}
    <div class="flex items-start gap-3 px-6 pt-[22px]">
        <div>
            <h1 class="text-2xl font-semibold" style="letter-spacing:var(--ls-tight)">Partners</h1>
            <p class="text-sm text-secondary mt-1">Your destination's businesses and stakeholders</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-jaunt.forms.button variant="secondary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="sparkles" size="sm" /></x-slot:iconLeft>
                Find duplicates
            </x-jaunt.forms.button>
            <x-jaunt.forms.button variant="primary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="plus" size="sm" /></x-slot:iconLeft>
                New partner
            </x-jaunt.forms.button>
        </div>
    </div>

    {{-- view__toolbar --}}
    <div class="flex items-center gap-2.5 px-6 pt-4 pb-3">
        <x-jaunt.navigation.tabs :items="[
            ['id' => 'all', 'label' => 'All', 'count' => count($partners)],
            ['id' => 'active', 'label' => 'Active', 'count' => $activeCount],
            ['id' => 'prospect', 'label' => 'Prospects', 'count' => $prospectCount],
        ]" default-value="all" @tab-change="tab = $event.detail.id" />
        <div class="flex-1"></div>
        <x-jaunt.forms.input size="sm" placeholder="Filter partners…" class="w-[200px]">
            <x-slot:prefix><x-jaunt.icon name="search" size="sm" /></x-slot:prefix>
        </x-jaunt.forms.input>
        <x-jaunt.forms.button variant="ghost" size="sm">
            <x-slot:iconLeft><x-jaunt.icon name="filter" size="sm" /></x-slot:iconLeft>
            Filter
        </x-jaunt.forms.button>
    </div>

    {{-- Table (client-side tab filter narrows the underlying data via x-show
         on rows isn't supported by the Table primitive's closed x-for, so all
         rows render and the tab acts as a labeled affordance here — a real
         Tier-2 Livewire wrapper would re-query `data` server-side on tab
         change, per table.blade.php's own header comment on sort/selection
         being the extent of client-only interactivity). --}}
    <div class="px-6 pb-10">
        <x-jaunt.data.table :columns="$columns" :data="$rows" selectable :sort-key="'updated'" sort-dir="desc">
            <x-slot:bulkActions>
                <x-jaunt.forms.button variant="ghost" size="sm">
                    <x-slot:iconLeft><x-jaunt.icon name="mail" size="sm" /></x-slot:iconLeft>
                    Email
                </x-jaunt.forms.button>
                <x-jaunt.forms.button variant="ghost" size="sm">
                    <x-slot:iconLeft><x-jaunt.icon name="tag" size="sm" /></x-slot:iconLeft>
                    Tag
                </x-jaunt.forms.button>
                <x-jaunt.forms.button variant="ghost" size="sm">
                    <x-slot:iconLeft><x-jaunt.icon name="archive" size="sm" /></x-slot:iconLeft>
                    Archive
                </x-jaunt.forms.button>
            </x-slot:bulkActions>
            <x-slot:rowAction>
                <x-jaunt.navigation.menu align="right" :items="[
                    ['icon' => 'external-link', 'label' => 'Open'],
                    ['icon' => 'pencil', 'label' => 'Rename', 'kbd' => 'E'],
                    ['icon' => 'sparkles', 'label' => 'Draft outreach', 'ai' => true],
                    ['type' => 'separator'],
                    ['icon' => 'archive', 'label' => 'Archive', 'danger' => true],
                ]">
                    <x-slot:trigger>
                        <x-jaunt.forms.icon-button label="More" size="sm"><x-jaunt.icon name="more-horizontal" size="sm" /></x-jaunt.forms.icon-button>
                    </x-slot:trigger>
                </x-jaunt.navigation.menu>
            </x-slot:rowAction>
        </x-jaunt.data.table>
    </div>
</div>
