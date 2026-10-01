<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    @php
        $siteSettings = rescue(fn() => \Modules\Website\Models\WebsiteSetting::first(), null);
        $siteName = $siteSettings?->website_name ?? config('app.name', 'BPVP Pangkep');
        if (empty($siteName) || $siteName === 'Laravel') {
            $siteName = 'BPVP Pangkep';
        }
        $faviconPath = $siteSettings?->favicon_path;
        $faviconUrl = $faviconPath ? '/storage/' . ltrim($faviconPath, '/') : '/favicon.ico';
        $logoPath = $siteSettings?->logo_path;
        $logoUrl = $logoPath ? '/storage/' . ltrim($logoPath, '/') : '/icons/icon-192x192.png';
        $waNumber = $siteSettings?->whatsapp_number ?? '628114611444';
        $waClean = preg_replace('/[^0-9]/', '', $waNumber);
        $emailContact = $siteSettings?->email ?? 'bpvp.pangkep@kemnaker.go.id';
        $phoneContact = $siteSettings?->phone_number ?? '(0410) 2313333';
        $igUrl = $siteSettings?->instagram_url ?? 'https://www.instagram.com/bpvppangkep/';
        $fbUrl = $siteSettings?->facebook_url ?? 'https://www.facebook.com/bpvppangkep';
        $ytUrl = $siteSettings?->youtube_url ?? 'https://www.youtube.com/@bpvppangkep';
        $tiktokUrl = $siteSettings?->tiktok_url ?? 'https://www.tiktok.com/@bpvp_pangkep';

        // Custom message if passed from exception
        $customMessage = isset($exception) && $exception->getMessage() ? $exception->getMessage() : null;
    @endphp

    <title>Mode Pemeliharaan Sistem | {{ $siteName }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lexend:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind Play CDN for immediate responsive styling on any domain/port -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        display: ['"Lexend"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Complete Standalone Pure CSS Fallback & Animations -->
    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-display: 'Lexend', var(--font-sans);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: #030712;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
            background:
                radial-gradient(ellipse 90% 60% at 50% 10%, rgba(10, 46, 80, 0.7) 0%, rgba(3, 7, 18, 0.98) 70%, #030712 100%),
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 48px 48px, 48px 48px;
        }

        /* Glassmorphism Master Panel */
        .glass-maintenance-card {
            background: rgba(11, 20, 36, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(56, 189, 248, 0.22);
            box-shadow:
                0 30px 60px -12px rgba(0, 0, 0, 0.8),
                0 0 50px -10px rgba(14, 165, 233, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .glass-subcard {
            background: rgba(15, 28, 48, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-subcard:hover {
            border-color: rgba(56, 189, 248, 0.4);
            background: rgba(20, 37, 65, 0.85);
            transform: translateY(-2px);
        }

        /* Orbital Animations */
        @keyframes orbit-cw {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes orbit-ccw {
            from {
                transform: rotate(360deg);
            }

            to {
                transform: rotate(0deg);
            }
        }

        @keyframes pulse-radar {
            0% {
                transform: scale(0.9);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.15);
                opacity: 0.3;
            }

            100% {
                transform: scale(0.9);
                opacity: 0.8;
            }
        }

        @keyframes shimmer-sweep {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        @keyframes float-hero {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .animate-orbit-slow {
            animation: orbit-cw 24s linear infinite;
        }

        .animate-orbit-reverse {
            animation: orbit-ccw 18s linear infinite;
        }

        .animate-pulse-radar {
            animation: pulse-radar 4s ease-in-out infinite;
        }

        .animate-float-hero {
            animation: float-hero 5s ease-in-out infinite;
        }

        .shimmer-progress {
            background: linear-gradient(90deg,
                    rgba(14, 165, 233, 1) 0%,
                    rgba(56, 189, 248, 1) 25%,
                    rgba(168, 85, 247, 1) 50%,
                    rgba(56, 189, 248, 1) 75%,
                    rgba(14, 165, 233, 1) 100%);
            background-size: 200% 100%;
            animation: shimmer-sweep 3s linear infinite;
        }

        .text-gradient-cyan {
            background: linear-gradient(135deg, #ffffff 10%, #7dd3fc 50%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-check-status {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: 1px solid rgba(56, 189, 248, 0.4);
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.4);
            color: #ffffff !important;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-check-status:hover {
            background: linear-gradient(135deg, #0369a1 0%, #0c4a6e 100%);
            box-shadow: 0 8px 30px rgba(2, 132, 199, 0.6);
            transform: translateY(-2px);
        }

        .ambient-glow-top {
            position: absolute;
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 450px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, rgba(99, 102, 241, 0.15) 40%, transparent 75%);
            filter: blur(80px);
            pointer-events: none;
        }

        /* Layout Structure */
        header {
            width: 100%;
            border-bottom: 1px solid rgba(56, 189, 248, 0.15);
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(16px);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .header-inner {
            max-width: 80rem;
            margin: 0 auto;
            padding: 0 1rem;
            height: 5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            position: relative;
            z-index: 10;
        }

        .maintenance-card-wrapper {
            max-width: 56rem;
            width: 100%;
            margin: 0 auto;
        }

        footer {
            width: 100%;
            border-top: 1px solid rgba(56, 189, 248, 0.15);
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(16px);
            padding: 1.5rem 1rem;
            position: relative;
            z-index: 20;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col justify-between selection:bg-cyan-500/30 selection:text-cyan-200 relative">

    <div class="ambient-glow-top"></div>

    <!-- Header Navigation -->
    <header>
        <div class="header-inner">
            <!-- Brand Emblem -->
            <div class="flex items-center gap-3.5">
                <div
                    class="relative w-11 h-11 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border border-cyan-500/30 p-2 flex items-center justify-center shadow-lg shadow-cyan-500/10">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="w-full h-full object-contain"
                        onerror="this.src='/icons/icon-192x192.png'; this.onerror=null;">
                    <span
                        class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-400 border-2 border-slate-900 flex items-center justify-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-950 animate-ping"></span>
                    </span>
                </div>
                <div>
                    <div
                        class="font-extrabold text-white text-base sm:text-lg tracking-tight leading-tight flex items-center gap-2">
                        <span>{{ $siteName }}</span>
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30">Maintenance</span>
                    </div>
                    <div class="text-xs text-slate-400 tracking-wide font-normal">Balai Pelatihan Vokasi & Produktivitas
                        Pangkajene & Kepulauan</div>
                </div>
            </div>

            <!-- Real-time WITA Clock Widget -->
            <div
                class="hidden sm:flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900/80 border border-cyan-500/20 shadow-inner">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                <div class="text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Waktu WITA (Makassar)
                    </div>
                    <div id="wita-clock" class="text-xs font-mono font-bold text-cyan-300">--:--:-- WITA</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Hero Stage -->
    <main>
        <div class="maintenance-card-wrapper">

            <!-- Card Master Container -->
            <div class="glass-maintenance-card rounded-3xl p-6 sm:p-10 md:p-12 relative overflow-hidden">

                <!-- Background Geometric Grid Rings -->
                <div
                    class="absolute -top-32 -left-32 w-80 h-80 rounded-full border border-cyan-500/10 pointer-events-none animate-orbit-slow">
                </div>
                <div
                    class="absolute -top-32 -left-32 w-80 h-80 rounded-full border border-dashed border-sky-500/10 pointer-events-none animate-orbit-reverse">
                </div>

                <!-- Central Visual Animation Core -->
                <div class="flex flex-col items-center text-center mb-8 relative">

                    <!-- Futuristic Orbital Tech Hub -->
                    <div
                        class="relative w-36 h-36 sm:w-44 sm:h-44 flex items-center justify-center mb-6 animate-float-hero">

                        <!-- Outer rotating dashed gear ring -->
                        <div
                            class="absolute inset-0 rounded-full border-2 border-dashed border-cyan-500/30 animate-orbit-slow">
                        </div>

                        <!-- Middle counter-rotating ring with tracking nodes -->
                        <div
                            class="absolute inset-3 rounded-full border border-sky-400/20 animate-orbit-reverse flex items-start justify-center">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 shadow-lg shadow-cyan-400"></span>
                        </div>
                        <div
                            class="absolute inset-3 rounded-full border border-sky-400/20 animate-orbit-reverse flex items-end justify-center">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shadow-lg shadow-amber-400"></span>
                        </div>

                        <!-- Inner pulsing radar glow -->
                        <div
                            class="absolute inset-7 rounded-full bg-gradient-to-tr from-cyan-500/20 via-sky-600/30 to-amber-500/20 blur-sm animate-pulse-radar">
                        </div>

                        <!-- Center Tech Core Badge -->
                        <div
                            class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-slate-900 border-2 border-cyan-400/40 shadow-2xl shadow-cyan-500/30 flex items-center justify-center text-cyan-400">
                            <i class="fa-solid fa-screwdriver-wrench text-3xl sm:text-4xl text-cyan-300"></i>

                            <!-- Floating mini-badge -->
                            <div
                                class="absolute -bottom-2 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-950 shadow-md">
                                503 UPGRADE
                            </div>
                        </div>
                    </div>

                    <!-- Main Status Heading -->
                    <div
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold tracking-wide uppercase bg-cyan-500/10 text-cyan-300 border border-cyan-500/30 mb-3 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                        <span>Pemeliharaan Terjadwal & Peningkatan Sistem</span>
                    </div>

                    <h1
                        class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4 font-display">
                        Kami Sedang Mempersiapkan <br class="hidden sm:inline">
                        <span class="text-gradient-cyan">Pengalaman Yang Lebih Baik</span>
                    </h1>

                    <!-- Description or Custom Message from `php artisan down` -->
                    <div class="max-w-2xl mx-auto">
                        @if ($customMessage)
                            <div
                                class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-sm leading-relaxed mb-4">
                                <div class="font-bold flex items-center justify-center gap-2 mb-1 text-amber-300">
                                    <i class="fa-solid fa-bullhorn"></i>
                                    <span>Pemberitahuan Administrator:</span>
                                </div>
                                <span>{{ $customMessage }}</span>
                            </div>
                        @else
                            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                                Portal resmi <strong class="text-white">{{ $siteName }}</strong> sedang menjalani
                                pembaruan rutin, optimalisasi basis data, dan peningkatan performa infrastruktur server
                                untuk memberikan layanan prima bagi masyarakat.
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Live Progress Simulation & Timeline -->
                <div class="glass-subcard rounded-2xl p-5 sm:p-6 mb-8 border border-white/10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-300">
                            <i class="fa-solid fa-bars-progress text-cyan-400"></i>
                            <span>Tahapan Pemeliharaan Sistem</span>
                        </div>
                        <div class="text-xs font-mono font-semibold text-cyan-400 flex items-center gap-1.5">
                            <span>Estimasi Progres:</span>
                            <span
                                class="px-2 py-0.5 rounded bg-cyan-950/80 border border-cyan-500/30 text-cyan-300">~85%
                                Selesai</span>
                        </div>
                    </div>

                    <!-- Visual Glowing Progress Bar -->
                    <div class="w-full h-3 rounded-full bg-slate-900 border border-white/10 p-0.5 overflow-hidden mb-6">
                        <div class="h-full rounded-full shimmer-progress" style="width: 85%;"></div>
                    </div>

                    <!-- Three Step Milestones -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <!-- Step 1: Backup -->
                        <div
                            class="p-3.5 rounded-xl bg-slate-900/60 border border-emerald-500/30 flex items-start gap-3">
                            <div
                                class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-xs"></i>
                            </div>
                            <div>
                                <div class="font-bold text-white mb-0.5">Pencadangan Data</div>
                                <div class="text-slate-400 text-[11px]">Integritas data & berkas pendaftaran aman 100%.
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Optimasi -->
                        <div
                            class="p-3.5 rounded-xl bg-cyan-950/40 border border-cyan-500/40 flex items-start gap-3 relative overflow-hidden">
                            <div
                                class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-300 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-bolt text-xs animate-bounce"></i>
                            </div>
                            <div>
                                <div class="font-bold text-cyan-200 mb-0.5 flex items-center gap-1.5">
                                    <span>Penyetelan Sistem</span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                                </div>
                                <div class="text-slate-300 text-[11px]">Pembaruan mesin modul & optimasi database.</div>
                            </div>
                        </div>

                        <!-- Step 3: Pengujian -->
                        <div
                            class="p-3.5 rounded-xl bg-slate-900/40 border border-white/5 flex items-start gap-3 opacity-80">
                            <div
                                class="w-6 h-6 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-hourglass-half text-xs"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-300 mb-0.5">Pengujian Terakhir</div>
                                <div class="text-slate-400 text-[11px]">Audit keamanan dan persiapan peluncuran.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Auto-Check Recovery Control Center -->
                <div
                    class="glass-subcard rounded-2xl p-5 sm:p-6 mb-8 border border-cyan-500/20 bg-gradient-to-r from-slate-900/80 via-slate-900/50 to-slate-900/80">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-5">

                        <!-- Left Status & Countdown -->
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                                <svg class="w-12 h-12 -rotate-90">
                                    <circle cx="24" cy="24" r="20" stroke="currentColor"
                                        stroke-width="3" class="text-slate-800" fill="transparent" />
                                    <circle id="countdown-circle" cx="24" cy="24" r="20"
                                        stroke="currentColor" stroke-width="3"
                                        class="text-cyan-400 transition-all duration-1000" fill="transparent"
                                        stroke-dasharray="125.6" stroke-dashoffset="0" />
                                </svg>
                                <span id="countdown-number"
                                    class="absolute text-xs font-mono font-bold text-white">30</span>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>Pemeriksaan Status Otomatis</span>
                                    <span id="auto-check-badge"
                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Aktif</span>
                                </div>
                                <div id="auto-check-caption" class="text-xs text-slate-400">
                                    Sistem akan mengecek kembali ketersediaan website dalam 30 detik.
                                </div>
                            </div>
                        </div>

                        <!-- Right Check Action Button -->
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                            <button id="btn-check-now" type="button" onclick="checkServerStatus(true)"
                                class="btn-check-status w-full sm:w-auto px-5 py-3 rounded-xl font-bold text-white text-xs sm:text-sm flex items-center justify-center gap-2 cursor-pointer">
                                <i id="icon-check" class="fa-solid fa-arrows-rotate text-cyan-200"></i>
                                <span id="text-check">Periksa Sekarang</span>
                            </button>

                            <button type="button" id="btn-toggle-auto" onclick="toggleAutoCheck()"
                                title="Nyalakan/Matikan Pemeriksaan Otomatis"
                                class="px-3.5 py-3 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-white/10 text-xs font-medium transition-colors cursor-pointer">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Toast Notification for Server Status -->
                    <div id="status-toast"
                        class="hidden mt-4 p-3 rounded-xl text-xs font-medium flex items-center gap-2.5 transition-all">
                        <i id="toast-icon" class="fa-solid fa-circle-info"></i>
                        <span id="toast-message">Memeriksa ketersediaan portal...</span>
                    </div>
                </div>

                <!-- Emergency Contact & Public Assistance Hub -->
                <div>
                    <div class="text-center mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Butuh Informasi Mendesak atau Layanan Publik?
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

                        <!-- WhatsApp Layanan -->
                        @if (!empty($waClean))
                            <a href="https://wa.me/{{ $waClean }}?text=Halo%20Admin%20BPVP%20Pangkep,%20saya%20ingin%20menanyakan%20informasi%20layanan%20selama%20website%20dalam%20pemeliharaan."
                                target="_blank" rel="noopener noreferrer"
                                class="glass-subcard p-4 rounded-xl flex items-center gap-3.5 group no-underline">
                                <div
                                    class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-whatsapp text-lg"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div
                                        class="text-xs font-bold text-white group-hover:text-emerald-300 transition-colors">
                                        WhatsApp Pelayanan</div>
                                    <div class="text-[11px] text-slate-400 truncate">+{{ $waClean }}</div>
                                </div>
                            </a>
                        @endif

                        <!-- Email Resmi -->
                        <a href="mailto:{{ $emailContact }}"
                            class="glass-subcard p-4 rounded-xl flex items-center gap-3.5 group no-underline">
                            <div
                                class="w-10 h-10 rounded-xl bg-cyan-500/15 text-cyan-400 border border-cyan-500/30 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-envelope text-base"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-xs font-bold text-white group-hover:text-cyan-300 transition-colors">
                                    Email Pelayanan</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ $emailContact }}</div>
                            </div>
                        </a>

                        <!-- Telepon Kantor -->
                        <div class="glass-subcard p-4 rounded-xl flex items-center gap-3.5 group">
                            <div
                                class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-400 border border-sky-500/30 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-phone text-base"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-xs font-bold text-white">Telepon / Fax</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ $phoneContact }}</div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Footer Bar -->
    <footer>
        <div
            class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="text-xs text-slate-400">
                &copy; {{ date('Y') }} <span class="font-bold text-slate-200">{{ $siteName }}</span>. Balai
                Pelatihan Vokasi dan Produktivitas Pangkajene dan Kepulauan &bull; Kemnaker RI.
            </div>

            <!-- Social Links with glowing hover -->
            <div class="flex items-center gap-4 text-sm">
                @if ($igUrl)
                    <a href="{{ $igUrl }}" target="_blank" rel="noopener noreferrer"
                        class="text-slate-400 hover:text-pink-400 transition-colors no-underline" title="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                @endif
                @if ($ytUrl)
                    <a href="{{ $ytUrl }}" target="_blank" rel="noopener noreferrer"
                        class="text-slate-400 hover:text-red-500 transition-colors no-underline" title="YouTube">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                @endif
                @if ($fbUrl)
                    <a href="{{ $fbUrl }}" target="_blank" rel="noopener noreferrer"
                        class="text-slate-400 hover:text-blue-500 transition-colors no-underline" title="Facebook">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                @endif
                @if ($tiktokUrl)
                    <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer"
                        class="text-slate-400 hover:text-cyan-400 transition-colors no-underline" title="TikTok">
                        <i class="fa-brands fa-tiktok"></i>
                    </a>
                @endif
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts for Realtime WITA Clock and Auto-Check Recovery -->
    <script>
        // 1. Real-time WITA Clock (UTC+8)
        function updateWitaClock() {
            const now = new Date();
            const utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
            const witaTime = new Date(utcTime + (8 * 3600000));

            const hours = String(witaTime.getHours()).padStart(2, '0');
            const minutes = String(witaTime.getMinutes()).padStart(2, '0');
            const seconds = String(witaTime.getSeconds()).padStart(2, '0');

            const el = document.getElementById('wita-clock');
            if (el) {
                el.textContent = `${hours}:${minutes}:${seconds} WITA`;
            }
        }
        setInterval(updateWitaClock, 1000);
        updateWitaClock();

        // 2. Auto-Check & Countdown Mechanism
        const TOTAL_COUNTDOWN = 30;
        let countdown = TOTAL_COUNTDOWN;
        let isAutoCheckEnabled = true;
        const CIRCUMFERENCE = 125.6; // 2 * PI * 20

        const countdownNumber = document.getElementById('countdown-number');
        const countdownCircle = document.getElementById('countdown-circle');
        const autoCheckBadge = document.getElementById('auto-check-badge');
        const autoCheckCaption = document.getElementById('auto-check-caption');
        const btnCheck = document.getElementById('btn-check-now');
        const iconCheck = document.getElementById('icon-check');
        const textCheck = document.getElementById('text-check');
        const statusToast = document.getElementById('status-toast');
        const toastIcon = document.getElementById('toast-icon');
        const toastMessage = document.getElementById('toast-message');

        function updateCountdownUI() {
            if (countdownNumber) countdownNumber.textContent = countdown;
            if (countdownCircle) {
                const offset = CIRCUMFERENCE - (countdown / TOTAL_COUNTDOWN) * CIRCUMFERENCE;
                countdownCircle.style.strokeDashoffset = offset;
            }
        }

        setInterval(() => {
            if (!isAutoCheckEnabled) return;
            countdown--;
            if (countdown <= 0) {
                countdown = TOTAL_COUNTDOWN;
                updateCountdownUI();
                checkServerStatus(false);
            } else {
                updateCountdownUI();
            }
        }, 1000);

        function toggleAutoCheck() {
            isAutoCheckEnabled = !isAutoCheckEnabled;
            if (autoCheckBadge) {
                if (isAutoCheckEnabled) {
                    autoCheckBadge.className =
                        'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30';
                    autoCheckBadge.textContent = 'Aktif';
                    if (autoCheckCaption) autoCheckCaption.textContent =
                        'Sistem akan mengecek kembali ketersediaan website dalam 30 detik.';
                } else {
                    autoCheckBadge.className =
                        'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-white/10';
                    autoCheckBadge.textContent = 'Nonaktif';
                    if (autoCheckCaption) autoCheckCaption.textContent =
                        'Pemeriksaan otomatis dihentikan. Anda dapat mengecek secara manual.';
                }
            }
        }

        function showToast(message, type = 'info') {
            if (!statusToast) return;
            statusToast.classList.remove('hidden', 'bg-cyan-500/10', 'text-cyan-300', 'border-cyan-500/30',
                'bg-emerald-500/10', 'text-emerald-300', 'border-emerald-500/30', 'bg-amber-500/10', 'text-amber-300',
                'border-amber-500/30');

            if (type === 'success') {
                statusToast.classList.add('bg-emerald-500/10', 'text-emerald-300', 'border', 'border-emerald-500/30');
                if (toastIcon) toastIcon.className = 'fa-solid fa-circle-check text-emerald-400';
            } else if (type === 'warning') {
                statusToast.classList.add('bg-amber-500/10', 'text-amber-300', 'border', 'border-amber-500/30');
                if (toastIcon) toastIcon.className = 'fa-solid fa-triangle-exclamation text-amber-400';
            } else {
                statusToast.classList.add('bg-cyan-500/10', 'text-cyan-300', 'border', 'border-cyan-500/30');
                if (toastIcon) toastIcon.className = 'fa-solid fa-circle-notch fa-spin text-cyan-400';
            }

            if (toastMessage) toastMessage.textContent = message;
        }

        async function checkServerStatus(isManual = true) {
            if (iconCheck) iconCheck.classList.add('fa-spin');
            if (textCheck) textCheck.textContent = 'Mengecek...';
            showToast('Menghubungi server untuk memverifikasi ketersediaan portal...', 'info');

            try {
                const response = await fetch('/up', {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });

                if (response.ok && response.status === 200) {
                    showToast('Sistem telah kembali ONLINE! Mengalihkan ke halaman utama...', 'success');
                    if (textCheck) textCheck.textContent = 'Sistem Pulih!';
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1500);
                } else {
                    showToast('Pemeliharaan masih berlangsung. Tim sedang menyelesaikan tahapan akhir.', 'warning');
                    if (iconCheck) iconCheck.classList.remove('fa-spin');
                    if (textCheck) textCheck.textContent = 'Periksa Sekarang';
                }
            } catch (err) {
                showToast('Server masih dalam proses sinkronisasi pemeliharaan. Terima kasih atas kesabaran Anda.',
                    'warning');
                if (iconCheck) iconCheck.classList.remove('fa-spin');
                if (textCheck) textCheck.textContent = 'Periksa Sekarang';
            }
        }
    </script>
</body>

</html>
