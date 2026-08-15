<?php

namespace Pilot\Core;

use Illuminate\Support\ServiceProvider;
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
