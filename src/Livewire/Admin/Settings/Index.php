<?php

namespace Pilot\Core\Livewire\Admin\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Support\Updates\PilotUpdateChecker;
use Pilot\Core\Support\Updates\PilotUpdateManager;
use RuntimeException;

class Index extends Component
{
    public string $defaultLocale = 'en';

    public bool $draftApiEnabled = true;

    public bool $previewLinksEnabled = true;

    public int $previewExpirationMinutes = 60;

    /** @var array<string, mixed> */
    public array $pilotVersion = [];

    /** @var array<string, mixed> */
    public array $pilotUpdate = [];

    public string $pilotUpdateLog = '';

    public function mount(PilotUpdateChecker $checker, PilotUpdateManager $manager): void
    {
        $this->defaultLocale = CmsSetting::get('default_locale', config('cms.default_locale', 'en'));
        $this->draftApiEnabled = (bool) CmsSetting::get('draft_api_enabled', true);
        $this->previewLinksEnabled = (bool) CmsSetting::get('preview_links_enabled', true);
        $this->previewExpirationMinutes = (int) CmsSetting::get('preview_expiration_minutes', 60);
        $this->pilotVersion = $checker->status();
        $this->pilotUpdate = $manager->status();
        $this->pilotUpdateLog = $manager->log();
    }

    public function checkForPilotUpdates(PilotUpdateChecker $checker): void
    {
        $this->authorizeSettingsManagement();
        $this->pilotVersion = $checker->status(true);
    }

    public function startPilotUpdate(PilotUpdateManager $manager): void
    {
        $this->authorizeSettingsManagement();
        $this->resetErrorBag('pilotUpdate');

        try {
            $this->pilotUpdate = $manager->start(
                (string) ($this->pilotVersion['latest'] ?? ''),
                auth()->id(),
            );
        } catch (RuntimeException $exception) {
            $this->addError('pilotUpdate', $exception->getMessage());
        }
    }

    public function refreshPilotUpdateStatus(PilotUpdateChecker $checker, PilotUpdateManager $manager): void
    {
        $previousStatus = $this->pilotUpdate['status'] ?? null;
        $this->pilotUpdate = $manager->status();
        $this->pilotUpdateLog = $manager->log();

        if ($previousStatus !== 'succeeded' && ($this->pilotUpdate['status'] ?? null) === 'succeeded') {
            $this->pilotVersion = $checker->status(true);
        }
    }

    public function save(): void
    {
        $this->authorizeSettingsManagement();

        $validated = $this->validate([
            'defaultLocale' => ['required', 'string', 'max:12', 'regex:/^[a-z]{2}([_-][A-Z]{2})?$/'],
            'draftApiEnabled' => ['boolean'],
            'previewLinksEnabled' => ['boolean'],
            'previewExpirationMinutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ]);

        CmsSetting::setMany([
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

        $this->defaultLocale = config('cms.default_locale', 'en');
        $this->draftApiEnabled = true;
        $this->previewLinksEnabled = true;
        $this->previewExpirationMinutes = 60;

        CmsSetting::query()->whereIn('key', [
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
            'settings' => CmsSetting::query()
                ->whereNotIn('key', ['preview_secret'])
                ->orderBy('key')
                ->get(),
        ])
            ->layout('layouts.admin');
    }

    protected function authorizeSettingsManagement(): void
    {
        abort_unless(auth()->user()?->can('manage settings'), 403);
    }
}
