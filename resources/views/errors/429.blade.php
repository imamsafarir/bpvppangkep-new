@extends('errors.layout')

@section('title', 'Terlalu Banyak Permintaan (429 Rate Limit)')
@section('code', '429')
@section('theme_color', 'purple')
@section('badge_text', 'Batas Permintaan Terlampaui')
@section('icon', 'fa-solid fa-gauge-high')

@section('message')
    Sistem mendeteksi frekuensi permintaan yang terlalu cepat dari koneksi Anda. Pembatasan laju sementara (rate limiting)
    diaktifkan demi menjaga stabilitas dan ketersediaan layanan bersama.
@endsection

@section('suggestions')
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-clock text-purple-400 mt-1 shrink-0"></i>
        <span>Harap tunggu beberapa detik sebelum mencoba kembali. Pembatasan ini akan dicabut secara otomatis dalam waktu
            singkat.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-purple-400 mt-1 shrink-0"></i>
        <span>Hindari menekan tombol klik berkali-kali atau memuat ulang browser secara terus-menerus.</span>
    </div>
    <div class="flex items-start gap-2.5">
        <i class="fa-solid fa-network-wired text-purple-400 mt-1 shrink-0"></i>
        <span>Bila Anda berada di jaringan kantor atau Wi-Fi bersama, pengguna lain pada IP publik yang sama mungkin sedang
            melakukan akses intensif.</span>
    </div>

    <!-- Countdown Timer Component -->
    <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs text-purple-300">
        <span class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
            <span>Dapat mencoba kembali dalam:</span>
        </span>
        <span id="rate-timer"
            class="font-mono font-bold text-sm bg-purple-950/80 px-2.5 py-0.5 rounded border border-purple-500/30 text-purple-200">30s</span>
    </div>
@endsection

@section('actions')
    <button id="btn-retry" disabled type="button" onclick="window.location.reload()"
        class="btn-primary opacity-60 cursor-not-allowed w-full sm:w-auto px-6 py-3 rounded-xl font-semibold text-white text-sm flex items-center justify-center gap-2 transition-all">
        <i class="fa-solid fa-rotate-right"></i>
        <span id="btn-text">Menunggu Waktu Tunggu...</span>
    </button>

    <a href="/"
        class="btn-secondary w-full sm:w-auto px-5 py-3 rounded-xl font-medium text-slate-300 hover:text-white text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-house"></i>
        <span>Kembali ke Beranda</span>
    </a>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let seconds = 30;
            const timerEl = document.getElementById('rate-timer');
            const btn = document.getElementById('btn-retry');
            const btnText = document.getElementById('btn-text');

            const interval = setInterval(() => {
                seconds--;
                if (timerEl) timerEl.textContent = seconds + 's';
                if (seconds <= 0) {
                    clearInterval(interval);
                    if (timerEl) timerEl.textContent = 'Siap!';
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('opacity-60', 'cursor-not-allowed');
                        if (btnText) btnText.textContent = 'Coba Sekarang';
                    }
                }
            }, 1000);
        });
    </script>
@endsection
