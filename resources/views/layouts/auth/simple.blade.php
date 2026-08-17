<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app antialiased">
        <x-toast-region />
        <main class="auth-shell">
            <aside class="auth-brand-panel" aria-label="About Pilot CMS">
                <div class="auth-brand-glow auth-brand-glow-top" aria-hidden="true"></div>
                <div class="auth-brand-glow auth-brand-glow-bottom" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="auth-brand" wire:navigate>
                    <span class="auth-brand-mark">
                        <img src="{{ asset('img/logo.svg') }}" alt="" />
                    </span>
                    <span>
                        <span class="auth-brand-name">Pilot CMS</span>
                        <span class="auth-brand-kicker">Content operations</span>
                    </span>
                </a>

                <div class="auth-brand-story">
                    <p class="auth-brand-eyebrow">Built for focused teams</p>
                    <h2>Keep every story moving in the right direction.</h2>
                    <p>Plan, create, review, and publish from one calm workspace.</p>

                    <div class="auth-workflow-preview" aria-hidden="true">
                        <div class="auth-workflow-topbar">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="auth-workflow-body">
                            <div class="auth-workflow-sidebar">
                                <span class="is-active"></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="auth-workflow-content">
                                <div class="auth-workflow-meta">
                                    <span></span>
                                    <b>Ready to publish</b>
                                </div>
                                <div class="auth-workflow-title"></div>
                                <div class="auth-workflow-line is-long"></div>
                                <div class="auth-workflow-line"></div>
                                <div class="auth-workflow-cards">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="auth-brand-footer">A secure workspace for your content and team.</p>
            </aside>

            <section class="auth-form-panel">
                <div class="auth-mobile-brand">
                    <a href="{{ route('home') }}" class="auth-brand" wire:navigate>
                        <span class="auth-brand-mark">
                            <img src="{{ asset('img/logo.svg') }}" alt="" />
                        </span>
                        <span class="auth-brand-name">Pilot CMS</span>
                    </a>
                </div>

                <div class="auth-form-wrap">
                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                    <p class="auth-security-note">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3 5.5 5.8v5.3c0 4.3 2.7 8.2 6.5 9.9 3.8-1.7 6.5-5.6 6.5-9.9V5.8L12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="m9.5 12 1.7 1.7 3.6-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Your connection is encrypted and secure.
                    </p>
                </div>
            </section>
        </main>
        @fluxScripts
    </body>
</html>
