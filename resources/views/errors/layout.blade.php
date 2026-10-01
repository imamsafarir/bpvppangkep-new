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

        $errorCode = trim($__env->yieldContent('code', 'Error'));
        $errorTitle = trim($__env->yieldContent('title', 'Terjadi Kendala'));
        $themeColor = trim($__env->yieldContent('theme_color', 'blue')); // blue, amber, red, purple, emerald, cyan
    @endphp

    <title>{{ $errorCode }} - {{ $errorTitle }} | {{ $siteName }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind Play CDN for guaranteed responsive utility support -->
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
            background-color: #060b13;
            background-image:
                radial-gradient(circle at 50% 20%, rgba(10, 46, 80, 0.55) 0%, rgba(6, 11, 19, 0.98) 70%, #060b13 100%),
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 40px 40px, 40px 40px;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Glassmorphism containers */
        .glass-panel {
            background: rgba(13, 23, 39, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .glass-panel-subtle {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Keyframes */
        @keyframes float-gentle {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes pulse-slow {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.35;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.6;
            }
        }

        .animate-float {
            animation: float-gentle 4s ease-in-out infinite;
        }

        .animate-pulse-slow {
            animation: pulse-slow 8s ease-in-out infinite;
        }

        /* Ambient Glow Themes */
        .glow-indigo {
            background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
        }

        .glow-amber {
            background: radial-gradient(circle, rgba(245, 158, 11, 0.35) 0%, transparent 70%);
        }

        .glow-red {
            background: radial-gradient(circle, rgba(239, 68, 68, 0.35) 0%, transparent 70%);
        }

        .glow-purple {
            background: radial-gradient(circle, rgba(168, 85, 247, 0.35) 0%, transparent 70%);
        }

        .glow-emerald {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.35) 0%, transparent 70%);
        }

        .glow-cyan {
            background: radial-gradient(circle, rgba(6, 182, 212, 0.35) 0%, transparent 70%);
        }

        /* Typography */
        .text-gradient-code {
            background: linear-gradient(135deg, #ffffff 15%, #94a3b8 70%, #64748b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Buttons */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.5);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0 !important;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-secondary:hover {
            background: rgba(51, 65, 85, 0.9);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff !important;
            transform: translateY(-2px);
        }

        /* Essential Standalone Layout Fallbacks */
        header {
            width: 100%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
            height: 4.5rem;
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

        .error-card-wrapper {
            max-width: 50rem;
            width: 100%;
            margin: 0 auto;
        }

        footer {
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(16px);
            padding: 1.5rem 1rem;
            position: relative;
            z-index: 20;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col justify-between selection:bg-cyan-500/30 selection:text-cyan-200">

    <!-- Top Glow Orb matching theme -->
    <div
        class="fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] pointer-events-none rounded-full blur-[100px] opacity-60 animate-pulse-slow
        @if ($themeColor === 'amber') glow-amber
        @elseif($themeColor === 'red') glow-red
        @elseif($themeColor === 'purple') glow-purple
        @elseif($themeColor === 'emerald') glow-emerald
        @elseif($themeColor === 'cyan') glow-cyan
        @else glow-indigo @endif">
    </div>

    <!-- Header Navigation Bar -->
    <header>
        <div class="header-inner">
            <!-- Brand Info -->
            <a href="/"
                class="flex items-center gap-3.5 group no-underline text-white transition-transform duration-200 hover:scale-[1.01]">
                <div
                    class="relative w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 border border-white/10 p-1.5 flex items-center justify-center shadow-lg shadow-black/40 group-hover:border-cyan-500/40 transition-colors">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="w-full h-full object-contain"
                        onerror="this.src='/icons/icon-192x192.png'; this.onerror=null;">
                </div>
                <div>
                    <div
                        class="font-bold text-white text-base sm:text-lg tracking-tight leading-tight flex items-center gap-2">
                        <span>{{ $siteName }}</span>
                        <span
                            class="hidden sm:inline-block text-[10px] font-semibold tracking-wider uppercase px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">Portal
                            Resmi</span>
                    </div>
                    <div class="text-xs text-slate-400 tracking-wide font-normal">Kementerian Ketenagakerjaan RI</div>
                </div>
            </a>

            <!-- Status Pill & Quick Nav -->
            <div class="flex items-center gap-2.5 sm:gap-4">
                <div
                    class="hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium border
                    @if ($themeColor === 'amber') bg-amber-500/10 text-amber-300 border-amber-500/30
                    @elseif($themeColor === 'red') bg-rose-500/10 text-rose-300 border-rose-500/30
                    @elseif($themeColor === 'purple') bg-purple-500/10 text-purple-300 border-purple-500/30
                    @elseif($themeColor === 'emerald') bg-emerald-500/10 text-emerald-300 border-emerald-500/30
                    @elseif($themeColor === 'cyan') bg-cyan-500/10 text-cyan-300 border-cyan-500/30
                    @else bg-sky-500/10 text-sky-300 border-sky-500/30 @endif">
                    <span
                        class="w-2 h-2 rounded-full animate-ping inline-block
                        @if ($themeColor === 'amber') bg-amber-400
                        @elseif($themeColor === 'red') bg-rose-400
                        @elseif($themeColor === 'purple') bg-purple-400
                        @elseif($themeColor === 'emerald') bg-emerald-400
                        @elseif($themeColor === 'cyan') bg-cyan-400
                        @else bg-sky-400 @endif"></span>
                    <span>HTTP {{ $errorCode }} - @yield('badge_text', 'Status Alert')</span>
                </div>

                <a href="/"
                    class="btn-secondary px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-medium rounded-xl gap-2">
                    <i class="fa-solid fa-house text-slate-400"></i>
                    <span class="hidden xs:inline">Beranda</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main>
        <div class="error-card-wrapper">

            <!-- Error Card Master Container -->
            <div class="glass-panel rounded-3xl p-6 sm:p-10 md:p-12 relative overflow-hidden">

                <!-- Ambient corner sheen -->
                <div
                    class="absolute -top-24 -right-24 w-52 h-52 rounded-full opacity-20 pointer-events-none blur-3xl
                    @if ($themeColor === 'amber') bg-amber-500
                    @elseif($themeColor === 'red') bg-rose-500
                    @elseif($themeColor === 'purple') bg-purple-500
                    @elseif($themeColor === 'emerald') bg-emerald-500
                    @elseif($themeColor === 'cyan') bg-cyan-500
                    @else bg-sky-500 @endif">
                </div>

                <!-- Top Center Illustration & Error Code -->
                <div class="text-center mb-8">
                    <!-- Icon Avatar Badge -->
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 rounded-3xl mb-6 relative animate-float shadow-xl
                        @if ($themeColor === 'amber') bg-gradient-to-tr from-amber-500/20 to-orange-500/10 border border-amber-500/30 text-amber-400 shadow-amber-500/10
                        @elseif($themeColor === 'red') bg-gradient-to-tr from-rose-500/20 to-red-500/10 border border-rose-500/30 text-rose-400 shadow-rose-500/10
                        @elseif($themeColor === 'purple') bg-gradient-to-tr from-purple-500/20 to-fuchsia-500/10 border border-purple-500/30 text-purple-400 shadow-purple-500/10
                        @elseif($themeColor === 'emerald') bg-gradient-to-tr from-emerald-500/20 to-teal-500/10 border border-emerald-500/30 text-emerald-400 shadow-emerald-500/10
                        @elseif($themeColor === 'cyan') bg-gradient-to-tr from-cyan-500/20 to-sky-500/10 border border-cyan-500/30 text-cyan-400 shadow-cyan-500/10
                        @else bg-gradient-to-tr from-sky-500/20 to-indigo-500/10 border border-sky-500/30 text-sky-400 shadow-sky-500/10 @endif">
                        <i class="@yield('icon', 'fa-solid fa-circle-exclamation') text-3xl sm:text-4xl"></i>
                        <span
                            class="absolute -bottom-2 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-900 border border-white/10 text-slate-300">
                            {{ $errorCode }}
                        </span>
                    </div>

                    <!-- Large Numerical Code -->
                    <h1
                        class="text-6xl sm:text-7xl md:text-8xl font-black tracking-tight text-gradient-code leading-none mb-3 font-display">
                        {{ $errorCode }}
                    </h1>

                    <!-- Main Indonesian Title -->
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-3">
                        @yield('title', 'Terjadi Kendala Teknis')
                    </h2>

                    <!-- Descriptive Message -->
                    <p class="text-slate-300 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
                        @yield('message', 'Sistem tidak dapat menyelesaikan permintaan yang Anda kirimkan. Silakan periksa kembali informasi Anda.')
                    </p>
                </div>

                <!-- Explanation & Suggestions Box -->
                <div class="glass-panel-subtle rounded-2xl p-5 sm:p-6 mb-8 border border-white/5">
                    <div
                        class="flex items-center gap-2.5 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3.5">
                        <i class="fa-solid fa-lightbulb text-amber-400"></i>
                        <span>Apa Yang Dapat Anda Lakukan?</span>
                    </div>

                    <div class="space-y-2.5 text-xs sm:text-sm text-slate-300">
                        @yield('suggestions')
                    </div>
                </div>

                <!-- Action Buttons Group -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 mb-6">
                    @yield('actions')
                </div>

                <!-- Collapsible Diagnostic Reference -->
                <div class="mt-8 pt-6 border-t border-white/5">
                    <button type="button"
                        onclick="const d = document.getElementById('diagnostic-info'); d.classList.toggle('hidden');"
                        class="mx-auto flex items-center justify-center gap-2 text-xs font-medium text-slate-400 hover:text-slate-200 transition-colors py-1 px-3 rounded-lg hover:bg-slate-800/40 cursor-pointer">
                        <i class="fa-solid fa-terminal text-[10px]"></i>
                        <span>Informasi Diagnostik Sistem</span>
                        <i class="fa-solid fa-chevron-down text-[10px] opacity-70"></i>
                    </button>

                    <div id="diagnostic-info"
                        class="hidden mt-4 p-4 rounded-xl bg-slate-950/80 border border-white/5 font-mono text-[11px] text-slate-400 space-y-1.5 overflow-x-auto">
                        <div class="flex justify-between border-b border-white/5 pb-1">
                            <span class="text-slate-400">Waktu Insiden:</span>
                            <span
                                class="text-slate-200">{{ now()->setTimezone('Asia/Makassar')->translatedFormat('d F Y, H:i:s') }}
                                WITA</span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-1">
                            <span class="text-slate-400">Jalur Akses (Path):</span>
                            <span
                                class="text-slate-200 truncate max-w-[280px] sm:max-w-md">{{ request()->path() ?: '/' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-1">
                            <span class="text-slate-400">Metode Request:</span>
                            <span class="text-slate-200 font-semibold">{{ request()->method() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">ID Referensi:</span>
                            <span
                                class="text-cyan-400 font-bold">ERR-{{ $errorCode }}-{{ substr(md5(request()->fullUrl() . now()->timestamp), 0, 8) }}</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div
            class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="text-xs text-slate-400">
                &copy; {{ date('Y') }} <span class="font-semibold text-slate-200">{{ $siteName }}</span>.
                Balai Pelatihan Vokasi dan Produktivitas Pangkajene dan Kepulauan.
            </div>

            <!-- Quick Contacts -->
            <div class="flex items-center gap-4 text-xs">
                @if (!empty($waClean))
                    <a href="https://wa.me/{{ $waClean }}?text=Halo%20Admin%20BPVP%20Pangkep,%20saya%20mengalami%20kendala%20pada%20halaman%20website%20(Error%20{{ $errorCode }})."
                        target="_blank" rel="noopener noreferrer"
                        class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1.5 no-underline">
                        <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                        <span>WhatsApp Layanan</span>
                    </a>
                @endif
                <span class="text-slate-700">&bull;</span>
                <a href="mailto:{{ $emailContact }}"
                    class="text-slate-400 hover:text-cyan-400 transition-colors flex items-center gap-1.5 no-underline">
                    <i class="fa-solid fa-envelope text-cyan-400"></i>
                    <span>Email Resmi</span>
                </a>
            </div>
        </div>
    </footer>

</body>

</html>
