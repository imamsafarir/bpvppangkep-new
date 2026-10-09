<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\PelayananPublik;
use Modules\Website\Services\MediaService;

class PelayananPublikController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(): Response
    {
        $pelayanan = PelayananPublik::first() ?? new PelayananPublik;

        return Inertia::render('Website::Admin/PelayananPublik/Index', [
            'pelayanan' => $pelayanan,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maklumat_pelayanan'         => 'nullable',
            'standar_pelayanan'          => 'nullable',
            'foto_alur_pelayanan'        => $request->hasFile('foto_alur_pelayanan') ? 'file|mimes:pdf,jpeg,png,jpg,webp,avif|max:20480' : 'nullable',
            'remove_foto_alur_pelayanan' => 'nullable|boolean',
            'deskripsi_alur_pelayanan'   => 'nullable|string',
            'survey_kepuasan_masyarakat' => 'nullable|string',
            'survey_kebutuhan_pelatihan' => 'nullable|string',
            'survey_kebekerjaan'         => 'nullable|string',
            'indeks_kepuasan_masyarakat' => 'nullable|string',
        ], [
            'foto_alur_pelayanan.file'  => 'Berkas bagan alur pelayanan tidak valid.',
            'foto_alur_pelayanan.mimes' => 'Format berkas bagan alur harus berupa PDF, JPG, PNG, atau WEBP.',
            'foto_alur_pelayanan.max'   => 'Ukuran berkas bagan alur maksimal adalah 20MB.',
        ]);

        $pelayanan = PelayananPublik::first();

        // Bagan Alur Pelayanan (Gambar AVIF atau Dokumen PDF)
        if ($request->boolean('remove_foto_alur_pelayanan') || $request->input('remove_foto_alur_pelayanan') === 'true' || $request->input('remove_foto_alur_pelayanan') === '1') {
            if ($pelayanan?->foto_alur_pelayanan) {
                $this->mediaService->deleteMedia($pelayanan->foto_alur_pelayanan);
            }
            $validated['foto_alur_pelayanan'] = null;
        } elseif ($request->hasFile('foto_alur_pelayanan')) {
            if ($pelayanan?->foto_alur_pelayanan) {
                $this->mediaService->deleteMedia($pelayanan->foto_alur_pelayanan);
            }
            $validated['foto_alur_pelayanan'] = $this->mediaService->storeMedia(
                $request->file('foto_alur_pelayanan'),
                'website/pelayanan/alur',
                'alur-pelayanan'
            );
        } elseif ($request->filled('foto_alur_pelayanan') && is_string($request->input('foto_alur_pelayanan'))) {
            $validated['foto_alur_pelayanan'] = $this->mediaService->cleanPath($request->input('foto_alur_pelayanan'));
        } elseif (! $request->filled('foto_alur_pelayanan') && ! $request->hasFile('foto_alur_pelayanan')) {
            unset($validated['foto_alur_pelayanan']);
        }

        // Maklumat Pelayanan: bersihkan path file lampiran dan hapus file yang tidak lagi digunakan
        if (isset($validated['maklumat_pelayanan'])) {
            $maklumat = is_string($validated['maklumat_pelayanan'])
                ? json_decode($validated['maklumat_pelayanan'], true)
                : $validated['maklumat_pelayanan'];

            if (is_array($maklumat)) {
                $newFiles = [];
                foreach ($maklumat as &$item) {
                    if (isset($item['file_maklumat']) && is_string($item['file_maklumat'])) {
                        $item['file_maklumat'] = $this->mediaService->cleanPath($item['file_maklumat']);
                        if (!empty($item['file_maklumat'])) {
                            $newFiles[] = $item['file_maklumat'];
                        }
                    }
                }
                $validated['maklumat_pelayanan'] = $maklumat;

                if ($pelayanan) {
                    $oldMaklumat = is_string($pelayanan->maklumat_pelayanan)
                        ? json_decode($pelayanan->maklumat_pelayanan, true)
                        : (array) $pelayanan->maklumat_pelayanan;
                    $oldFiles = [];
                    foreach ((array) $oldMaklumat as $item) {
                        if (!empty($item['file_maklumat']) && is_string($item['file_maklumat'])) {
                            $oldFiles[] = $this->mediaService->cleanPath($item['file_maklumat']);
                        }
                    }
                    $removedFiles = array_diff($oldFiles, $newFiles);
                    foreach ($removedFiles as $f) {
                        $this->mediaService->deleteMedia($f);
                    }
                }
            }
        }

        // Standar Pelayanan: bersihkan path file lampiran dan hapus file yang tidak lagi digunakan
        if (isset($validated['standar_pelayanan'])) {
            $standar = is_string($validated['standar_pelayanan'])
                ? json_decode($validated['standar_pelayanan'], true)
                : $validated['standar_pelayanan'];

            if (is_array($standar)) {
                $newFiles = [];
                foreach ($standar as &$item) {
                    if (isset($item['file_standar']) && is_string($item['file_standar'])) {
                        $item['file_standar'] = $this->mediaService->cleanPath($item['file_standar']);
                        if (!empty($item['file_standar'])) {
                            $newFiles[] = $item['file_standar'];
                        }
                    }
                }
                $validated['standar_pelayanan'] = $standar;

                if ($pelayanan) {
                    $oldStandar = is_string($pelayanan->standar_pelayanan)
                        ? json_decode($pelayanan->standar_pelayanan, true)
                        : (array) $pelayanan->standar_pelayanan;
                    $oldFiles = [];
                    foreach ((array) $oldStandar as $item) {
                        if (!empty($item['file_standar']) && is_string($item['file_standar'])) {
                            $oldFiles[] = $this->mediaService->cleanPath($item['file_standar']);
                        }
                    }
                    $removedFiles = array_diff($oldFiles, $newFiles);
                    foreach ($removedFiles as $f) {
                        $this->mediaService->deleteMedia($f);
                    }
                }
            }
        }

        unset($validated['remove_foto_alur_pelayanan']);

        if ($pelayanan) {
            $pelayanan->update($validated);
        } else {
            PelayananPublik::create($validated);
        }

        return back()->with('success', 'Pelayanan publik berhasil diperbarui.');
    }
}
