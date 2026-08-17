<x-layouts::auth :title="__('Confirm your identity · Pilot CMS')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Confirm it’s you')"
            :description="__('Enter your password to continue to this secure area.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Continue') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
