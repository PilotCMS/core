<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $cmsTitle = match (true) {
        request()->routeIs('admin.dashboard') => 'Dashboard',
        request()->routeIs('admin.content.index') => 'Content',
        request()->routeIs('admin.content.create') => 'New Content',
        request()->routeIs('admin.content.edit', 'admin.content.editor') => 'Content Editor',
        request()->routeIs('admin.content-types.*') => 'Content Types',
        request()->routeIs('admin.blocks.create') => 'New Block',
        request()->routeIs('admin.blocks.edit') => 'Edit Block',
        request()->routeIs('admin.blocks.*') => 'Blocks',
        request()->routeIs('admin.assets.*') => 'Assets',
        request()->routeIs('admin.datasources.*') => 'Datasources',
        request()->routeIs('admin.spaces.create') => 'New Space',
        request()->routeIs('admin.spaces.edit') => 'Edit Space',
        request()->routeIs('admin.spaces.*') => 'Spaces',
        request()->routeIs('admin.users.*') => 'Users',
        request()->routeIs('admin.settings.*') => 'CMS Settings',
        request()->routeIs('profile.edit') => 'Profile Settings',
        request()->routeIs('user-password.edit') => 'Password Settings',
        request()->routeIs('two-factor.show') => 'Two-Factor Authentication',
        request()->routeIs('appearance.edit') => 'Appearance Settings',
        default => null,
    };

    $browserTitle = $title ?? $cmsTitle;

    if ($browserTitle && request()->routeIs('admin.*', 'profile.edit', 'user-password.edit', 'two-factor.show', 'appearance.edit')) {
        $browserTitle = str($browserTitle)->contains('Pilot CMS')
            ? $browserTitle
            : "{$browserTitle} · Pilot CMS";
    }
@endphp

<title>{{ $browserTitle ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<style>
    :root.dark {
        color-scheme: dark;
    }
</style>
<script>
    window.Flux = {
        applyAppearance(appearance) {
            const root = document.documentElement;
            const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const resolvedAppearance = appearance === 'system'
                ? (systemIsDark ? 'dark' : 'light')
                : appearance;
            const isDark = resolvedAppearance === 'dark';

            root.classList.toggle('dark', isDark);
            root.setAttribute('data-theme', isDark ? 'dark' : 'light');
            root.style.colorScheme = isDark ? 'dark' : 'light';

            if (appearance === 'system') {
                window.localStorage.removeItem('flux.appearance');
            } else {
                window.localStorage.setItem('flux.appearance', appearance);
            }

            window.dispatchEvent(new CustomEvent('pilot-theme-changed', {
                detail: { appearance, isDark },
            }));
        },
    };

    window.Flux.applyAppearance(window.localStorage.getItem('flux.appearance') || 'system');
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
