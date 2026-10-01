@extends('errors.layout')

@section('title', 'Akses Ditolak (Forbidden)')
@section('code', '403')
@section('theme_color', 'red')
@section('badge_text', 'Akses Ditolak')
@section('icon', 'fa-solid fa-shield-halved')

@section('message')
    Mohon maaf, Anda tidak memiliki izin atau wewenang hak akses (role/permission) yang memadai untuk mengakses halaman atau
    modul internal ini.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Pastikan Anda masuk menggunakan akun dengan peran yang sesuai (misal Administrator / Staf
            Terotorisasi).</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Jika Anda merasa akun Anda seharusnya memiliki akses ke fitur ini, silakan hubungi Koordinator TI BPVP
            Pangkep.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Gunakan menu navigasi dasbor resmi untuk membuka fitur-fitur yang tersedia bagi akun Anda.</span>
    </div>
@endsection

@section('actions')
    @auth
        <a href="/dashboard"
            class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
            <i class="fa-solid fa-gauge"></i>
            <span>Kembali ke Dasbor</span>
        </a>
    @else
        <a href="/"
            class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
            <i class="fa-solid fa-house"></i>
            <span>Kembali ke Beranda</span>
        </a>
    @endauth

    <button type="button" onclick="window.history.back()"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Halaman Sebelumnya</span>
    </button>

    @if (!empty($waClean))
        <a href="https://wa.me/{{ $waClean }}?text=Halo%20Admin%20BPVP%20Pangkep,%20akun%20saya%20terkendala%20Akses%20Ditolak%20(Error%20403)."
            target="_blank" rel="noopener noreferrer"
            class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-emerald-400 text-sm flex items-center justify-center gap-2">
            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
            <span>Hubungi Admin</span>
        </a>
    @endif
@endsection
