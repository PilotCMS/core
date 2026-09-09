<?php

use Pilot\Core\Support\Cms\LocalAssetImageUrlGenerator;

return [
    'routes' => [
        'admin' => env('PILOT_ADMIN_ROUTES', true),
        'api' => env('PILOT_API_ROUTES', true),
        'images' => env('PILOT_IMAGE_ROUTES', true),
        'setup' => env('PILOT_SETUP_ROUTES', true),
        'settings' => env('PILOT_SETTINGS_ROUTES', true),
        'docs' => env('PILOT_DOCS_ROUTES', true),
    ],
    'images' => [
        'driver' => env('PILOT_IMAGE_DRIVER', 'local'),
        'drivers' => [
            'local' => LocalAssetImageUrlGenerator::class,
        ],
        'max_width' => (int) env('PILOT_IMAGE_MAX_WIDTH', 4096),
        'max_height' => (int) env('PILOT_IMAGE_MAX_HEIGHT', 4096),
        'max_pixels' => (int) env('PILOT_IMAGE_MAX_PIXELS', 16777216),
        'default_quality' => (int) env('PILOT_IMAGE_QUALITY', 80),
        'allow_upscale' => env('PILOT_IMAGE_ALLOW_UPSCALE', false),
        'cache_control' => env('PILOT_IMAGE_CACHE_CONTROL', 'public, max-age=31536000, immutable'),
    ],
    'default_locale' => env('CMS_DEFAULT_LOCALE', env('APP_LOCALE', 'en')),
    'auto_revision_retention' => (int) env('CMS_AUTO_REVISION_RETENTION', 30),
    'updates' => [
        'api_url' => env('PILOT_UPDATE_API_URL', 'https://api.github.com/repos/PilotCMS/core/releases/latest'),
        'cache_ttl' => (int) env('PILOT_UPDATE_CACHE_TTL', 3600),
        'php_binary' => env('PILOT_UPDATE_PHP_BINARY'),
        'self_update' => env('PILOT_SELF_UPDATE', env('APP_ENV', 'production') === 'local'),
        'stale_after' => (int) env('PILOT_UPDATE_STALE_AFTER', 3600),
        'database_backup' => env('PILOT_UPDATE_DATABASE_BACKUP', true),
        'backup_retention' => (int) env('PILOT_UPDATE_BACKUP_RETENTION', 3),
        'minimum_free_mb' => (int) env('PILOT_UPDATE_MINIMUM_FREE_MB', 512),
        'maintenance_mode' => env('PILOT_UPDATE_MAINTENANCE_MODE', true),
        'health_path' => env('PILOT_UPDATE_HEALTH_PATH', '/login'),
        'health_url' => env('PILOT_UPDATE_HEALTH_URL'),
        'health_timeout' => (int) env('PILOT_UPDATE_HEALTH_TIMEOUT', 15),
    ],
];
