<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\BeritaDanGaleri;
use Modules\Website\Models\Informasi;
use Modules\Website\Models\InformasiPublik;
use Modules\Website\Models\Jdih;
use Modules\Website\Models\Kunjungan;
use Modules\Website\Models\PelayananPublik;
use Modules\Website\Models\Profil;
use Modules\Website\Models\WebsiteSetting;

class WebsiteController extends Controller
{
    /**
     * Beranda Website Publik
     */
    public function home(): Response
    {
        // Track kunjungan harian per IP (cached agar tidak query DB setiap refresh)
        $clientIp = request()->ip();
        $visitCacheKey = 'visitor_logged_' . md5($clientIp . '_' . date('Y-m-d'));
        if (! Cache::has($visitCacheKey)) {
            try {
                Kunjungan::firstOrCreate([
                    'ip_address' => $clientIp,
                    'tanggal'    => today(),
                ]);
                Cache::put($visitCacheKey, true, 86400);
            } catch (\Throwable $e) {
                // Ignore DB uniqueness race condition
            }
        }

        $settings = WebsiteSetting::first() ?? new WebsiteSetting;
        $beritaTerbaru = BeritaDanGaleri::where('jenis', 'berita')->latest()->take(6)->get();
        $dokumenInformasi = InformasiPublik::latest()->take(6)->get();
        $infoModel = Informasi::first();

        $stats = Cache::remember('website_home_stats', 300, function () {
            return [
                'total_informasi' => InformasiPublik::count(),
                'total_jdih'      => Jdih::count(),
                'total_berita'    => BeritaDanGaleri::where('jenis', 'berita')->count(),
                'total_unduhan'   => (int) (InformasiPublik::sum('jumlah_diunduh') + Jdih::sum('jumlah_diunduh')),
                'total_kunjungan' => Kunjungan::count(),
            ];
        });

        return Inertia::render('Website::Home', [
            'settings'        => $settings,
            'berita_terbaru'  => $beritaTerbaru,
            'informasi'       => $dokumenInformasi,
            'partners'        => $infoModel?->kerjasama ?? [],
            'faqs'            => $infoModel?->faq ?? [],
            'total_informasi' => $stats['total_informasi'],
            'total_jdih'      => $stats['total_jdih'],
            'total_berita'    => $stats['total_berita'],
            'total_unduhan'   => $stats['total_unduhan'],
            'total_kunjungan' => $stats['total_kunjungan'],
        ]);
    }

    // ── PROFIL ────────────────────────────────────────────────────────

    public function sambutan(): Response
    {
        return Inertia::render('Website::Profil/Sambutan', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function tentangKami(): Response
    {
        return Inertia::render('Website::Profil/TentangKami', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function ppid(): Response
    {
        return Inertia::render('Website::Profil/Ppid', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function visiMisi(): Response
    {
        return Inertia::render('Website::Profil/VisiMisi', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function tugasFungsi(): Response
    {
        return Inertia::render('Website::Profil/TugasFungsi', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function struktur(): Response
    {
        return Inertia::render('Website::Profil/Struktur', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    public function pejabat(): Response
    {
        return Inertia::render('Website::Profil/Pejabat', [
            'profil' => Profil::first() ?? new Profil,
        ]);
    }

    // ── INFORMASI ─────────────────────────────────────────────────────

    public function kejuruan(): Response
    {
        $info = Informasi::first();
        return Inertia::render('Website::Informasi/Kejuruan', [
            'kejuruan' => $info?->kejuruan ?? [],
        ]);
    }

    public function fasilitas(): Response
    {
        $info = Informasi::first();
        return Inertia::render('Website::Informasi/Fasilitas', [
            'fasilitas' => $info?->gedung_fasilitas ?? [],
        ]);
    }

    public function workshop(): Response
    {
        $info = Informasi::first();
        return Inertia::render('Website::Informasi/Workshop', [
            'workshop' => $info?->kelas_workshop ?? [],
        ]);
    }

    public function alumni(): Response
    {
        $info = Informasi::first();
        return Inertia::render('Website::Informasi/Alumni', [
            'alumni' => $info?->alumni ?? [],
        ]);
    }

    public function testimoni(): Response
    {
        $info = Informasi::first();
        return Inertia::render('Website::Informasi/Testimoni', [
            'testimoni' => $info?->testimoni ?? [],
        ]);
    }

    // ── INFORMASI PUBLIK (PPID) ───────────────────────────────────────

    public function informasiBerkala(): Response
    {
        return Inertia::render('Website::InformasiPublik/Berkala', [
            'dokumen' => InformasiPublik::whereIn('kategori', ['Berkala', 'berkala'])->latest()->get(),
        ]);
    }

    public function informasiSertaMerta(): Response
    {
        return Inertia::render('Website::InformasiPublik/SertaMerta', [
            'dokumen' => InformasiPublik::whereIn('kategori', ['Serta Merta', 'serta_merta', 'serta-merta'])->latest()->get(),
        ]);
    }

    public function informasiSetiapSaat(): Response
    {
        return Inertia::render('Website::InformasiPublik/SetiapSaat', [
            'dokumen' => InformasiPublik::whereIn('kategori', ['Setiap Saat', 'setiap_saat', 'setiap-saat'])->latest()->get(),
        ]);
    }

    // ── PELAYANAN PUBLIK ──────────────────────────────────────────────

    public function maklumat(): Response
    {
        return Inertia::render('Website::PelayananPublik/Maklumat', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function standar(): Response
    {
        return Inertia::render('Website::PelayananPublik/Standar', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function alur(): Response
    {
        return Inertia::render('Website::PelayananPublik/Alur', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function surveyKepuasan(): Response
    {
        return Inertia::render('Website::PelayananPublik/SurveyKepuasan', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function surveyKebutuhan(): Response
    {
        return Inertia::render('Website::PelayananPublik/SurveyKebutuhan', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function surveyKebekerjaan(): Response
    {
        return Inertia::render('Website::PelayananPublik/SurveyKebekerjaan', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    public function indeksKepuasan(): Response
    {
        return Inertia::render('Website::PelayananPublik/IndeksKepuasan', [
            'pelayanan' => PelayananPublik::first() ?? new PelayananPublik,
        ]);
    }

    // ── BERITA & GALERI ───────────────────────────────────────────────

    public function beritaIndex(): Response
    {
        return Inertia::render('Website::Berita/Index', [
            'berita_list' => BeritaDanGaleri::where('jenis', 'berita')->latest()->get(),
        ]);
    }

    public function beritaShow($id): Response
    {
        $berita = BeritaDanGaleri::where('jenis', 'berita')->findOrFail($id);
        $prevBerita = BeritaDanGaleri::where('jenis', 'berita')->where('id', '<', $id)->latest('id')->first();
        $nextBerita = BeritaDanGaleri::where('jenis', 'berita')->where('id', '>', $id)->oldest('id')->first();
        $beritaTerkait = BeritaDanGaleri::where('jenis', 'berita')->where('id', '!=', $id)->latest()->take(3)->get();

        return Inertia::render('Website::Berita/Show', [
            'berita'        => $berita,
            'prevBerita'    => $prevBerita,
            'nextBerita'    => $nextBerita,
            'beritaTerkait' => $beritaTerkait,
        ]);
    }

    public function galeriIndex(): Response
    {
        return Inertia::render('Website::Berita/Galeri', [
            'galeri_list' => BeritaDanGaleri::where('jenis', 'galeri')->latest()->get(),
        ]);
    }

    // ── JDIH ──────────────────────────────────────────────────────────

    public function jdihIndex(): Response
    {
        return Inertia::render('Website::Jdih/Index', [
            'jdih_list' => Jdih::latest()->get(),
        ]);
    }

    /**
     * Download Dokumen Informasi Publik & update counter unduhan
     */
    public function downloadInformasiPublik($id)
    {
        $dokumen = InformasiPublik::findOrFail($id);
        $dokumen->increment('jumlah_diunduh');

        $disk = Storage::disk('public');
        $cleanPath = ltrim(str_replace('/storage/', '', $dokumen->file_path), '/');

        if ($dokumen->file_path && $disk->exists($cleanPath)) {
            $ext = pathinfo($cleanPath, PATHINFO_EXTENSION);
            $filename = Str::slug($dokumen->nama_dokumen) . ($ext ? ".{$ext}" : '');
            return $disk->download($cleanPath, $filename);
        }

        if ($dokumen->file_path && (str_starts_with($dokumen->file_path, 'http://') || str_starts_with($dokumen->file_path, 'https://'))) {
            return redirect()->away($dokumen->file_path);
        }

        return redirect('/storage/' . $cleanPath);
    }

    /**
     * Download Produk Hukum JDIH & update counter unduhan
     */
    public function downloadJdih($id)
    {
        $jdih = Jdih::findOrFail($id);
        $jdih->increment('jumlah_diunduh');

        $disk = Storage::disk('public');
        $filePath = $jdih->file_path ?? $jdih->file_dokumen;
        $cleanPath = ltrim(str_replace('/storage/', '', $filePath), '/');

        if ($filePath && $disk->exists($cleanPath)) {
            $ext = pathinfo($cleanPath, PATHINFO_EXTENSION);
            $filename = Str::slug($jdih->judul_peraturan ?? 'dokumen-jdih') . ($ext ? ".{$ext}" : '');
            return $disk->download($cleanPath, $filename);
        }

        if ($filePath && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            return redirect()->away($filePath);
        }

        return redirect('/storage/' . $cleanPath);
    }
}
