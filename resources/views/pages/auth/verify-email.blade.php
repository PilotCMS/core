<x-layouts::auth :title="__('Verify your email · Pilot CMS')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Check your inbox')"
            :description="__('We sent you a verification link. Open it to finish setting up your account.')"
        />

        @if (session('status') == 'verification-link-sent')
            <div class="auth-session-status" role="status">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="m5.75 10.25 2.5 2.5 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </span>
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification link') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full text-center">
                @csrf
                <flux:button variant="ghost" type="submit" class="w-full cursor-pointer" data-test="logout-button">
                    {{ __('Sign out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
