<?php

use Illuminate\Support\Facades\Route;
use Pilot\Core\Http\Controllers\Admin\ContentPreviewController;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Dashboard;
use Pilot\Core\Livewire\Admin\Spaces\Create;
use Pilot\Core\Livewire\Admin\Spaces\Edit;
use Pilot\Core\Livewire\Admin\Spaces\Index;

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Spaces
    Route::get('/spaces', Index::class)->name('spaces.index')->middleware('role:Admin');
    Route::get('/spaces/create', Create::class)->name('spaces.create')->middleware('role:Admin');
    Route::get('/spaces/{space}/edit', Edit::class)->name('spaces.edit')->middleware('role:Admin');

    // Content
    Route::get('/content', Pilot\Core\Livewire\Admin\Content\Index::class)->name('content.index');
    Route::get('/content/create', Pilot\Core\Livewire\Admin\Content\Create::class)->name('content.create');
    Route::get('/content/{content}/edit', Editor::class)->name('content.edit');
    Route::get('/content/{content}/editor', Editor::class)->name('content.editor');
    Route::get('/content/{content}/preview', ContentPreviewController::class)->name('content.preview');
    Route::get('/content-types', Pilot\Core\Livewire\Admin\ContentTypes\Index::class)->name('content-types.index')->middleware('role:Admin');

    // Block Types
    Route::get('/blocks', Pilot\Core\Livewire\Admin\Blocks\Index::class)->name('blocks.index')->middleware('role:Admin');
    Route::get('/blocks/create', Pilot\Core\Livewire\Admin\Blocks\Create::class)->name('blocks.create')->middleware('role:Admin');
    Route::get('/blocks/{blockType}/edit', Pilot\Core\Livewire\Admin\Blocks\Edit::class)->name('blocks.edit')->middleware('role:Admin');

    // Assets
    Route::get('/assets', Pilot\Core\Livewire\Admin\Assets\Index::class)->name('assets.index');

    // Datasources
    Route::get('/datasources', Pilot\Core\Livewire\Admin\Datasources\Index::class)->name('datasources.index');

    // Users
    Route::get('/users', Pilot\Core\Livewire\Admin\Users\Index::class)->name('users.index')->middleware('role:Admin');

    // Settings
    Route::get('/settings', Pilot\Core\Livewire\Admin\Settings\Index::class)->name('settings.index')->middleware('role:Admin');
});
