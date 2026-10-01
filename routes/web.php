<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - Portal Inti
|--------------------------------------------------------------------------
*/

// ── Guest routes (Login Portal) ─────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// ── Auth routes (Portal Dashboard & Sistem) ─────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::put('/user/password', [PasswordController::class, 'update'])->name('user.password.update');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_berita' => \Modules\Website\Models\BeritaDanGaleri::where('jenis', 'berita')->count(),
                'total_galeri' => \Modules\Website\Models\BeritaDanGaleri::where('jenis', 'galeri')->count(),
                'total_dokumen' => \Modules\Website\Models\InformasiPublik::count() + \Modules\Website\Models\Jdih::count(),
                'total_shortlinks' => \Modules\Shortlink\Models\Shortlink::count(),
                'total_clicks' => (int) \Modules\Shortlink\Models\Shortlink::sum('clicks_count'),
                'total_users' => \App\Models\User::count(),
                'total_courses' => \Modules\Lms\Models\Course::count(),
                'total_participants' => \Modules\Lms\Models\Participant::count(),
            ],
        ]);
    })->name('dashboard');

    // Manajemen Pengguna & Hak Akses (Level Portal / Multi-Modul) - Khusus Super Admin
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserController::class, 'bulkAction'])->name('users.bulk');
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
