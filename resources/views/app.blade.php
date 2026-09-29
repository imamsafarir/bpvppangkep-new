<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    @php
        $siteSettings = \Modules\Website\Models\WebsiteSetting::first();
        $faviconPath = $siteSettings?->favicon_path;
        $faviconUrl = $faviconPath ? asset('storage/' . $faviconPath) : asset('favicon.ico');
        $faviconVersion = $siteSettings?->updated_at?->timestamp ?? time();
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0A2E50">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BPVP Pangkep">
    <meta name="application-name" content="BPVP Pangkep - Super APP">
    <meta name="msapplication-TileColor" content="#0A2E50">
    <meta name="msapplication-TileImage" content="/icons/icon-144x144.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
