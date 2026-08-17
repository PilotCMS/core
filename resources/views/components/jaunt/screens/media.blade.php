{{--
    jaunt.screens.media — Media workspace. Ported from screens.jsx
    `MediaScreen`. The lightest screen in the source itself — a page head
    plus a single AI-variant jaunt.data.empty-state inside a bare panel
    surface (no bespoke chart/KPI markup to translate here).

    Usage: <x-jaunt.screens.media />
--}}
<div>
    {{-- view__head --}}
    <div class="flex items-start gap-3 px-6 pt-[22px]">
        <div>
            <h1 class="text-2xl font-semibold" style="letter-spacing:var(--ls-tight)">Media</h1>
            <p class="text-sm text-secondary mt-1">Photos and video for your destination</p>
        </div>
    </div>

    <div class="px-6 pb-10" style="padding-top:40px">
        <div class="bg-card border border-[color:var(--border-default)] rounded-lg">
            <x-jaunt.data.empty-state
                variant="ai"
                icon="image"
                title="Your media library is empty"
                description="Upload photos and Jaunt will auto-tag them and draft alt text for accessibility."
            >
                <x-slot:actions>
                    <x-jaunt.forms.button variant="primary">
                        <x-slot:iconLeft><x-jaunt.icon name="upload" size="sm" /></x-slot:iconLeft>
                        Upload photos
                    </x-jaunt.forms.button>
                    <x-jaunt.forms.button variant="secondary">Connect a source</x-jaunt.forms.button>
                </x-slot:actions>
            </x-jaunt.data.empty-state>
        </div>
    </div>
</div>
