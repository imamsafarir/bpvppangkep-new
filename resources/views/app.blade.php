<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteSettings = \Illuminate\Support\Facades\Cache::remember('shared_website_settings', 3600, fn() => \Modules\Website\Models\WebsiteSetting::first());
        $seoService = app(\Modules\Website\Services\SeoService::class);
        $seo = $seoService->resolveMetadata($page ?? []);

        $siteName = $seo['site_name'];
        $faviconPath = $siteSettings?->favicon_path;
        $faviconUrl = $faviconPath ? asset('storage/' . $faviconPath) : asset('favicon.ico');
        $faviconVersion = $siteSettings?->updated_at?->timestamp ?? time();
    @endphp

    <!-- 1. Primary Page & Search Engine Meta Tags -->
    <title inertia>{{ $seo['title'] }}</title>
    <meta name="title" content="{{ $seo['title'] }}" head-key="meta-title">
    <meta name="description" content="{{ $seo['description'] }}" head-key="description">
    <meta name="keywords" content="{{ $seo['keywords'] }}" head-key="keywords">
    <meta name="author" content="BPVP Pangkep - Kementerian Ketenagakerjaan RI">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="{{ $seo['canonical'] }}" head-key="canonical">

    <!-- 2. Geo & Regional Localization (Targeting Sulawesi & Nasional) -->
    <meta name="geo.region" content="ID-SN">
    <meta name="geo.placename" content="Pangkajene dan Kepulauan">
    <meta name="geo.position" content="-4.558029;119.598334">
    <meta name="ICBM" content="-4.558029, 119.598334">
    <meta name="language" content="Indonesian">

    <!-- 3. Open Graph / Facebook / WhatsApp / Telegram Preview -->
    <meta property="og:type" content="{{ $seo['og_type'] }}" head-key="og:type">
    <meta property="og:site_name" content="{{ $siteName }} - Kemnaker RI">
    <meta property="og:url" content="{{ $seo['canonical'] }}" head-key="og:url">
    <meta property="og:title" content="{{ $seo['title'] }}" head-key="og:title">
    <meta property="og:description" content="{{ $seo['description'] }}" head-key="og:description">
    <meta property="og:image" content="{{ $seo['og_image'] }}" head-key="og:image">
    <meta property="og:image:secure_url" content="{{ $seo['og_image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $seo['title'] }}">
    <meta property="og:locale" content="id_ID">
    @if (!empty($seo['article_data']))
        <meta property="article:published_time" content="{{ $seo['article_data']['datePublished'] }}">
        <meta property="article:modified_time" content="{{ $seo['article_data']['dateModified'] }}">
        <meta property="article:author" content="{{ $seo['article_data']['author'] }}">
        <meta property="article:section" content="Pelatihan Vokasi & Ketenagakerjaan">
    @endif

    <!-- 4. Twitter Card (Large Summary for X / Twitter) -->
    <meta name="twitter:card" content="summary_large_image" head-key="twitter:card">
    <meta name="twitter:url" content="{{ $seo['canonical'] }}" head-key="twitter:url">
    <meta name="twitter:title" content="{{ $seo['title'] }}" head-key="twitter:title">
    <meta name="twitter:description" content="{{ $seo['description'] }}" head-key="twitter:description">
    <meta name="twitter:image" content="{{ $seo['og_image'] }}" head-key="twitter:image">
    <meta name="twitter:site" content="@bpvppangkep">
    <meta name="twitter:creator" content="@bpvppangkep">

    <!-- 5. Structured Data (JSON-LD / Schema.org for Google Search Rich Results) -->
    @foreach ($seo['schemas'] as $schema)
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endforeach

    <!-- 6. Favicon & App Icons -->
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">

    <!-- 7. PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0A2E50">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BPVP Pangkep">
    <meta name="application-name" content="BPVP Pangkep - Super APP">
    <meta name="msapplication-TileColor" content="#0A2E50">
    <meta name="msapplication-TileImage" content="/icons/icon-144x144.png">

    <!-- 8. Fonts & External Resources (Preconnect & Non-blocking) -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lexend:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,400&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lexend:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,400&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    </noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </noscript>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="font-sans antialiased text-slate-800 bg-slate-50 selection:bg-blue-600 selection:text-white">
    @inertia
</body>

</html>
