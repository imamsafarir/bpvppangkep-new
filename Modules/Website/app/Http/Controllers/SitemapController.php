<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Website\Models\BeritaDanGaleri;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for search engines.
     */
    public function index(): Response
    {
        $baseUrl = url('/');
        $urls = [];

        // 1. Static Public Pages
        $staticPages = [
            // Home
            ['url' => "{$baseUrl}/", 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => now()->toIso8601String()],

            // Berita & Informasi
            ['url' => "{$baseUrl}/berita-informasi/daftar-berita", 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/berita-informasi/galeri-kegiatan", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],

            // Profil
            ['url' => "{$baseUrl}/profil/sambutan-kepala", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/tentang-kami", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/ppid-pelayanan", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/visi-misi", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/tugas-fungsi", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/struktur-organisasi", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/profil/pejabat-struktural", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],

            // Informasi
            ['url' => "{$baseUrl}/informasi/kejuruan", 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi/gedung-fasilitas", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi/ruang-kelas-workshop", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi/alumni", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi/testimoni", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],

            // Informasi Publik (PPID)
            ['url' => "{$baseUrl}/informasi-publik/berkala", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi-publik/serta-merta", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/informasi-publik/setiap-saat", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],

            // Pelayanan Publik
            ['url' => "{$baseUrl}/pelayanan-publik/maklumat", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/standar-pelayanan", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/alur-pelayanan", 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/survey-kepuasan", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/survey-kebutuhan", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/survey-kebekerjaan", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => "{$baseUrl}/pelayanan-publik/indeks-kepuasan", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],

            // JDIH
            ['url' => "{$baseUrl}/jdih", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $page;
        }

        // 2. Dynamic News Articles
        $articles = BeritaDanGaleri::where('jenis', 'berita')
            ->latest('updated_at')
            ->get();

        foreach ($articles as $article) {
            $urls[] = [
                'url'        => "{$baseUrl}/berita-informasi/berita/{$article->id}",
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => ($article->updated_at ?? $article->created_at ?? now())->toIso8601String(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $item) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($item['url'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$item['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$item['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
