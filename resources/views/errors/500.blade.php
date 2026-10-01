@extends('errors.layout')

@section('title', 'Kendala Internal Server (500 Server Error)')
@section('code', '500')
@section('theme_color', 'red')
@section('badge_text', 'Gangguan Internal Server')
@section('icon', 'fa-solid fa-server')

@section('message')
    Terjadi kendala teknis yang tidak terduga pada server aplikasi kami saat memproses permintaan Anda. Sistem telah
    mencatat log diagnostik dan tim teknis sedang memeriksa masalah ini.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Kendala ini terjadi di sisi server sistem, sehingga Anda tidak perlu khawatir tentang kesalahan konfigurasi di
            perangkat Anda.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Silakan tunggu beberapa saat lalu coba muat ulang (refresh) halaman untuk melihat apakah kendala telah
            terselesaikan.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-rose-400 mt-1 shrink-0"></i>
        <span>Jika kendala mendesak terkait pendaftaran pelatihan atau layanan publik, hubungi kami melalui kanal bantuan
            resmi di bawah.</span>
    </div>
@endsection

@section('actions')
    <button type="button" onclick="window.location.reload()"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-rotate-right"></i>
        <span>Coba Muat Ulang Halaman</span>
    </button>

    <a href="/"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-house"></i>
        <span>Kembali ke Beranda</span>
    </a>

    @if (!empty($waClean))
        <a href="https://wa.me/{{ $waClean }}?text=Halo%20Admin%20BPVP%20Pangkep,%20saya%20menemukan%20kendala%20Internal%20Server%20Error%20(500)%20pada%20portal."
            target="_blank" rel="noopener noreferrer"
            class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-emerald-400 text-sm flex items-center justify-center gap-2">
            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
            <span>Lapor ke Layanan</span>
        </a>
    @endif
@endsection
