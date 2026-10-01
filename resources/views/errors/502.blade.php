@extends('errors.layout')

@section('title', 'Gerbang Komunikasi Bermasalah (502 Bad Gateway)')
@section('code', '502')
@section('theme_color', 'amber')
@section('badge_text', 'Bad Gateway')
@section('icon', 'fa-solid fa-network-wired')

@section('message')
    Server perantara (gateway / proxy) menerima tanggapan yang tidak valid dari server backend aplikasi. Hal ini biasanya
    terjadi saat server aplikasi sedang dalam proses restart atau ada beban lonjakan sesaat.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Tunggu sekitar 15 - 30 detik lalu muat ulang halaman ini. Layanan biasanya segera pulih otomatis.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Pastikan koneksi internet di perangkat Anda berjalan lancar dan stabil.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Jika kendala berlanjut lebih dari 5 menit, kemungkinan sedang berlangsung pembaruan infrastruktur
            server.</span>
    </div>
@endsection

@section('actions')
    <button type="button" onclick="window.location.reload()"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-rotate-right"></i>
        <span>Muat Ulang Halaman</span>
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
