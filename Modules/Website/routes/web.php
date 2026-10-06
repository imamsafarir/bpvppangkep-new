<?php

use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\Admin\BeritaController;
use Modules\Website\Http\Controllers\Admin\InformasiController;
use Modules\Website\Http\Controllers\Admin\InformasiPublikController;
use Modules\Website\Http\Controllers\Admin\JdihController;
use Modules\Website\Http\Controllers\Admin\PelayananPublikController;
use Modules\Website\Http\Controllers\Admin\ProfilController;
use Modules\Website\Http\Controllers\Admin\WebsiteSettingController;
use Modules\Website\Http\Controllers\SitemapController;
use Modules\Website\Http\Controllers\WebsiteController;

/*
|--------------------------------------------------------------------------
| Web Routes - Modul Website
|--------------------------------------------------------------------------
*/

// ═════════════════════════════════════════════════════════════════════
// 1. RUTE PUBLIK WEBSITE
// ═════════════════════════════════════════════════════════════════════

// Sitemap XML
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Beranda
Route::get('/', [WebsiteController::class, 'home'])->name('home');

// Profil
Route::prefix('profil')->name('profil.')->group(function () {
    Route::get('/sambutan-kepala', [WebsiteController::class, 'sambutan'])->name('sambutan');
    Route::get('/tentang-kami', [WebsiteController::class, 'tentangKami'])->name('tentang');
    Route::get('/ppid-pelayanan', [WebsiteController::class, 'ppid'])->name('ppid');
    Route::get('/visi-misi', [WebsiteController::class, 'visiMisi'])->name('visi-misi');
    Route::get('/tugas-fungsi', [WebsiteController::class, 'tugasFungsi'])->name('tugas-fungsi');
    Route::get('/struktur-organisasi', [WebsiteController::class, 'struktur'])->name('struktur');
    Route::get('/pejabat-struktural', [WebsiteController::class, 'pejabat'])->name('pejabat');
});

// Informasi
Route::prefix('informasi')->name('informasi.')->group(function () {
    Route::get('/kejuruan', [WebsiteController::class, 'kejuruan'])->name('kejuruan');
    Route::get('/gedung-fasilitas', [WebsiteController::class, 'fasilitas'])->name('fasilitas');
    Route::get('/ruang-kelas-workshop', [WebsiteController::class, 'workshop'])->name('workshop');
    Route::get('/alumni', [WebsiteController::class, 'alumni'])->name('alumni');
    Route::get('/testimoni', [WebsiteController::class, 'testimoni'])->name('testimoni');
});

// Informasi Publik (PPID)
Route::prefix('informasi-publik')->name('informasi-publik.')->group(function () {
    Route::get('/berkala', [WebsiteController::class, 'informasiBerkala'])->name('berkala');
    Route::get('/serta-merta', [WebsiteController::class, 'informasiSertaMerta'])->name('serta-merta');
    Route::get('/setiap-saat', [WebsiteController::class, 'informasiSetiapSaat'])->name('setiap-saat');
    Route::get('/download/{id}', [WebsiteController::class, 'downloadInformasiPublik'])->name('download');
});

// Pelayanan Publik
Route::prefix('pelayanan-publik')->name('pelayanan-publik.')->group(function () {
    Route::get('/maklumat', [WebsiteController::class, 'maklumat'])->name('maklumat');
    Route::get('/standar-pelayanan', [WebsiteController::class, 'standar'])->name('standar');
    Route::get('/standar', [WebsiteController::class, 'standar']);
    Route::get('/alur-pelayanan', [WebsiteController::class, 'alur'])->name('alur');
    Route::get('/alur', [WebsiteController::class, 'alur']);
    Route::get('/survey-kepuasan', [WebsiteController::class, 'surveyKepuasan'])->name('survey-kepuasan');
    Route::get('/survey-kebutuhan', [WebsiteController::class, 'surveyKebutuhan'])->name('survey-kebutuhan');
    Route::get('/survey-kebekerjaan', [WebsiteController::class, 'surveyKebekerjaan'])->name('survey-kebekerjaan');
    Route::get('/indeks-kepuasan', [WebsiteController::class, 'indeksKepuasan'])->name('indeks-kepuasan');
});

// Berita & Galeri
Route::prefix('berita-informasi')->name('berita.')->group(function () {
    Route::get('/daftar-berita', [WebsiteController::class, 'beritaIndex'])->name('index');
    Route::get('/berita/{id}', [WebsiteController::class, 'beritaShow'])->name('show');
    Route::get('/galeri-kegiatan', [WebsiteController::class, 'galeriIndex'])->name('galeri');
});

// JDIH
Route::get('/jdih', [WebsiteController::class, 'jdihIndex'])->name('jdih.index');
Route::get('/jdih/download/{id}', [WebsiteController::class, 'downloadJdih'])->name('jdih.download');



// ═════════════════════════════════════════════════════════════════════
// 2. RUTE ADMIN MANAJEMEN WEBSITE (Auth Protected)
// ═════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'role:admin_website'])->prefix('admin')->name('admin.')->group(function () {
    // Berita & Galeri
    Route::get('/berita', [BeritaController::class, 'index'])->name('berita.index');
    Route::post('/berita', [BeritaController::class, 'store'])->name('berita.store');
    Route::post('/berita/bulk-delete', [BeritaController::class, 'bulkDestroy'])->name('berita.bulk-destroy');
    Route::match(['put', 'post'], '/berita/{beritaDanGaleri}', [BeritaController::class, 'update'])->name('berita.update');
    Route::delete('/berita/{beritaDanGaleri}', [BeritaController::class, 'destroy'])->name('berita.destroy');

    // Profil Balai
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::match(['put', 'post'], '/profil', [ProfilController::class, 'update'])->name('profil.update');

    // Informasi Balai (Kejuruan, Fasilitas, Workshop, Alumni, dll)
    Route::get('/informasi', [InformasiController::class, 'index'])->name('informasi.index');
    Route::match(['put', 'post'], '/informasi', [InformasiController::class, 'update'])->name('informasi.update');

    // Informasi Publik (PPID)
    Route::get('/informasi-publik', [InformasiPublikController::class, 'index'])->name('informasi-publik.index');
    Route::post('/informasi-publik', [InformasiPublikController::class, 'store'])->name('informasi-publik.store');
    Route::match(['put', 'post'], '/informasi-publik/{informasiPublik}', [InformasiPublikController::class, 'update'])->name('informasi-publik.update');
    Route::delete('/informasi-publik/{informasiPublik}', [InformasiPublikController::class, 'destroy'])->name('informasi-publik.destroy');

    // Produk Hukum (JDIH)
    Route::get('/jdih', [JdihController::class, 'index'])->name('jdih.index');
    Route::post('/jdih', [JdihController::class, 'store'])->name('jdih.store');
    Route::match(['put', 'post'], '/jdih/{jdih}', [JdihController::class, 'update'])->name('jdih.update');
    Route::delete('/jdih/{jdih}', [JdihController::class, 'destroy'])->name('jdih.destroy');

    // Pelayanan Publik
    Route::get('/pelayanan', [PelayananPublikController::class, 'index'])->name('pelayanan.index');
    Route::match(['put', 'post'], '/pelayanan', [PelayananPublikController::class, 'update'])->name('pelayanan.update');


    // Konfigurasi Website
    Route::get('/settings', [WebsiteSettingController::class, 'index'])->name('settings.index');
    Route::match(['put', 'post'], '/settings', [WebsiteSettingController::class, 'update'])->name('settings.update');

    // Upload Media Helper
    Route::post('/upload-media', [WebsiteSettingController::class, 'uploadMedia'])->name('upload.media');
});
