@extends('errors.layout')

@php
    $rawCode = trim($__env->yieldContent('code', 'Error'));
    $rawMessage = trim($__env->yieldContent('message', 'Terjadi kesalahan saat memproses permintaan Anda.'));

    // Determine theme color based on status code range
    $theme = 'blue';
    if (str_starts_with($rawCode, '4')) {
        $theme = in_array($rawCode, ['401', '403']) ? 'red' : 'amber';
    } elseif (str_starts_with($rawCode, '5')) {
        $theme = 'red';
    }
@endphp

@section('theme_color', $theme)
@section('badge_text', 'Status ' . $rawCode)

@section('icon')
    @if (str_starts_with($rawCode, '4'))
        fa-solid fa-triangle-exclamation
    @else
        fa-solid fa-server
    @endif
@endsection

@section('title')
    @hasSection('title')
        @yield('title')
    @else
        Kendala Akses (Kode: {{ $rawCode }})
    @endif
@endsection

@section('message')
    {{ $rawMessage }}
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Pastikan alamat URL dan parameter permintaan yang Anda tuju sudah benar dan lengkap.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Coba muat ulang halaman ini atau kembali ke halaman beranda utama.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-sky-400 mt-1 shrink-0"></i>
        <span>Apabila masalah berlanjut, hubungi tim dukungan teknis BPVP Pangkep untuk bantuan lebih lanjut.</span>
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
