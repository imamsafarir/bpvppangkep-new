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
            'chief_name'          => 'nullable|string|max:255',
            'chief_nip'           => 'nullable|string|max:255',
            'chief_photo_path'    => 'nullable',
            'sambutan_kepala'     => 'nullable|string',
            'tentang_kami'        => 'nullable|string',
            'ppid'                => 'nullable|string',
            'tugas_fungsi'        => 'nullable|string',
            'visi_misi'           => 'nullable|string',
            'struktur_organisasi' => 'nullable',
            'pejabat_struktural'  => 'nullable|array',
        ]);

        $profil = Profil::first();

        // Foto Kepala Balai (AVIF + simpan asli di website/profil/chief)
        if ($request->hasFile('chief_photo_path')) {
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
        } elseif (!$request->filled('chief_photo_path') && !$request->hasFile('chief_photo_path')) {
            unset($validated['chief_photo_path']);
        }

        // Gambar / Dokumen Struktur Organisasi (AVIF untuk gambar, atau PDF dokumen)
        if ($request->hasFile('struktur_organisasi')) {
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
        } elseif (!$request->filled('struktur_organisasi') && !$request->hasFile('struktur_organisasi')) {
            unset($validated['struktur_organisasi']);
        }

        // Pejabat Struktural: bersihkan path foto agar sesuai format database
        if (isset($validated['pejabat_struktural']) && is_array($validated['pejabat_struktural'])) {
            foreach ($validated['pejabat_struktural'] as &$pejabat) {
                if (isset($pejabat['foto']) && is_string($pejabat['foto'])) {
                    $pejabat['foto'] = $this->mediaService->cleanPath($pejabat['foto']);
                }
            }
        }

        if ($profil) {
            $profil->update($validated);
        } else {
            Profil::create($validated);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
