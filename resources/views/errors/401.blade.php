@extends('errors.layout')

@section('title', 'Autentikasi Diperlukan (Unauthorized)')
@section('code', '401')
@section('theme_color', 'cyan')
@section('badge_text', 'Autentikasi Diperlukan')
@section('icon', 'fa-solid fa-user-lock')

@section('message')
    Akses ke halaman atau fitur ini membutuhkan identitas terverifikasi. Sesi Anda mungkin belum aktif atau telah berakhir,
    silakan masuk ke akun Anda terlebih dahulu.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-cyan-400 mt-1 shrink-0"></i>
        <span>Masuk ke akun Anda menggunakan nama pengguna / email dan kata sandi yang telah terdaftar.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-cyan-400 mt-1 shrink-0"></i>
        <span>Jika sesi Anda sebelumnya terputus karena terlalu lama tidak aktif, lakukan login ulang untuk
            melanjutkan.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-cyan-400 mt-1 shrink-0"></i>
        <span>Jika Anda belum memiliki kredensial resmi atau lupa kata sandi, hubungi admin portal BPVP Pangkep.</span>
    </div>
@endsection

@section('actions')
    <a href="/login"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-right-to-bracket"></i>
        <span>Masuk / Login Akun</span>
    </a>

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
