<x-layouts::auth :title="__('Reset password · Pilot CMS')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Reset your password')" :description="__('Enter your email and we’ll send you a secure reset link.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email Address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <div class="text-center text-sm text-secondary">
            <flux:link :href="route('login')" wire:navigate class="auth-inline-link">{{ __('Back to sign in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
