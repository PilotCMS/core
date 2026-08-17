<?php

namespace Pilot\Core\Livewire\Admin\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Models\Space;

class Index extends Component
{
    public string $defaultSpace = '';

    public string $homeSlug = 'home';

    public string $defaultLocale = 'en';

    public bool $draftApiEnabled = true;

    public bool $previewLinksEnabled = true;

    public int $previewExpirationMinutes = 60;

    public function mount(): void
    {
        $this->defaultSpace = CmsSetting::get('default_space', config('cms.default_space', '')) ?? '';
        $this->homeSlug = CmsSetting::get('home_slug', config('cms.home_slug', 'home'));
        $this->defaultLocale = CmsSetting::get('default_locale', 'en');
        $this->draftApiEnabled = (bool) CmsSetting::get('draft_api_enabled', true);
        $this->previewLinksEnabled = (bool) CmsSetting::get('preview_links_enabled', true);
        $this->previewExpirationMinutes = (int) CmsSetting::get('preview_expiration_minutes', 60);
    }

    public function save(): void
    {
        $this->authorizeSettingsManagement();

        $validated = $this->validate([
            'defaultSpace' => ['nullable', 'string', Rule::exists('spaces', 'slug')],
            'homeSlug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9\/_-]*$/i'],
            'defaultLocale' => ['required', 'string', 'max:12', 'regex:/^[a-z]{2}([_-][A-Z]{2})?$/'],
            'draftApiEnabled' => ['boolean'],
            'previewLinksEnabled' => ['boolean'],
            'previewExpirationMinutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ]);

        CmsSetting::setMany([
            'default_space' => $validated['defaultSpace'] ?: null,
            'home_slug' => $validated['homeSlug'],
            'default_locale' => $validated['defaultLocale'],
            'draft_api_enabled' => $validated['draftApiEnabled'],
            'preview_links_enabled' => $validated['previewLinksEnabled'],
            'preview_expiration_minutes' => $validated['previewExpirationMinutes'],
        ]);

        $this->dispatch('cms-settings-saved');
    }

    public function resetToEnvironmentDefaults(): void
    {
        $this->authorizeSettingsManagement();

        $this->defaultSpace = config('cms.default_space', '') ?? '';
        $this->homeSlug = config('cms.home_slug', 'home');
        $this->defaultLocale = 'en';
        $this->draftApiEnabled = true;
        $this->previewLinksEnabled = true;
        $this->previewExpirationMinutes = 60;

        CmsSetting::query()->whereIn('key', [
            'default_space',
            'home_slug',
            'default_locale',
            'draft_api_enabled',
            'preview_links_enabled',
            'preview_expiration_minutes',
        ])->delete();

        $this->resetValidation();
        $this->dispatch('cms-settings-reset');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index', [
            'spaces' => Space::query()->orderBy('name')->get(),
            'settings' => CmsSetting::query()->orderBy('key')->get(),
        ])
            ->layout('layouts.admin');
    }

    protected function authorizeSettingsManagement(): void
    {
        abort_unless(auth()->user()?->can('manage settings'), 403);
    }
}
