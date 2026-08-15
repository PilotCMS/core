<?php

return [
    'default_space' => env('CMS_DEFAULT_SPACE'),
    'home_slug' => env('CMS_HOME_SLUG', 'home'),
    'delivery_source' => env('CMS_DELIVERY_SOURCE', 'mysql'),
    'auto_revision_retention' => (int) env('CMS_AUTO_REVISION_RETENTION', 30),
];
