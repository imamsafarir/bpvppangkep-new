<?php

use Illuminate\Support\Facades\Route;
use Modules\Sosmedhub\Http\Controllers\SosmedhubController;

/*
|--------------------------------------------------------------------------
| Admin Sosmedhub Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:medsos_planner,medsos_editor,medsos_instruktur,medsos_admin_platform'])->prefix('admin/sosmedhub')->name('admin.sosmedhub.')->group(function () {
    Route::get('/', [SosmedhubController::class, 'index'])->name('index');
    Route::post('/', [SosmedhubController::class, 'store'])->name('store');
    Route::post('/bulk', [SosmedhubController::class, 'bulkAction'])->name('bulk');
    Route::get('/export', [SosmedhubController::class, 'exportCsv'])->name('export');
    Route::post('/upload-media', [SosmedhubController::class, 'uploadMedia'])->name('upload_media');

    Route::put('/{content}', [SosmedhubController::class, 'update'])->name('update');
    Route::delete('/{content}', [SosmedhubController::class, 'destroy'])->name('destroy');
    Route::patch('/{content}/status', [SosmedhubController::class, 'updateStatus'])->name('status');

    // Revisi & Komentar Tim
    Route::post('/{content}/revisions', [SosmedhubController::class, 'addRevision'])->name('revisions.store');
    Route::post('/{content}/comments', [SosmedhubController::class, 'addComment'])->name('comments.store');

    // Platform & Social Settings
    Route::patch('/platforms/{platform}/toggle', [SosmedhubController::class, 'togglePlatform'])->name('platforms.toggle');
    Route::post('/settings/social', [SosmedhubController::class, 'saveSocialSetting'])->name('settings.social');
});

/*
|--------------------------------------------------------------------------
| Legacy / Quick Navigation Redirects
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:medsos_planner,medsos_editor,medsos_instruktur,medsos_admin_platform'])->group(function () {
    Route::get('/sosmed/kalender', fn() => redirect('/admin/sosmedhub?tab=kalender'))->name('sosmed.kalender');
    Route::get('/sosmed/daftar', fn() => redirect('/admin/sosmedhub?tab=daftar'))->name('sosmed.daftar');
    Route::get('/sosmed/planner', fn() => redirect('/admin/sosmedhub?tab=daftar&stage=planner'))->name('sosmed.planner');
    Route::get('/sosmed/editor', fn() => redirect('/admin/sosmedhub?tab=daftar&stage=editor'))->name('sosmed.editor');
    Route::get('/sosmed/analitik', fn() => redirect('/admin/sosmedhub?tab=statistik_medsos'))->name('sosmed.analitik');
});
