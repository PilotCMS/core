# Pilot Core

The versioned CMS engine used by [Pilot](https://github.com/PilotCMS/Pilot). It contains Pilot's domain models, migrations, lifecycle services, GraphQL implementation, seeders, and maintenance commands.

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
