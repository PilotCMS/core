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
];
