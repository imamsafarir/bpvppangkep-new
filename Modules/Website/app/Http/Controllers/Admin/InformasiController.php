<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\Informasi;
use Modules\Website\Services\MediaService;

class InformasiController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(): Response
    {
        $informasi = Informasi::first() ?? new Informasi;

        return Inertia::render('Website::Admin/Informasi/Index', [
            'informasi' => $informasi,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kejuruan'        => 'nullable|array',
            'gedung_fasilitas' => 'nullable|array',
            'kelas_workshop'  => 'nullable|array',
            'alumni'          => 'nullable|array',
            'testimoni'       => 'nullable|array',
            'kerjasama'       => 'nullable|array',
            'faq'             => 'nullable|array',
        ]);

        // Bersihkan seluruh path foto agar sesuai format relatif database (website/informasi/...)
        $photoKeys = [
            'kejuruan'         => 'foto_kejuruan',
            'gedung_fasilitas' => 'foto_fasilitas',
            'kelas_workshop'   => 'foto_ruangan',
            'alumni'           => 'foto_kegiatan_alumni',
            'testimoni'        => 'foto_alumni',
            'kerjasama'        => 'logo',
        ];

        foreach ($photoKeys as $section => $key) {
            if (isset($validated[$section]) && is_array($validated[$section])) {
                foreach ($validated[$section] as &$item) {
                    if (isset($item[$key]) && is_string($item[$key])) {
                        $item[$key] = $this->mediaService->cleanPath($item[$key]);
                    }
                    // Handle fallback jika item memiliki key alternatif 'foto'
                    if (isset($item['foto']) && is_string($item['foto'])) {
                        $item[$key] = $this->mediaService->cleanPath($item['foto']);
                        unset($item['foto']);
                    }
                }
            }
        }

        $informasi = Informasi::first();

        if ($informasi) {
            // Bandingkan foto lama dengan foto baru di tiap section untuk menghapus file dari storage jika dihapus
            foreach ($photoKeys as $section => $key) {
                $oldSectionData = $informasi->{$section};
                if (is_string($oldSectionData)) {
                    $oldSectionData = json_decode($oldSectionData, true) ?? [];
                }
                $oldPaths = [];
                if (is_array($oldSectionData)) {
                    foreach ($oldSectionData as $oldItem) {
                        $p = $oldItem[$key] ?? $oldItem['foto'] ?? null;
                        if (! empty($p) && is_string($p)) {
                            $oldPaths[] = $this->mediaService->cleanPath($p);
                        }
                    }
                }

                $newPaths = [];
                if (isset($validated[$section]) && is_array($validated[$section])) {
                    foreach ($validated[$section] as $newItem) {
                        $p = $newItem[$key] ?? null;
                        if (! empty($p) && is_string($p)) {
                            $newPaths[] = $this->mediaService->cleanPath($p);
                        }
                    }
                }

                $removed = array_diff(array_unique($oldPaths), array_unique($newPaths));
                foreach ($removed as $removedPath) {
                    $this->mediaService->deleteMedia($removedPath);
                }
            }

            $informasi->update($validated);
        } else {
            Informasi::create($validated);
        }

        return back()->with('success', 'Data informasi berhasil diperbarui.');
    }
}
