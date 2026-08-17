<?php

return [
    'lock_file' => storage_path('app/pilot-installed.json'),

    // Feature tests normally exercise an already-installed application. Setup
    // tests explicitly disable this flag when they need the first-run state.
    'assume_installed_when_testing' => true,
];
