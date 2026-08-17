<div class="grid gap-8 lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="min-w-0">
        <div class="space-y-1">
            <a href="{{ route('profile.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('profile.edit') ? 'bg-card border border-[color:var(--border-default)] text-primary shadow-sm' : 'text-secondary hover:text-primary hover:bg-hover' }}" wire:navigate>
                <x-jaunt.icon name="circle-user-round" size="sm" />
                <span>{{ __('Profile') }}</span>
            </a>
            <a href="{{ route('user-password.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('user-password.edit') ? 'bg-card border border-[color:var(--border-default)] text-primary shadow-sm' : 'text-secondary hover:text-primary hover:bg-hover' }}" wire:navigate>
                <x-jaunt.icon name="key-round" size="sm" />
                <span>{{ __('Password') }}</span>
            </a>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <a href="{{ route('two-factor.show') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('two-factor.show') ? 'bg-card border border-[color:var(--border-default)] text-primary shadow-sm' : 'text-secondary hover:text-primary hover:bg-hover' }}" wire:navigate>
                    <x-jaunt.icon name="shield-check" size="sm" />
                    <span>{{ __('Two-Factor Auth') }}</span>
                </a>
            @endif
            <a href="{{ route('appearance.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-card border border-[color:var(--border-default)] text-primary shadow-sm' : 'text-secondary hover:text-primary hover:bg-hover' }}" wire:navigate>
                <x-jaunt.icon name="palette" size="sm" />
                <span>{{ __('Appearance') }}</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 self-stretch min-w-0">
        <div>
            <h2 class="text-base font-semibold text-primary">{{ $heading ?? '' }}</h2>
            <p class="mt-1 text-sm text-tertiary">{{ $subheading ?? '' }}</p>
        </div>

        <div class="mt-6 w-full max-w-2xl rounded-xl border border-[color:var(--border-default)] bg-card p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</div>
