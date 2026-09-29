<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortlink\Http\Controllers\PublicShortlinkController;

/*
|--------------------------------------------------------------------------
| Shortlink API & Feeds
|--------------------------------------------------------------------------
*/
// Leads Feed
Route::get('/shortlink/feed.csv', [PublicShortlinkController::class, 'feedCsv'])->name('feed.csv');
Route::get('/shortlink/feed.json', [PublicShortlinkController::class, 'feedJson'])->name('feed.json');

// Shortlinks List Feed
Route::get('/shortlink/shortlinks.csv', [PublicShortlinkController::class, 'shortlinksFeedCsv'])->name('shortlinks.feed.csv');
Route::get('/shortlink/shortlinks.json', [PublicShortlinkController::class, 'shortlinksFeedJson'])->name('shortlinks.feed.json');
