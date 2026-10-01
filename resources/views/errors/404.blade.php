@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan (404 Not Found)')
@section('code', '404')
@section('theme_color', 'blue')
@section('badge_text', 'Halaman Tidak Ditemukan')
@section('icon', 'fa-solid fa-compass')

@section('message')
    Mohon maaf, halaman atau tautan yang Anda tuju tidak dapat kami temukan. Halaman tersebut mungkin telah dihapus,
    dipindahkan ke alamat baru, atau terdapat kekeliruan ketik pada tautan.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Periksa kembali penulisan alamat web (URL) pada bilah peramban Anda untuk memastikan tidak ada huruf yang
            terlewat.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Jika Anda membuka tautan dari media sosial atau pihak ketiga, kemungkinan tautan tersebut telah diperbarui ke
            struktur baru.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Kunjungi halaman utama untuk menemukan program pelatihan vokasi, sertifikasi, layanan PPID, atau informasi
            berita terbaru.</span>
    </div>
@endsection

@section('actions')
    <a href="/"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-house"></i>
        <span>Kembali ke Beranda Utama</span>
    </a>

    <button type="button" onclick="window.history.back()"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Halaman Sebelumnya</span>
    </button>

    <a href="/login"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-user"></i>
        <span>Portal Masuk</span>
    </a>
@endsection
