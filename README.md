# Pilot Core

The versioned CMS application used by [Pilot](https://github.com/PilotCMS/Pilot). It contains Pilot's domain models, migrations, lifecycle services, administration routes, Livewire components, delivery API controllers, admin views and assets, GraphQL implementation, seeders, and maintenance commands.

The `PilotCMS/Pilot` repository is a thin Laravel host. Product functionality belongs here so an existing installation updated to a release has the same managed application code as a fresh installation at that release.

Applications should install Pilot through the `pilot` installer rather than requiring this package directly.

## Extensions

First-party and third-party packages can add administration links, command-palette entries, and page titles without replacing Pilot's built-in application surface. See the [extension authoring guide](docs/extensions.md) for the supported API and a complete service-provider example.

## Updating

From an existing Pilot project:

```shell
pilot update
```

Or use the application command directly:

```shell
php artisan pilot:update
```

Updates install Core and its compatible `pilot/laravel` dependency, migrate the host integration, install the managed admin dependencies, run database migrations, rebuild admin assets, and clear application caches. Client-facing routes, views, and themes belong to the consuming application. The host migration is idempotent and can be run directly with:

```shell
php artisan pilot:sync-host
```

## Background update PHP

Admin updates resolve and validate a PHP CLI executable before starting. On servers with multiple PHP versions, set `PILOT_UPDATE_PHP_BINARY=/usr/bin/php8.5` (using the installed CLI path). PHP-FPM executables cannot run Artisan updates. Startup output is captured in `storage/logs/pilot-update-launcher.log` and included in the admin update log; a queued update that does not start within 30 seconds is marked failed.
