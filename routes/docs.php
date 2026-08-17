<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs', fn () => response(
    file_get_contents(__DIR__.'/../resources/docs/index.html'),
    200,
    ['Content-Type' => 'text/html; charset=UTF-8'],
))
    ->middleware('web')
    ->name('pilot.docs');
