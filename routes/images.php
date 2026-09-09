<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Pilot\Core\Http\Controllers\AssetImageController;

Route::get('/assets/{asset}/{filename}', AssetImageController::class)
    ->whereNumber('asset')
    ->middleware([SubstituteBindings::class, 'throttle:120,1'])
    ->name('pilot.assets.image');
