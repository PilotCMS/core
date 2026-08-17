@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Pilot CMS" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-sm bg-accent text-on-accent shadow-xs">
            <img src="{{ asset('img/logo.svg') }}" alt="" class="size-5 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Pilot CMS" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-sm bg-accent text-on-accent shadow-xs">
            <img src="{{ asset('img/logo.svg') }}" alt="" class="size-5 object-contain" />
        </x-slot>
    </flux:brand>
@endif
