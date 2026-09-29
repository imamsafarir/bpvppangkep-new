<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\BeritaDanGaleri;
use Modules\Website\Services\MediaService;

class BeritaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(Request $request): Response
    {
        $searchBerita = $request->query('search_berita');
        $sortBerita = $request->query('sort_berita', 'latest');
        $sortByBerita = $request->query('sort_by_berita');
        $sortDirBerita = strtolower($request->query('sort_dir_berita', 'asc')) === 'desc' ? 'desc' : 'asc';

        $beritaQuery = BeritaDanGaleri::where('jenis', 'berita')
            ->when($searchBerita, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul_berita', 'like', "%{$search}%")
                        ->orWhere('konten_berita', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            });

        $allowedBeritaColumns = ['id', 'judul_berita', 'tags', 'created_at', 'updated_at'];
        if ($sortByBerita && in_array($sortByBerita, $allowedBeritaColumns, true)) {
            $beritaQuery->orderBy($sortByBerita, $sortDirBerita);
        } elseif ($sortBerita === 'oldest') {
            $beritaQuery->oldest();
        } elseif ($sortBerita === 'title_asc') {
            $beritaQuery->orderBy('judul_berita', 'asc');
        } elseif ($sortBerita === 'title_desc') {
            $beritaQuery->orderBy('judul_berita', 'desc');
        } else {
            $beritaQuery->latest();
        }

        $berita = $beritaQuery->paginate(10, ['*'], 'page_berita')->withQueryString();

        $searchGaleri = $request->query('search_galeri');
        $sortGaleri = $request->query('sort_galeri', 'latest');
        $sortByGaleri = $request->query('sort_by_galeri');
        $sortDirGaleri = strtolower($request->query('sort_dir_galeri', 'asc')) === 'desc' ? 'desc' : 'asc';

        $galeriQuery = BeritaDanGaleri::where('jenis', 'galeri')
            ->when($searchGaleri, function ($q, $search) {
                $q->where('keterangan_galeri', 'like', "%{$search}%");
            });

        $allowedGaleriColumns = ['id', 'keterangan_galeri', 'created_at'];
        if ($sortByGaleri && in_array($sortByGaleri, $allowedGaleriColumns, true)) {
            $galeriQuery->orderBy($sortByGaleri, $sortDirGaleri);
        } elseif ($sortGaleri === 'oldest') {
            $galeriQuery->oldest();
        } else {
            $galeriQuery->latest();
        }

        $galeri = $galeriQuery->paginate(12, ['*'], 'page_galeri')->withQueryString();

        return Inertia::render('Website::Admin/Berita/Index', [
            'berita' => $berita,
            'galeri' => $galeri,
            'filters' => [
                'search_berita' => $searchBerita ?? '',
                'sort_berita' => $sortBerita,
                'sort_by_berita' => $sortByBerita ?? '',
                'sort_dir_berita' => $sortDirBerita,
                'search_galeri' => $searchGaleri ?? '',
                'sort_galeri' => $sortGaleri,
                'sort_by_galeri' => $sortByGaleri ?? '',
                'sort_dir_galeri' => $sortDirGaleri,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis'             => 'required|in:berita,galeri',
            'judul_berita'      => 'nullable|string|max:255',
            'tags'              => 'nullable',
            'konten_berita'     => 'nullable|string',
            'file_foto'         => 'nullable',
            'keterangan_galeri' => 'nullable|string|max:255',
        ]);

        $folder = $validated['jenis'] === 'galeri' ? 'website/galeri/foto' : 'website/berita/sampul';
        $slug = $validated['judul_berita'] ?? $validated['keterangan_galeri'] ?? $validated['jenis'];

        if ($request->hasFile('file_foto')) {
            $path = $this->mediaService->storeImage(
                $request->file('file_foto'),
                $folder,
                $slug
            );
            $validated['file_foto'] = json_encode([$path]);
        } elseif ($request->filled('file_foto') && is_string($request->input('file_foto'))) {
            $cleaned = $this->mediaService->cleanPath($request->input('file_foto'));
            $validated['file_foto'] = json_encode([$cleaned]);
        }

        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        BeritaDanGaleri::create($validated);

        return back()->with('success', 'Data berhasil disimpan.');
    }

    public function update(Request $request, BeritaDanGaleri $beritaDanGaleri): RedirectResponse
    {
        $validated = $request->validate([
            'jenis'             => 'required|in:berita,galeri',
            'judul_berita'      => 'nullable|string|max:255',
            'tags'              => 'nullable',
            'konten_berita'     => 'nullable|string',
            'file_foto'         => 'nullable',
            'keterangan_galeri' => 'nullable|string|max:255',
        ]);

        $folder = $validated['jenis'] === 'galeri' ? 'website/galeri/foto' : 'website/berita/sampul';
        $slug = $validated['judul_berita'] ?? $validated['keterangan_galeri'] ?? $validated['jenis'];

        if ($request->hasFile('file_foto')) {
            $this->deletePhotos($beritaDanGaleri->file_foto);
            $path = $this->mediaService->storeImage(
                $request->file('file_foto'),
                $folder,
                $slug
            );
            $validated['file_foto'] = json_encode([$path]);
        } elseif ($request->filled('file_foto') && is_string($request->input('file_foto'))) {
            $cleaned = $this->mediaService->cleanPath($request->input('file_foto'));
            $validated['file_foto'] = json_encode([$cleaned]);
        } elseif (!$request->filled('file_foto') && !$request->hasFile('file_foto')) {
            unset($validated['file_foto']);
        }

        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        $beritaDanGaleri->update($validated);

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(BeritaDanGaleri $beritaDanGaleri): RedirectResponse
    {
        $this->deletePhotos($beritaDanGaleri->file_foto);
        $beritaDanGaleri->delete();

        return back()->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Hapus berkas foto (AVIF dan aslinya) dari storage.
     */
    protected function deletePhotos($fileFoto): void
    {
        if (!$fileFoto) {
            return;
        }

        $photos = is_string($fileFoto) ? (json_decode($fileFoto, true) ?: [$fileFoto]) : (array) $fileFoto;
        foreach ($photos as $photo) {
            if ($photo && is_string($photo)) {
                $this->mediaService->deleteMedia($this->mediaService->cleanPath($photo));
            }
        }
    }
}
