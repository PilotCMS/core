<?php

return [
    'routes' => [
        'admin' => env('PILOT_ADMIN_ROUTES', true),
        'api' => env('PILOT_API_ROUTES', true),
        'setup' => env('PILOT_SETUP_ROUTES', true),
        'settings' => env('PILOT_SETTINGS_ROUTES', true),
        'docs' => env('PILOT_DOCS_ROUTES', true),
    ],
    'default_space' => env('CMS_DEFAULT_SPACE'),
    'home_slug' => env('CMS_HOME_SLUG', 'home'),
    'delivery_source' => env('CMS_DELIVERY_SOURCE', 'mysql'),
    'auto_revision_retention' => (int) env('CMS_AUTO_REVISION_RETENTION', 30),
    'updates' => [
        'api_url' => env('PILOT_UPDATE_API_URL', 'https://api.github.com/repos/PilotCMS/core/releases/latest'),
        'cache_ttl' => (int) env('PILOT_UPDATE_CACHE_TTL', 3600),
        'self_update' => env('PILOT_SELF_UPDATE', env('APP_ENV', 'production') === 'local'),
        'stale_after' => (int) env('PILOT_UPDATE_STALE_AFTER', 3600),
        'database_backup' => env('PILOT_UPDATE_DATABASE_BACKUP', true),
        'backup_retention' => (int) env('PILOT_UPDATE_BACKUP_RETENTION', 3),
        'minimum_free_mb' => (int) env('PILOT_UPDATE_MINIMUM_FREE_MB', 512),
        'maintenance_mode' => env('PILOT_UPDATE_MAINTENANCE_MODE', true),
        'health_url' => env('PILOT_UPDATE_HEALTH_URL'),
        'health_timeout' => (int) env('PILOT_UPDATE_HEALTH_TIMEOUT', 15),
    ],
];
