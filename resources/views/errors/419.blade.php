@extends('errors.layout')

@section('title', 'Sesi Halaman Kedaluwarsa (Page Expired)')
@section('code', '419')
@section('theme_color', 'amber')
@section('badge_text', 'Sesi Kedaluwarsa')
@section('icon', 'fa-solid fa-clock-rotate-left')

@section('message')
    Sesi keamanan halaman ini (CSRF Token) telah berakhir karena tab atau jendela browser dibiarkan terbuka dalam waktu yang
    cukup lama tanpa aktivitas pengiriman data.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-shield-halved text-amber-400 mt-1 shrink-0"></i>
        <span>Ini adalah mekanisme keamanan standar untuk melindungi data akun dan formulir Anda dari pengiriman pihak
            ketiga yang tidak sah.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Cukup klik tombol <strong>"Muat Ulang Halaman"</strong> di bawah untuk memperbarui sesi dan token keamanan
            secara otomatis.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Setelah memuat ulang, silakan isi kembali formulir atau coba aksi Anda sebelumnya.</span>
    </div>
@endsection

@section('actions')
    <button type="button" onclick="window.location.reload()"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-rotate-right"></i>
        <span>Muat Ulang Halaman Sekarang</span>
    </button>

    <a href="/"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-house"></i>
        <span>Kembali ke Beranda</span>
    </a>

    <button type="button" onclick="window.history.back()"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Halaman Sebelumnya</span>
    </button>
@endsection
