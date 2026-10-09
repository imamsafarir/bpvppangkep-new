<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\Jdih;
use Modules\Website\Services\MediaService;

class JdihController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $sort = $request->query('sort', 'latest');
        $sortBy = $request->query('sort_by');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Jdih::query()
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('judul_peraturan', 'like', "%{$s}%")
                        ->orWhere('nomor_peraturan', 'like', "%{$s}%")
                        ->orWhere('tentang', 'like', "%{$s}%");
                });
            })
            ->when($status, fn($q, $st) => $q->where('status_peraturan', $st));

        $allowedColumns = ['id', 'nomor_peraturan', 'judul_peraturan', 'status_peraturan', 'created_at', 'updated_at'];
        if ($sortBy && in_array($sortBy, $allowedColumns, true)) {
            $query->orderBy($sortBy, $sortDir);
        } elseif ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'nomor_asc') {
            $query->orderBy('nomor_peraturan', 'asc');
        } else {
            $query->latest();
        }

        $peraturan = $query->paginate(10)->withQueryString();
        $statusList = Jdih::select('status_peraturan')
            ->distinct()
            ->pluck('status_peraturan');

        return Inertia::render('Website::Admin/Jdih/Index', [
            'peraturan'  => $peraturan,
            'statusList' => $statusList,
            'filters'    => [
                'search'   => $search ?? '',
                'status'   => $status ?? '',
                'sort'     => $sort,
                'sort_by'  => $sortBy ?? '',
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status_peraturan' => 'required|string|max:255',
            'judul_peraturan'  => 'required|string|max:255',
            'nomor_peraturan'  => 'required|string|max:255',
            'file_path'        => $request->hasFile('file_path') ? 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:51200' : 'nullable',
            'tentang'          => 'nullable|string',
        ], [
            'file_path.file'  => 'Berkas dokumen tidak valid.',
            'file_path.mimes' => 'Format dokumen peraturan harus berupa file PDF atau dokumen terkait.',
            'file_path.max'   => 'Ukuran dokumen maksimal adalah 50MB.',
        ]);

        if ($request->hasFile('file_path')) {
            $validated['file_path'] = $this->mediaService->storeDocument(
                $request->file('file_path'),
                'website/jdih',
                $validated['nomor_peraturan'] . '-' . $validated['judul_peraturan']
            );
        } elseif ($request->filled('file_path') && is_string($request->input('file_path'))) {
            $validated['file_path'] = $this->mediaService->cleanPath($request->input('file_path'));
        }

        Jdih::create($validated);

        return back()->with('success', 'Peraturan berhasil ditambahkan.');
    }

    public function update(Request $request, Jdih $jdih): RedirectResponse
    {
        $validated = $request->validate([
            'status_peraturan' => 'required|string|max:255',
            'judul_peraturan'  => 'required|string|max:255',
            'nomor_peraturan'  => 'required|string|max:255',
            'file_path'        => $request->hasFile('file_path') ? 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:51200' : 'nullable',
            'remove_file_path' => 'nullable|boolean',
            'tentang'          => 'nullable|string',
        ], [
            'file_path.file'  => 'Berkas dokumen tidak valid.',
            'file_path.mimes' => 'Format dokumen peraturan harus berupa file PDF atau dokumen terkait.',
            'file_path.max'   => 'Ukuran dokumen maksimal adalah 50MB.',
        ]);

        if ($request->boolean('remove_file_path') || $request->input('remove_file_path') === 'true' || $request->input('remove_file_path') === '1') {
            if ($jdih->file_path) {
                $this->mediaService->deleteMedia($jdih->file_path);
            }
            $validated['file_path'] = null;
        } elseif ($request->hasFile('file_path')) {
            if ($jdih->file_path) {
                $this->mediaService->deleteMedia($jdih->file_path);
            }
            $validated['file_path'] = $this->mediaService->storeDocument(
                $request->file('file_path'),
                'website/jdih',
                $validated['nomor_peraturan'] . '-' . $validated['judul_peraturan']
            );
        } elseif ($request->filled('file_path') && is_string($request->input('file_path'))) {
            $validated['file_path'] = $this->mediaService->cleanPath($request->input('file_path'));
        } elseif (! $request->filled('file_path') && ! $request->hasFile('file_path')) {
            unset($validated['file_path']);
        }

        unset($validated['remove_file_path']);
        $jdih->update($validated);

        return back()->with('success', 'Peraturan berhasil diperbarui.');
    }

    public function destroy(Jdih $jdih): RedirectResponse
    {
        if ($jdih->file_path) {
            $this->mediaService->deleteMedia($jdih->file_path);
        }
        $jdih->delete();

        return back()->with('success', 'Peraturan berhasil dihapus.');
    }
}
