<?php

use Illuminate\Support\Facades\Route;
use Pilot\Core\Http\Controllers\Api\ContentController;
use Pilot\Core\Http\Controllers\Api\LivePreviewController;
use Pilot\Core\Http\Controllers\Api\PreviewController;

Route::prefix('api/v1')->middleware('api')->group(function () {
    Route::get('/spaces/{space}/contents', [ContentController::class, 'index']);
    Route::get('/spaces/{space}/contents/{slug}', [ContentController::class, 'show']);

    // Preview: signed URL returns draft content (no auth required, signature validates)
    Route::get('/preview/{content}', [PreviewController::class, 'show'])
        ->middleware('signed')
        ->name('api.preview.show');
    Route::post('/preview/render', LivePreviewController::class)
        ->name('api.preview.render');

    // Draft access is guarded inside ContentController so published delivery stays public.
});
