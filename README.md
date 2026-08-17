# Pilot Core

The versioned CMS application used by [Pilot](https://github.com/PilotCMS/Pilot). It contains Pilot's domain models, migrations, lifecycle services, administration routes, Livewire components, API controllers, views, frontend sources, GraphQL implementation, seeders, and maintenance commands.

The `PilotCMS/Pilot` repository is a thin Laravel host. Product functionality belongs here so an existing installation updated to a release has the same managed application code as a fresh installation at that release.

Applications should install Pilot through the `pilot` installer rather than requiring this package directly.

## Updating

From an existing Pilot project:

```shell
pilot update
```

Or use the application command directly:

```shell
php artisan pilot:update
```

Updates install Core and its compatible `pilot/laravel` dependency, migrate the host integration, install the managed frontend dependencies, run database migrations, rebuild frontend assets, and clear application caches. The host migration is idempotent and can be run directly with:

```shell
php artisan pilot:sync-host
```
