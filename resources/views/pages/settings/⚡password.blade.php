<?php

use Pilot\Core\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('Password Settings')]
class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <x-jaunt.shell.dynamic-header :title="__('Account')" :subtitle="__('Manage your Pilot CMS account settings')" top="0px" as="header" scroll-target="#password-settings-scroll" aria-label="Page header" />

    <main id="password-settings-scroll" class="flex-1 min-h-0 overflow-y-auto">
        <div class="w-full px-6 sm:px-8 py-8">
            <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
                <form method="POST" wire:submit="updatePassword" class="space-y-6">
                    <flux:input
                        wire:model="current_password"
                        :label="__('Current password')"
                        type="password"
                        required
                        autocomplete="current-password"
                    />
                    <flux:input
                        wire:model="password"
                        :label="__('New password')"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <flux:input
                        wire:model="password_confirmation"
                        :label="__('Confirm Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                    />

                    <div class="flex items-center gap-4">
                        <flux:button variant="primary" type="submit" data-test="update-password-button">
                            {{ __('Save') }}
                        </flux:button>

                        <x-action-message class="me-3" on="password-updated">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </x-pages::settings.layout>
        </div>
    </main>
</section>
