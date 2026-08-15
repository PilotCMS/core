<?php

namespace Pilot\Core;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Pilot\Core\Console\Commands\BackfillPublishedContentRevisions;
use Pilot\Core\Console\Commands\GenerateAssetThumbnails;
use Pilot\Core\Console\Commands\PublishScheduledContent;
use Pilot\Core\Console\Commands\UpdatePilot;

class PilotCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cms.php', 'cms');
    }

    public function boot(): void
    {
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillPublishedContentRevisions::class,
                GenerateAssetThumbnails::class,
                PublishScheduledContent::class,
                UpdatePilot::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/cms.php' => config_path('cms.php'),
            ], 'pilot-config');
        }
    }
}
