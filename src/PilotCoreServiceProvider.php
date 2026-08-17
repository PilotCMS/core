<?php

namespace Pilot\Core;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Pilot\Core\Console\Commands\BackfillPublishedContentRevisions;
use Pilot\Core\Console\Commands\FinalizePilotUpdate;
use Pilot\Core\Console\Commands\GenerateAssetThumbnails;
use Pilot\Core\Console\Commands\InstallPilot;
use Pilot\Core\Console\Commands\PublishScheduledContent;
use Pilot\Core\Console\Commands\SyncPilotHost;
use Pilot\Core\Console\Commands\UpdatePilot;
use Pilot\Core\Livewire\Admin\Assets\AssetPickerModal;
use Pilot\Core\Livewire\Admin\Assets\Index;
use Pilot\Core\Livewire\Admin\Blocks\Create;
use Pilot\Core\Livewire\Admin\Blocks\Edit;
use Pilot\Core\Livewire\Admin\CommandPalette;
use Pilot\Core\Livewire\Admin\Content\BlockEditor;
use Pilot\Core\Livewire\Admin\Content\ContentSyncPoller;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Dashboard;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Providers\FortifyServiceProvider;

class PilotCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->set('cms', require __DIR__.'/../config/cms.php');
        $this->app['config']->set('installation', require __DIR__.'/../config/installation.php');
        $this->app['config']->set('fortify', require __DIR__.'/../config/fortify.php');
        $this->app['config']->set('lighthouse.schema_path', __DIR__.'/../graphql/schema.graphql');
        $this->app->register(FortifyServiceProvider::class);
    }

    public function boot(): void
    {
        $this->configureApplicationDefaults();
        $this->configurePilotPreviewSecret();

        View::getFinder()->prependLocation(__DIR__.'/../resources/views');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pilot-core');
        View::prependNamespace('flux', __DIR__.'/../resources/views/flux');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/flux', 'flux');

        Livewire::addNamespace('layouts', viewPath: __DIR__.'/../resources/views/layouts');
        View::addNamespace('layouts', __DIR__.'/../resources/views/layouts');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/layouts', 'layouts');

        Livewire::addNamespace('pages', viewPath: __DIR__.'/../resources/views/pages');
        View::addNamespace('pages', __DIR__.'/../resources/views/pages');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/pages', 'pages');

        $components = [
            'dashboard' => Dashboard::class,
            'command-palette' => CommandPalette::class,
            'assets' => Index::class,
            'assets.asset-picker-modal' => AssetPickerModal::class,
            'blocks' => \Pilot\Core\Livewire\Admin\Blocks\Index::class,
            'blocks.create' => Create::class,
            'blocks.edit' => Edit::class,
            'content' => \Pilot\Core\Livewire\Admin\Content\Index::class,
            'content.create' => \Pilot\Core\Livewire\Admin\Content\Create::class,
            'content.edit' => \Pilot\Core\Livewire\Admin\Content\Edit::class,
            'content.editor' => Editor::class,
            'content.block-editor' => BlockEditor::class,
            'content.content-sync-poller' => ContentSyncPoller::class,
            'content-types' => \Pilot\Core\Livewire\Admin\ContentTypes\Index::class,
            'datasources' => \Pilot\Core\Livewire\Admin\Datasources\Index::class,
            'settings' => \Pilot\Core\Livewire\Admin\Settings\Index::class,
            'spaces' => \Pilot\Core\Livewire\Admin\Spaces\Index::class,
            'spaces.create' => \Pilot\Core\Livewire\Admin\Spaces\Create::class,
            'spaces.edit' => \Pilot\Core\Livewire\Admin\Spaces\Edit::class,
            'users' => \Pilot\Core\Livewire\Admin\Users\Index::class,
        ];

        foreach ($components as $name => $class) {
            Livewire::component('admin.'.$name, $class);
            Livewire::component('pilot.core.livewire.admin.'.$name, $class);
        }

        if (config('cms.routes.admin', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }

        if (config('cms.routes.api', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        if (config('cms.routes.setup', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/setup.php');
        }

        if (config('cms.routes.settings', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/settings.php');
        }

        if (config('cms.routes.docs', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/docs.php');
        }

        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (Str::startsWith($modelName, 'Pilot\\Core\\Models\\')) {
                return 'Database\\Factories\\'.class_basename($modelName).'Factory';
            }

            $appNamespace = $this->app->getNamespace();
            $relativeName = Str::startsWith($modelName, $appNamespace.'Models\\')
                ? Str::after($modelName, $appNamespace.'Models\\')
                : Str::after($modelName, $appNamespace);

            return 'Database\\Factories\\'.$relativeName.'Factory';
        });

        Factory::guessModelNamesUsing(function (Factory $factory): string {
            $model = Str::replaceLast('Factory', '', class_basename($factory));
            $coreModel = 'Pilot\\Core\\Models\\'.$model;

            if (class_exists($coreModel)) {
                return $coreModel;
            }

            $appModel = $this->app->getNamespace().'Models\\'.$model;

            return class_exists($appModel) ? $appModel : $this->app->getNamespace().$model;
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Schedule::command('pilot:publish-scheduled')->everyMinute();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillPublishedContentRevisions::class,
                FinalizePilotUpdate::class,
                GenerateAssetThumbnails::class,
                InstallPilot::class,
                PublishScheduledContent::class,
                SyncPilotHost::class,
                UpdatePilot::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/cms.php' => config_path('cms.php'),
            ], 'pilot-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pilot-core'),
            ], 'pilot-core-views');
        }
    }

    protected function configureApplicationDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Password::defaults(fn (): ?Password => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : null
        );
    }

    protected function configurePilotPreviewSecret(): void
    {
        if (config('pilot.preview.secret')) {
            return;
        }

        try {
            if (Schema::hasTable('cms_settings')) {
                $secret = CmsSetting::get('preview_secret');

                if (is_string($secret) && $secret !== '') {
                    config(['pilot.preview.secret' => $secret]);
                }
            }
        } catch (QueryException) {
        }
    }
}
