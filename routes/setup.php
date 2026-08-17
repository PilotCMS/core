<?php

use Illuminate\Support\Facades\Route;
use Pilot\Core\Http\Controllers\SetupController;

Route::middleware('web')->group(function () {
    Route::get('/setup/{step?}', [SetupController::class, 'show'])
        ->where('step', 'welcome|requirements|database|account|project|developer')
        ->name('setup.show');
    Route::post('/setup/database', [SetupController::class, 'database'])->name('setup.database');
    Route::post('/setup/account', [SetupController::class, 'account'])->name('setup.account');
    Route::post('/setup/project', [SetupController::class, 'project'])->name('setup.project');
    Route::post('/setup/finish', [SetupController::class, 'finish'])->name('setup.finish');
});
