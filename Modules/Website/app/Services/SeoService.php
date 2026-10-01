<?php

namespace Modules\Website\Services;

use Illuminate\Support\Str;
use Modules\Website\Models\WebsiteSetting;

class SeoService
{
    /**
     * Resolve comprehensive SEO metadata for the current request / Inertia page.
     *
     * @param  array<string, mixed>  $pageData
     * @return array<string, mixed>
     */
    public function resolveMetadata(array $pageData = []): array
    {
        $settings = WebsiteSetting::first();
        $siteName = $settings?->website_name ?? 'BPVP Pangkep';
        if (empty($siteName) || $siteName === 'Laravel') {
            $siteName = 'BPVP Pangkep';
        }

        $component = $pageData['component'] ?? '';
        $props = $pageData['props'] ?? [];

        $defaultDescription = 'Website Resmi Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan - Kementerian Ketenagakerjaan RI. Pusat pelatihan kerja berbasis kompetensi, uji sertifikasi BNSP gratis, dan layanan keterbukaan informasi publik (PPID).';
        $defaultKeywords = 'BPVP Pangkep, Balai Pelatihan Vokasi dan Produktivitas Pangkep, Kemnaker RI, BLK Pangkep, Pelatihan Gratis, Sertifikasi BNSP, Pelatihan Kerja Makassar Pangkep, Kejuruan Otomotif, Teknik Las, Listrik, TIK, Garmen, PPID BPVP Pangkep, Pelatihan Vokasi Sulawesi Selatan';
        $defaultOgImage = asset('images/bpvp-pangkep-og.png');

        $title = "{$siteName} - Balai Pelatihan Vokasi & Produktivitas Kemnaker RI";
        $description = $defaultDescription;
        $keywords = $defaultKeywords;
        $ogImage = $defaultOgImage;
        $ogType = 'website';
        $canonical = url()->current();
        $breadcrumbs = [
            ['name' => 'Beranda', 'url' => url('/')],
        ];
        $articleData = null;

        // Berita Detail
        if ($component === 'Website::Berita/Show' && isset($props['berita'])) {
            $berita = $props['berita'];
            $judul = $berita['judul_berita'] ?? 'Berita Terkini';
            $title = "{$judul} | Berita {$siteName}";

            $rawContent = $berita['konten_berita'] ?? '';
            $cleanContent = trim(preg_replace('/\s+/', ' ', strip_tags($rawContent)));
            if (!empty($cleanContent)) {
                $description = Str::limit($cleanContent, 160);
            }

            if (!empty($berita['tags'])) {
                $keywords = $berita['tags'] . ', ' . $defaultKeywords;
            }

            $foto = $berita['file_foto'] ?? null;
            if (is_string($foto) && str_starts_with($foto, '[')) {
                $decoded = json_decode($foto, true);
                if (is_array($decoded) && !empty($decoded[0])) {
                    $foto = $decoded[0];
                }
            } elseif (is_array($foto) && !empty($foto[0])) {
                $foto = $foto[0];
            }

            if (!empty($foto)) {
                if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
                    $ogImage = $foto;
                } else {
                    $cleanFoto = ltrim(str_replace('/storage/', '', $foto), '/');
                    $ogImage = asset('storage/' . $cleanFoto);
                }
            }

            $ogType = 'article';
            $articleData = [
                'headline'       => $judul,
                'datePublished'  => $berita['created_at'] ?? now()->toIso8601String(),
                'dateModified'   => $berita['updated_at'] ?? now()->toIso8601String(),
                'author'         => 'Humas BPVP Pangkep',
                'description'    => $description,
                'image'          => $ogImage,
            ];

            $breadcrumbs[] = ['name' => 'Berita & Informasi', 'url' => url('/berita-informasi/daftar-berita')];
            $breadcrumbs[] = ['name' => $judul, 'url' => $canonical];
        }
        // Daftar Berita
        elseif ($component === 'Website::Berita/Index') {
            $title = "Berita & Pengumuman Terbaru | {$siteName}";
            $description = "Kumpulan berita, artikel, agenda, dan pengumuman terbaru seputar kegiatan pelatihan kerja di BPVP Pangkep Kementerian Ketenagakerjaan RI.";
            $breadcrumbs[] = ['name' => 'Berita & Pengumuman', 'url' => $canonical];
        }
        // Galeri
        elseif ($component === 'Website::Berita/Galeri') {
            $title = "Galeri Dokumentasi Kegiatan | {$siteName}";
            $description = "Dokumentasi foto dan galeri kegiatan pelatihan vokasi, workshop industri, dan kunjungan di Balai Pelatihan Vokasi dan Produktivitas Pangkep.";
            $breadcrumbs[] = ['name' => 'Galeri Kegiatan', 'url' => $canonical];
        }
        // Profil Pages
        elseif (str_starts_with($component, 'Website::Profil/')) {
            $sub = str_replace('Website::Profil/', '', $component);
            $titles = [
                'Sambutan'     => 'Sambutan Kepala Balai',
                'TentangKami'  => 'Tentang Kami & Profil Balai',
                'Ppid'         => 'Profil PPID (Pejabat Pengelola Informasi & Dokumentasi)',
                'VisiMisi'     => 'Visi, Misi & Nilai Budaya Kerja',
                'TugasFungsi'  => 'Tugas Pokok & Fungsi Organisasi',
                'Struktur'     => 'Bagan Struktur Organisasi',
                'Pejabat'      => 'Pejabat Struktural Balai',
            ];
            $pageName = $titles[$sub] ?? 'Profil Lembaga';
            $title = "{$pageName} | {$siteName} - Kemnaker RI";
            $description = "Informasi resmi {$pageName} Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan, Kementerian Ketenagakerjaan RI.";
            $breadcrumbs[] = ['name' => 'Profil', 'url' => url('/profil/tentang-kami')];
            $breadcrumbs[] = ['name' => $pageName, 'url' => $canonical];
        }
        // Informasi (Kejuruan, Fasilitas, Workshop, Alumni, Testimoni)
        elseif (str_starts_with($component, 'Website::Informasi/')) {
            $sub = str_replace('Website::Informasi/', '', $component);
            $titles = [
                'Kejuruan'  => 'Program Kejuruan & Pelatihan Vokasi',
                'Fasilitas' => 'Gedung & Fasilitas Balai',
                'Workshop'  => 'Ruang Kelas & Workshop Praktik Industri',
                'Alumni'    => 'Data Alumni & Penempatan Kerja',
                'Testimoni' => 'Testimoni Alumni & Mitra Industri',
            ];
            $pageName = $titles[$sub] ?? 'Informasi Pelatihan';
            $title = "{$pageName} | {$siteName}";
            if ($sub === 'Kejuruan') {
                $description = "Daftar program kejuruan pelatihan vokasi di BPVP Pangkep: Otomotif, Teknik Las, Kelistrikan, TIK, Garmen Apparel, Refrigerasi, dan lainnya. Berbasis kompetensi & sertifikasi BNSP.";
                $keywords = "Kejuruan BPVP Pangkep, Pelatihan Las Pangkep, Pelatihan Otomotif Kemnaker, Kursus Komputer Gratis, Sertifikasi BNSP, " . $defaultKeywords;
            } else {
                $description = "Informasi lengkap mengenai {$pageName} di Balai Pelatihan Vokasi dan Produktivitas Pangkep Kemnaker RI.";
            }
            $breadcrumbs[] = ['name' => 'Informasi', 'url' => url('/informasi/kejuruan')];
            $breadcrumbs[] = ['name' => $pageName, 'url' => $canonical];
        }
        // Informasi Publik (PPID)
        elseif (str_starts_with($component, 'Website::InformasiPublik/')) {
            $sub = str_replace('Website::InformasiPublik/', '', $component);
            $titles = [
                'Berkala'    => 'Informasi Publik Berkala',
                'SertaMerta' => 'Informasi Publik Serta Merta',
                'SetiapSaat' => 'Informasi Publik Setiap Saat',
            ];
            $pageName = $titles[$sub] ?? 'Informasi Publik PPID';
            $title = "{$pageName} | PPID {$siteName}";
            $description = "Layanan keterbukaan informasi publik resmi kategori {$pageName} di PPID BPVP Pangkep sesuai regulasi UU KIP.";
            $breadcrumbs[] = ['name' => 'PPID', 'url' => url('/profil/ppid-pelayanan')];
            $breadcrumbs[] = ['name' => $pageName, 'url' => $canonical];
        }
        // Pelayanan Publik
        elseif (str_starts_with($component, 'Website::PelayananPublik/')) {
            $sub = str_replace('Website::PelayananPublik/', '', $component);
            $titles = [
                'Maklumat'          => 'Maklumat Pelayanan Publik',
                'Standar'           => 'Standar Operasional Pelayanan Publik',
                'Alur'              => 'Alur & Prosedur Pelayanan',
                'SurveyKepuasan'    => 'Survei Kepuasan Masyarakat (SKM)',
                'SurveyKebutuhan'   => 'Survei Kebutuhan Pelatihan Kerja',
                'SurveyKebekerjaan' => 'Survei Kebekerjaan Alumni',
                'IndeksKepuasan'    => 'Indeks Kepuasan Pelayanan',
            ];
            $pageName = $titles[$sub] ?? 'Pelayanan Publik';
            $title = "{$pageName} | Layanan Publik {$siteName}";
            $description = "Layanan terpadu dan standar operasional {$pageName} di Balai Pelatihan Vokasi dan Produktivitas Pangkep.";
            $breadcrumbs[] = ['name' => 'Pelayanan Publik', 'url' => url('/pelayanan-publik/maklumat')];
            $breadcrumbs[] = ['name' => $pageName, 'url' => $canonical];
        }
        // JDIH
        elseif ($component === 'Website::Jdih/Index') {
            $title = "JDIH - Jaringan Dokumentasi & Informasi Hukum | {$siteName}";
            $description = "Pusat produk hukum, regulasi ketenagakerjaan, peraturan menteri, dan keputusan resmi JDIH di lingkungan BPVP Pangkep.";
            $breadcrumbs[] = ['name' => 'JDIH', 'url' => $canonical];
        }

        // Generate JSON-LD Schemas
        $schemas = $this->buildSchemas($siteName, $title, $description, $canonical, $ogImage, $breadcrumbs, $articleData, $settings);

        return [
            'site_name'    => $siteName,
            'title'        => $title,
            'description'  => $description,
            'keywords'     => $keywords,
            'canonical'    => $canonical,
            'og_image'     => $ogImage,
            'og_type'      => $ogType,
            'breadcrumbs'  => $breadcrumbs,
            'article_data' => $articleData,
            'schemas'      => $schemas,
        ];
    }

    /**
     * Build Schema.org structured data array.
     */
    protected function buildSchemas(
        string $siteName,
        string $title,
        string $description,
        string $canonical,
        string $ogImage,
        array $breadcrumbs,
        ?array $articleData,
        ?WebsiteSetting $settings
    ): array {
        $baseUrl = url('/');

        // 1. Organization / GovernmentOrganization Schema
        $orgSchema = [
            '@context'          => 'https://schema.org',
            '@type'             => 'GovernmentOrganization',
            '@id'               => "{$baseUrl}#organization",
            'name'              => 'Balai Pelatihan Vokasi dan Produktivitas Pangkep',
            'alternateName'     => ['BPVP Pangkep', 'BLK Pangkep', 'BBPVP Pangkep', 'BPVP Pangkajene dan Kepulauan'],
            'url'               => $baseUrl,
            'logo'              => [
                '@type'  => 'ImageObject',
                'url'    => asset('icons/icon-512x512.png'),
                'width'  => 512,
                'height' => 512,
            ],
            'image'             => asset('images/bpvp-pangkep-og.png'),
            'description'       => 'Unit Pelaksana Teknis Pusat (UPTP) di bawah Direktorat Jenderal Pembinaan Pelatihan Vokasi dan Produktivitas, Kementerian Ketenagakerjaan RI.',
            'parentOrganization' => [
                '@type'         => 'GovernmentOrganization',
                'name'          => 'Kementerian Ketenagakerjaan Republik Indonesia',
                'alternateName' => 'Kemnaker RI',
                'url'           => 'https://kemnaker.go.id',
            ],
            'address'           => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $settings?->address ?? 'Jl. BLK No.2 Poros Makassar-Parepare Km.83 Mandalle',
                'addressLocality' => 'Pangkajene dan Kepulauan',
                'addressRegion'   => 'Sulawesi Selatan',
                'postalCode'      => '90655',
                'addressCountry'  => 'ID',
            ],
            'contactPoint'      => [
                [
                    '@type'             => 'ContactPoint',
                    'telephone'         => $settings?->phone_number ? "+62" . ltrim($settings->phone_number, '0') : '+6285343747243',
                    'contactType'       => 'customer service',
                    'areaServed'        => 'ID',
                    'availableLanguage' => ['Indonesian'],
                ],
            ],
            'sameAs'            => array_values(array_filter([
                $settings?->facebook_url ?: 'https://facebook.com/bpvppangkep',
                $settings?->instagram_url ?: 'https://instagram.com/bpvppangkep',
                $settings?->youtube_url ?: 'https://youtube.com/@bpvppangkep',
                $settings?->tiktok_url ?: 'https://tiktok.com/@bpvppangkep',
            ])),
        ];

        // 2. WebSite Schema with Sitelinks SearchBox
        $webSiteSchema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => "{$baseUrl}#website",
            'url'             => $baseUrl,
            'name'            => $siteName,
            'alternateName'   => 'Portal Resmi BPVP Pangkep - Kemnaker RI',
            'publisher'       => [
                '@id' => "{$baseUrl}#organization",
            ],
            'inLanguage'      => 'id-ID',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => "{$baseUrl}/berita-informasi/daftar-berita?q={search_term_string}",
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $schemas = [$orgSchema, $webSiteSchema];

        // 3. BreadcrumbList Schema (if on internal page)
        if (count($breadcrumbs) > 1) {
            $breadcrumbElements = [];
            foreach ($breadcrumbs as $idx => $bc) {
                $breadcrumbElements[] = [
                    '@type'    => 'ListItem',
                    'position' => $idx + 1,
                    'name'     => $bc['name'],
                    'item'     => $bc['url'],
                ];
            }

            $schemas[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $breadcrumbElements,
            ];
        }

        // 4. NewsArticle Schema
        if ($articleData) {
            $schemas[] = [
                '@context'         => 'https://schema.org',
                '@type'            => 'NewsArticle',
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id'   => $canonical,
                ],
                'headline'         => $articleData['headline'],
                'image'            => [$articleData['image']],
                'datePublished'    => $articleData['datePublished'],
                'dateModified'     => $articleData['dateModified'],
                'author'           => [
                    '@type' => 'Organization',
                    'name'  => $articleData['author'],
                    'url'   => $baseUrl,
                ],
                'publisher'        => [
                    '@id' => "{$baseUrl}#organization",
                ],
                'description'      => $articleData['description'],
            ];
        }

        return $schemas;
    }
}
