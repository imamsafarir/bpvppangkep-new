<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortlink\Http\Controllers\Admin\ShortlinkAdminController;
use Modules\Shortlink\Http\Controllers\PublicShortlinkController;

/*
|--------------------------------------------------------------------------
| Public Shortlink Redirection & Capture
|--------------------------------------------------------------------------
*/

Route::get('/s/{code}', [PublicShortlinkController::class, 'redirect'])->name('shortlink.capture');
Route::post('/s/{code}', [PublicShortlinkController::class, 'submitCapture'])->name('shortlink.submit');
Route::get('/s/{code}/thank-you', [PublicShortlinkController::class, 'thankyou'])->name('shortlink.thankyou');

/*
|--------------------------------------------------------------------------
| Admin Shortlink Management (Protected by Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin_shortlink'])->prefix('admin/shortlinks')->name('admin.shortlinks.')->group(function () {
    Route::get('/', [ShortlinkAdminController::class, 'index'])->name('index');
    Route::post('/', [ShortlinkAdminController::class, 'store'])->name('store');

    // Bulk actions
    Route::post('/bulk', [ShortlinkAdminController::class, 'bulkAction'])->name('bulk');
    Route::post('/leads/bulk', [ShortlinkAdminController::class, 'bulkActionLeads'])->name('leads.bulk');

    // Import & Export Shortlinks
    Route::get('/export/shortlinks', [ShortlinkAdminController::class, 'exportShortlinks'])->name('export');
    Route::get('/template/download', [ShortlinkAdminController::class, 'downloadTemplate'])->name('template');
    Route::post('/import', [ShortlinkAdminController::class, 'importShortlinks'])->name('import');

    // Leads & Export
    Route::get('/export/leads', [ShortlinkAdminController::class, 'exportLeads'])->name('leads.export');
    Route::delete('/leads/{lead}', [ShortlinkAdminController::class, 'destroyLead'])->name('leads.destroy');

    // Integration Settings
    Route::post('/settings/regenerate-token', [ShortlinkAdminController::class, 'regenerateToken'])->name('token.regenerate');
    Route::post('/settings/webhook', [ShortlinkAdminController::class, 'updateWebhook'])->name('webhook.update');
    Route::post('/settings/test-webhook', [ShortlinkAdminController::class, 'testWebhook'])->name('webhook.test');

    // Resource routes
    Route::put('/{shortlink}', [ShortlinkAdminController::class, 'update'])->name('update');
    Route::patch('/{shortlink}/toggle', [ShortlinkAdminController::class, 'toggle'])->name('toggle');
    Route::delete('/{shortlink}', [ShortlinkAdminController::class, 'destroy'])->name('destroy');
});
