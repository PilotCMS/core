{{--
    jaunt.screens.analytics — Analytics workspace. Ported from screens.jsx
    `AnalyticsScreen`. KPI tiles + a site-visitors bar chart + an AI insights
    panel + a recent-activity timeline.

    None of the KPI tile / bar chart / panel chrome exists as a Tier-1
    primitive (they're workspace-specific visuals, not reusable components —
    see docs/03-component-library.md's index), so they're hand-built here
    with Tailwind utilities on semantic tokens, translating the source's
    `.kpi`/`.panel`/`.bars` CSS classes (ui_kits-source/app/app.css) 1:1.
    Everything else composes Tier-1 primitives: jaunt.forms.select,
    jaunt.forms.button, jaunt.feedback.badge, jaunt.ai.ai-streaming,
    jaunt.ai.confidence-badge, jaunt.data.timeline.

    Expects (mirrors ui_kits-source/app/data.js shapes):
      $kpis:    [{ label, icon, value, delta, up }]
      $traffic: [{ m, v }]   -- month label + value, bar height = v / max(v)
      $activity:[{ id, icon, tone, who, text, time }]

    Usage: <x-jaunt.screens.analytics :kpis="$kpis" :traffic="$traffic" :activity="$activity" />
--}}
@props([
    'kpis' => [],
    'traffic' => [],
    'activity' => [],
])

@php
$max = collect($traffic)->max('v') ?: 1;
@endphp

<div>
    {{-- view__head --}}
    <div class="flex items-start gap-3 px-6 pt-[22px]">
        <div>
            <h1 class="text-2xl font-semibold" style="letter-spacing:var(--ls-tight)">Analytics</h1>
            <p class="text-sm text-secondary mt-1">Destination performance · last 7 months</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-jaunt.forms.select size="sm" :options="[
                ['value' => '7m', 'label' => 'Last 7 months'],
                ['value' => '30d', 'label' => 'Last 30 days'],
            ]" value="7m" />
            <x-jaunt.forms.button variant="secondary" size="sm">
                <x-slot:iconLeft><x-jaunt.icon name="download" size="sm" /></x-slot:iconLeft>
                Export
            </x-jaunt.forms.button>
        </div>
    </div>

    {{-- view__body --}}
    <div class="px-6 pb-10 pt-4">
        {{-- KPI tiles --}}
        <div class="grid grid-cols-4 gap-3.5 mt-2 max-md:grid-cols-2">
            @foreach ($kpis as $k)
                <div class="bg-card border border-[color:var(--border-default)] rounded-lg p-4 shadow-sm">
                    <div class="flex items-center gap-1.5 text-xs text-tertiary">
                        <x-jaunt.icon :name="$k['icon']" size="sm" />
                        {{ $k['label'] }}
                    </div>
                    <div class="text-2xl font-semibold mt-2.5 tabular-nums" style="letter-spacing:var(--ls-tight)">{{ $k['value'] }}</div>
                    <div class="text-xs font-medium mt-1 inline-flex items-center gap-1 {{ $k['up'] ? 'text-success' : 'text-danger' }}">
                        <x-jaunt.icon :name="$k['up'] ? 'trending-up' : 'trending-down'" size="xs" />
                        {{ $k['delta'] }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Chart + Insights --}}
        <div class="grid gap-3.5 mt-3.5" style="grid-template-columns: 1.6fr 1fr;">
            {{-- Site visitors bar chart --}}
            <div class="bg-card border border-[color:var(--border-default)] rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-subtle">
                    <span class="text-sm font-semibold">Site visitors</span>
                    <x-jaunt.feedback.badge variant="success" dot class="ml-auto">Trending up</x-jaunt.feedback.badge>
                </div>
                <div class="p-4">
                    <div class="flex items-end gap-2.5" style="height:180px">
                        @foreach ($traffic as $i => $t)
                            <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                                <div
                                    class="w-full rounded-t {{ $i === count($traffic) - 1 ? 'bg-accent-subtle' : 'bg-accent' }}"
                                    style="max-width:34px; height:{{ $t['v'] / $max * 100 }}%; transition: height var(--dur-slow) var(--ease-out);"
                                ></div>
                                <div class="text-2xs text-tertiary">{{ $t['m'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- AI Insights panel --}}
            <div class="bg-card border border-[color:var(--border-default)] rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-subtle">
                    <span class="grid place-items-center w-[22px] h-[22px] rounded-sm bg-ai-subtle text-ai border border-ai-border">
                        <x-jaunt.icon name="sparkles" size="xs" />
                    </span>
                    <span class="text-sm font-semibold">Insights</span>
                    <span class="ml-auto"><x-jaunt.ai.confidence-badge level="high" :show-label="false" /></span>
                </div>
                <div class="p-4 flex flex-col gap-3">
                    <x-jaunt.ai.ai-streaming
                        text="Web referrals from partners dropped 3.2% — mostly from the events page after last week's layout change. Visitor sessions are up 18% overall, led by the coastal trail listings."
                        typewriter
                    />
                    <x-jaunt.forms.button variant="ai" size="sm" block>
                        <x-slot:iconLeft><x-jaunt.icon name="sparkles" size="sm" /></x-slot:iconLeft>
                        Ask a follow-up
                    </x-jaunt.forms.button>
                </div>
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="bg-card border border-[color:var(--border-default)] rounded-lg shadow-sm overflow-hidden mt-3.5">
            <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-subtle">
                <span class="text-sm font-semibold">Recent activity</span>
            </div>
            <div class="p-4">
                <x-jaunt.data.timeline :items="collect($activity)->map(fn ($a) => [
                    'id' => $a['id'],
                    'icon' => $a['icon'],
                    'tone' => $a['tone'] === 'default' ? 'default' : $a['tone'],
                    'title' => '<b>' . e($a['who']) . '</b> ' . e($a['text']),
                    'time' => $a['time'],
                ])->all()" />
            </div>
        </div>
    </div>
</div>
