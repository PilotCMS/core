# Pilot extension entry points

Pilot extensions are Laravel packages that add optional functionality while leaving the built-in Pilot application intact. An extension owns its routes, Livewire components, migrations, configuration, authorization rules, and domain behavior. `pilotcms/core` provides additive entry points that let the package appear naturally in the Pilot administration interface.

The current entry points support:

- Workspace or Admin sidebar links
- Command-palette links and search
- Browser page titles for extension routes
- Permission-aware visibility
- Deterministic ordering between extension contributions

Pilot's built-in navigation and commands remain hard-coded. Registered extension items are appended to them.

## Package setup

An extension should be an auto-discovered Composer package with its own Laravel service provider:

```json
{
    "name": "pilotcms/mcp",
    "require": {
        "php": "^8.4.1",
        "pilotcms/core": "^0.2.9"
    },
    "autoload": {
        "psr-4": {
            "Pilot\\Mcp\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Pilot\\Mcp\\McpServiceProvider"
            ]
        }
    }
}
```

Register UI contributions in the provider's `boot` method. By that point Laravel has registered all package providers and the shared registry is available for dependency injection.

```php
<?php

namespace Pilot\Mcp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Pilot\Core\Extensions\ExtensionRegistry;
use Pilot\Mcp\Livewire\Settings;

class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mcp.php', 'pilot-mcp');
    }

    public function boot(ExtensionRegistry $extensions): void
    {
        Livewire::component('pilot.mcp.settings', Settings::class);

        Route::prefix('admin/extensions')
            ->name('admin.extensions.')
            ->middleware(['web', 'auth'])
            ->group(function (): void {
                Route::get('/mcp', Settings::class)
                    ->middleware('permission:manage mcp')
                    ->name('mcp.index');
            });

        $extensions
            ->navigationItem(
                section: 'admin',
                route: 'admin.extensions.mcp.index',
                label: 'MCP Server',
                icon: 'plug',
                active: 'admin.extensions.mcp.*',
                permission: 'manage mcp',
            )
            ->commandPaletteItem(
                group: 'Extensions',
                route: 'admin.extensions.mcp.index',
                title: 'MCP Server',
                description: 'Configure AI client connections',
                icon: 'plug',
                permission: 'manage mcp',
            )
            ->pageTitle('admin.extensions.mcp.*', 'MCP Server');
    }
}
```

Routes referenced by navigation or command entries must be named and registered before the administration interface is rendered. An extension should use icons available in Pilot's Jaunt icon set.

## Sidebar navigation

Use `navigationItem` to append a link to one of Pilot's existing sidebar sections:

```php
$extensions->navigationItem(
    section: 'workspace',
    route: 'admin.analytics.index',
    label: 'Analytics',
    icon: 'chart-no-axes-column',
    active: 'admin.analytics.*',
    permission: 'view analytics',
    order: 100,
);
```

Parameters:

| Parameter | Required | Description |
| --- | --- | --- |
| `section` | Yes | Either `workspace` or `admin`. Other values throw an exception. |
| `route` | Yes | The named Laravel route used to generate the link. |
| `label` | Yes | User-facing link text. |
| `icon` | Yes | A Jaunt icon name. |
| `active` | No | A `request()->routeIs()` pattern. Defaults to the route name. |
| `permission` | No | A permission checked through the authenticated user's `can()` method. |
| `order` | No | Sort order among extension items in the same section. Defaults to `100`. |

Extension items always appear after Pilot's built-in items. Items with the same section and route replace the earlier extension registration, which prevents duplicate links when a package is booted more than once in a test environment.

## Command palette

Use `commandPaletteItem` to make an extension route available through Command-K:

```php
$extensions->commandPaletteItem(
    group: 'Extensions',
    route: 'admin.analytics.index',
    title: 'Analytics',
    description: 'Review content performance',
    icon: 'chart-no-axes-column',
    permission: 'view analytics',
    order: 100,
);
```

Parameters:

| Parameter | Required | Description |
| --- | --- | --- |
| `group` | Yes | Palette group label. `Go to` and `Admin` merge into the existing groups; any other label creates an extension group. |
| `route` | Yes | The named Laravel route used to generate the result URL. |
| `title` | Yes | Primary result label. |
| `description` | Yes | Supporting text and additional searchable content. |
| `icon` | Yes | A Jaunt icon name. |
| `permission` | No | A permission checked through the authenticated user's `can()` method. |
| `order` | No | Sort order among extension commands. Defaults to `100`. |

Visible extension commands appear in the palette's initial quick links. Search matches the title and description case-insensitively. Items with the same group and route replace the earlier extension registration.

## Page titles

Use `pageTitle` to supply the browser title for routes not known to Pilot core:

```php
$extensions->pageTitle('admin.analytics.*', 'Analytics');
```

The first matching pattern is returned using Laravel's `routeIs` behavior, including `*` wildcards. Within the Pilot administration routes, the rendered browser title becomes:

```text
Analytics · Pilot CMS
```

An explicit title supplied by a page or layout still takes precedence over the registered extension title.

## Authorization

The registry controls whether links are visible; it does not authorize the destination. Always apply the same permission to the extension route or component:

```php
Route::get('/analytics', Analytics::class)
    ->middleware('permission:view analytics')
    ->name('admin.analytics.index');
```

This prevents a user from bypassing navigation visibility by entering the URL directly. Extensions are responsible for creating or synchronizing any permissions they declare.

## Extension boundaries

The registry is intentionally limited to discovery inside Pilot's existing shell. An extension package remains responsible for:

- Composer installation and version compatibility
- Enable or disable state, when the extension supports it
- Routes and route middleware
- Livewire components, controllers, and views
- Database migrations and configuration
- Permission creation and assignment
- Auditing and safe use of Pilot domain services
- Tests for the extension's behavior

For write-capable integrations, call Pilot's lifecycle or application services instead of updating models directly. This preserves revisions, references, workflow state, and other CMS behavior.

## Testing an extension

An integration test should register the package provider, authenticate a user with the relevant permission, and verify both discoverability and route authorization:

```php
$this->actingAs($admin)
    ->get(route('admin.extensions.mcp.index'))
    ->assertOk()
    ->assertSee('MCP Server')
    ->assertSee('<title>MCP Server · Pilot CMS</title>', false);

Livewire::actingAs($admin)
    ->test(\Pilot\Core\Livewire\Admin\CommandPalette::class)
    ->set('search', 'AI client')
    ->assertSee('MCP Server');
```

Also test a user without the permission to ensure the link and command are hidden and the route returns `403`.
