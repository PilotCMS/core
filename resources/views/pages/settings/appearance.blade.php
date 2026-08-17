<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('Appearance Settings')]
class extends Component {
    //
}; ?>

<section class="flex flex-col w-full min-w-0 h-full bg-app">
    <x-jaunt.shell.dynamic-header :title="__('Account')" :subtitle="__('Manage your Pilot CMS account settings')" top="0px" as="header" scroll-target="#appearance-settings-scroll" aria-label="Page header" />

    <main id="appearance-settings-scroll" class="flex-1 min-h-0 overflow-y-auto">
        <div class="w-full px-6 sm:px-8 py-8">
            <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Choose how Pilot CMS should render on this device')">
                <flux:radio.group x-data variant="segmented" name="appearance" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </x-pages::settings.layout>
        </div>
    </main>
</section>
