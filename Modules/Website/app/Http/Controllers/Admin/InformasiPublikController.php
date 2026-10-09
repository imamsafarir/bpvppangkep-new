<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\InformasiPublik;
use Modules\Website\Services\MediaService;

class InformasiPublikController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $kategori = $request->query('kategori');
        $sort = $request->query('sort', 'latest');
        $sortBy = $request->query('sort_by');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = InformasiPublik::query()
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_dokumen', 'like', "%{$s}%")
                        ->orWhere('deskripsi', 'like', "%{$s}%");
                });
            })
            ->when($kategori, fn($q, $k) => $q->where('kategori', $k));

        $allowedColumns = ['id', 'kategori', 'nama_dokumen', 'created_at', 'updated_at'];
        if ($sortBy && in_array($sortBy, $allowedColumns, true)) {
            $query->orderBy($sortBy, $sortDir);
        } elseif ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'nama_asc') {
            $query->orderBy('nama_dokumen', 'asc');
        } elseif ($sort === 'nama_desc') {
            $query->orderBy('nama_dokumen', 'desc');
        } else {
            $query->latest();
        }

        $dokumen = $query->paginate(10)->withQueryString();
        $kategoriList = InformasiPublik::select('kategori')
            ->distinct()
            ->pluck('kategori');

        return Inertia::render('Website::Admin/InformasiPublik/Index', [
            'dokumen'      => $dokumen,
            'kategoriList' => $kategoriList,
            'filters'      => [
                'search'   => $search ?? '',
                'kategori' => $kategori ?? '',
                'sort'     => $sort,
                'sort_by'  => $sortBy ?? '',
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori'     => 'required|string|max:255',
            'nama_dokumen' => 'required|string|max:255',
            'file_path'    => $request->hasFile('file_path') ? 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpeg,png,jpg,webp|max:51200' : 'nullable',
            'deskripsi'    => 'nullable|string',
        ], [
            'file_path.file'  => 'Berkas dokumen tidak valid.',
            'file_path.mimes' => 'Format berkas dokumen harus PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, atau RAR.',
            'file_path.max'   => 'Ukuran dokumen maksimal adalah 50MB.',
        ]);

        $kategoriFolder = Str::slug($validated['kategori'], '_');
        $folder = "website/informasi-publik/{$kategoriFolder}";

        if ($request->hasFile('file_path')) {
            $validated['file_path'] = $this->mediaService->storeMedia(
                $request->file('file_path'),
                $folder,
                $validated['nama_dokumen']
            );
        } elseif ($request->filled('file_path') && is_string($request->input('file_path'))) {
            $validated['file_path'] = $this->mediaService->cleanPath($request->input('file_path'));
        }

        InformasiPublik::create($validated);

        return back()->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function update(Request $request, InformasiPublik $informasiPublik): RedirectResponse
    {
        $validated = $request->validate([
            'kategori'         => 'required|string|max:255',
            'nama_dokumen'     => 'required|string|max:255',
            'file_path'        => $request->hasFile('file_path') ? 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpeg,png,jpg,webp|max:51200' : 'nullable',
            'remove_file_path' => 'nullable|boolean',
            'deskripsi'        => 'nullable|string',
        ], [
            'file_path.file'  => 'Berkas dokumen tidak valid.',
            'file_path.mimes' => 'Format berkas dokumen harus PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, atau RAR.',
            'file_path.max'   => 'Ukuran dokumen maksimal adalah 50MB.',
        ]);

        $kategoriFolder = Str::slug($validated['kategori'], '_');
        $folder = "website/informasi-publik/{$kategoriFolder}";

        if ($request->boolean('remove_file_path') || $request->input('remove_file_path') === 'true' || $request->input('remove_file_path') === '1') {
            if ($informasiPublik->file_path) {
                $this->mediaService->deleteMedia($informasiPublik->file_path);
            }
            $validated['file_path'] = null;
        } elseif ($request->hasFile('file_path')) {
            if ($informasiPublik->file_path) {
                $this->mediaService->deleteMedia($informasiPublik->file_path);
            }
            $validated['file_path'] = $this->mediaService->storeMedia(
                $request->file('file_path'),
                $folder,
                $validated['nama_dokumen']
            );
        } elseif ($request->filled('file_path') && is_string($request->input('file_path'))) {
            $validated['file_path'] = $this->mediaService->cleanPath($request->input('file_path'));
        } elseif (! $request->filled('file_path') && ! $request->hasFile('file_path')) {
            unset($validated['file_path']);
        }

        unset($validated['remove_file_path']);
        $informasiPublik->update($validated);

        return back()->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(InformasiPublik $informasiPublik): RedirectResponse
    {
        if ($informasiPublik->file_path) {
            $this->mediaService->deleteMedia($informasiPublik->file_path);
        }
        $informasiPublik->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
