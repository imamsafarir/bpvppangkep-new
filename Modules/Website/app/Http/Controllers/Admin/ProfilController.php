<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\Profil;
use Modules\Website\Services\MediaService;

class ProfilController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(): Response
    {
        $profil = Profil::first() ?? new Profil;

        return Inertia::render('Website::Admin/Profil/Index', [
            'profil' => $profil,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chief_name'                  => 'nullable|string|max:255',
            'chief_nip'                   => 'nullable|string|max:255',
            'chief_photo_path'            => 'nullable',
            'remove_chief_photo_path'     => 'nullable|boolean',
            'sambutan_kepala'             => 'nullable|string',
            'tentang_kami'                => 'nullable|string',
            'ppid'                        => 'nullable|string',
            'tugas_fungsi'                => 'nullable|string',
            'visi_misi'                   => 'nullable|string',
            'struktur_organisasi'         => 'nullable',
            'remove_struktur_organisasi'  => 'nullable|boolean',
            'pejabat_struktural'          => 'nullable|array',
        ], [
            'chief_photo_path.file'        => 'Foto kepala balai harus berupa file yang valid.',
            'chief_photo_path.mimes'       => 'Format foto kepala balai harus PNG, JPG, JPEG, atau WEBP.',
            'chief_photo_path.max'         => 'Ukuran foto kepala balai tidak boleh melebihi 10MB.',
            'struktur_organisasi.file'     => 'Bagan struktur organisasi harus berupa file yang valid.',
            'struktur_organisasi.mimes'    => 'Format bagan struktur organisasi harus PNG, JPG, JPEG, WEBP, atau PDF.',
            'struktur_organisasi.max'      => 'Ukuran bagan struktur organisasi tidak boleh melebihi 20MB.',
        ]);

        $profil = Profil::first();

        // Handle Hapus / Ganti Foto Kepala Balai
        if ($request->boolean('remove_chief_photo_path')) {
            if ($profil?->chief_photo_path) {
                $this->mediaService->deleteMedia($profil->chief_photo_path);
            }
            $validated['chief_photo_path'] = null;
        } elseif ($request->hasFile('chief_photo_path')) {
            $request->validate([
                'chief_photo_path' => 'file|mimes:jpeg,png,jpg,webp|max:10240',
            ], [
                'chief_photo_path.mimes' => 'Format foto kepala balai harus PNG, JPG, JPEG, atau WEBP.',
                'chief_photo_path.max'   => 'Ukuran foto kepala balai tidak boleh melebihi 10MB.',
            ]);

            if ($profil?->chief_photo_path) {
                $this->mediaService->deleteMedia($profil->chief_photo_path);
            }
            $validated['chief_photo_path'] = $this->mediaService->storeImage(
                $request->file('chief_photo_path'),
                'website/profil/chief',
                $validated['chief_name'] ?? 'chief'
            );
        } elseif ($request->filled('chief_photo_path') && is_string($request->input('chief_photo_path'))) {
            $validated['chief_photo_path'] = $this->mediaService->cleanPath($request->input('chief_photo_path'));
        } else {
            unset($validated['chief_photo_path']);
        }

        // Handle Hapus / Ganti Bagan Struktur Organisasi
        if ($request->boolean('remove_struktur_organisasi')) {
            if ($profil?->struktur_organisasi) {
                $this->mediaService->deleteMedia($profil->struktur_organisasi);
            }
            $validated['struktur_organisasi'] = null;
        } elseif ($request->hasFile('struktur_organisasi')) {
            $request->validate([
                'struktur_organisasi' => 'file|mimes:jpeg,png,jpg,webp,pdf|max:20480',
            ], [
                'struktur_organisasi.mimes' => 'Format bagan struktur organisasi harus PNG, JPG, JPEG, WEBP, atau PDF.',
                'struktur_organisasi.max'   => 'Ukuran bagan struktur organisasi tidak boleh melebihi 20MB.',
            ]);

            if ($profil?->struktur_organisasi) {
                $this->mediaService->deleteMedia($profil->struktur_organisasi);
            }
            $validated['struktur_organisasi'] = $this->mediaService->storeMedia(
                $request->file('struktur_organisasi'),
                'website/profil/struktur',
                'struktur-organisasi'
            );
        } elseif ($request->filled('struktur_organisasi') && is_string($request->input('struktur_organisasi'))) {
            $validated['struktur_organisasi'] = $this->mediaService->cleanPath($request->input('struktur_organisasi'));
        } else {
            unset($validated['struktur_organisasi']);
        }

        // Pejabat Struktural: bersihkan path foto & hapus foto yang telah dihilangkan dari storage
        if (isset($validated['pejabat_struktural']) && is_array($validated['pejabat_struktural'])) {
            $newPejabatPhotos = [];
            foreach ($validated['pejabat_struktural'] as &$pejabat) {
                if (isset($pejabat['foto']) && is_string($pejabat['foto'])) {
                    $pejabat['foto'] = $this->mediaService->cleanPath($pejabat['foto']);
                    if (! empty($pejabat['foto'])) {
                        $newPejabatPhotos[] = $pejabat['foto'];
                    }
                }
            }

            if ($profil) {
                $oldPejabatData = $profil->pejabat_struktural;
                if (is_string($oldPejabatData)) {
                    $oldPejabatData = json_decode($oldPejabatData, true) ?? [];
                }
                $oldPejabatPhotos = [];
                if (is_array($oldPejabatData)) {
                    foreach ($oldPejabatData as $oldPejabat) {
                        if (! empty($oldPejabat['foto']) && is_string($oldPejabat['foto'])) {
                            $oldPejabatPhotos[] = $this->mediaService->cleanPath($oldPejabat['foto']);
                        }
                    }
                }
                $removedPejabatPhotos = array_diff(array_unique($oldPejabatPhotos), array_unique($newPejabatPhotos));
                foreach ($removedPejabatPhotos as $removedPhoto) {
                    $this->mediaService->deleteMedia($removedPhoto);
                }
            }
        }

        unset($validated['remove_chief_photo_path'], $validated['remove_struktur_organisasi']);

        if ($profil) {
            $profil->update($validated);
        } else {
            Profil::create($validated);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
