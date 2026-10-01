@extends('errors.layout')

@section('title', 'Permintaan Tidak Valid (Bad Request)')
@section('code', '400')
@section('theme_color', 'amber')
@section('badge_text', 'Bad Request')
@section('icon', 'fa-solid fa-triangle-exclamation')

@section('message')
    Server tidak dapat memproses permintaan Anda karena format data, sintaks URL, atau parameter yang dikirimkan tidak
    sesuai dengan standar yang diharapkan sistem.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Periksa kembali tautan atau URL yang Anda tuju, pastikan tidak terdapat karakter yang salah atau
            terpotong.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Jika Anda sedang mengirimkan formulir atau berkas pendaftaran, pastikan seluruh format kolom dan ukuran berkas
            telah sesuai panduan.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-amber-400 mt-1 shrink-0"></i>
        <span>Coba bersihkan cache atau cookie browser Anda lalu ulangi langkah tersebut.</span>
    </div>
@endsection

@section('actions')
    <a href="/"
        class="btn-primary w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-house"></i>
        <span>Kembali ke Beranda</span>
    </a>

    <button type="button" onclick="window.history.back()"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Halaman Sebelumnya</span>
    </button>

    <button type="button" onclick="window.location.reload()"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-rotate-right"></i>
        <span>Muat Ulang</span>
    </button>
@endsection
