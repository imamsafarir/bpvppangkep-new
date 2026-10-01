@extends('errors.layout')

@section('title', 'Waktu Tunggu Server Berakhir (504 Gateway Timeout)')
@section('code', '504')
@section('theme_color', 'blue')
@section('badge_text', 'Gateway Timeout')
@section('icon', 'fa-solid fa-hourglass-end')

@section('message')
    Server gateway tidak menerima tanggapan tepat waktu dari server pemroses data. Hal ini dapat terjadi ketika server
    sedang menangani antrean transaksi yang padat atau permintaan memerlukan waktu olah yang lama.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Tunggu beberapa menit sebelum mencoba mengakses atau memuat ulang halaman ini kembali.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Jika Anda sedang memproses dokumen berukuran besar atau laporan, pastikan ukuran data tidak melebihi kapasitas
            sistem.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Jangan kirimkan data formulir secara berulang-ulang untuk menghindari duplikasi transaksi.</span>
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
