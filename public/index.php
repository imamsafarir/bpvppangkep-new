<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Deteksi otomatis lokasi folder project Laravel
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Jalur untuk LOKAL (atau jika file berada di bpvppangkep-new/public)
    $projectDir = __DIR__ . '/..';
} else {
    // Jalur untuk CPANEL (saat file disalin ke public_html)
    $projectDir = __DIR__ . '/../bpvppangkep-new';
}

// 1. Cek Maintenance Mode
if (file_exists($maintenance = $projectDir . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// 2. Autoload Composer
require $projectDir . '/vendor/autoload.php';

// 3. Bootstrap Laravel
/** @var Application $app */
$app = require_once $projectDir . '/bootstrap/app.php';

// 4. Jalankan Aplikasi
$app->handleRequest(Request::capture());
