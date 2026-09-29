<?php

namespace Modules\Sosmedhub\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sosmedhub\Models\Comment;
use Modules\Sosmedhub\Models\Content;
use Modules\Sosmedhub\Models\Platform;
use Modules\Sosmedhub\Models\Revision;
use Modules\Sosmedhub\Models\SocialSetting;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SosmedhubController extends Controller
{
    /**
     * Daftar status konten dalam alur kerja 3 bagian (Planner -> Editor -> Admin Platform)
     */
    public const STATUS_OPTIONS = [
        'Draft',
        'Proses Editing',
        'Revisi',
        'Menunggu Review',
        'Tayang',
    ];

    /**
     * Jenis konten media sosial balai
     */
    public const JENIS_KONTEN_OPTIONS = [
        'Video Reel / Shorts',
        'Carousel / Feed Foto',
        'TikTok Video',
        'YouTube Video',
        'Flyer / Poster Edukasi',
        'Story / Sorotan',
        'Live Streaming',
        'Infografis Pelatihan',
    ];

    public function index(Request $request): Response
    {
        $allowedTabs = ['kalender', 'daftar', 'statistik_tim', 'statistik_medsos', 'pengaturan'];
        $rawTab = $request->query('tab', 'kalender');
        // Backward compatibility mapping for old links
        if ($rawTab === 'pipeline') $rawTab = 'kalender';
        if ($rawTab === 'planner') {
            $rawTab = 'daftar';
            $request->merge(['stage' => 'planner']);
        }
        if ($rawTab === 'editor') {
            $rawTab = 'daftar';
            $request->merge(['stage' => 'editor']);
        }
        if ($rawTab === 'platforms') $rawTab = 'pengaturan';

        $tab = in_array($rawTab, $allowedTabs, true) ? $rawTab : 'kalender';

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        if ($month < 1 || $month > 12) $month = now()->month;
        if ($year < 2020 || $year > 2035) $year = now()->year;

        $search = $request->query('search');
        $status = $request->query('status');
        $stage = $request->query('stage'); // planner | editor | admin
        $platformId = $request->query('platform_id');
        $jenisKonten = $request->query('jenis_konten');
        $sortBy = $request->query('sort_by', 'tanggal_kegiatan');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 10;

        // 1. DATA KALENDER (Aktivitas kegiatan bulanan)
        $startCalendarRange = Carbon::createFromDate($year, $month, 1)->startOfMonth()->subDays(7)->toDateString();
        $endCalendarRange = Carbon::createFromDate($year, $month, 1)->endOfMonth()->addDays(14)->toDateString();

        $calendarContents = Content::with([
            'platforms',
            'planner:id,name,email,role',
            'editor:id,name,email,role',
            'admin:id,name,email,role',
            'instruktur:id,name,email,role',
            'revisions.user:id,name',
            'comments.user:id,name',
        ])
            ->where(function ($q) use ($startCalendarRange, $endCalendarRange) {
                $q->whereBetween('tanggal_kegiatan', [$startCalendarRange, $endCalendarRange])
                    ->orWhereBetween('tanggal_posting', [$startCalendarRange, $endCalendarRange]);
            })
            ->when($platformId, fn($q, $pid) => $q->whereHas('platforms', fn($pq) => $pq->where('platforms.id', $pid)))
            ->when($jenisKonten, fn($q, $jk) => $q->where('jenis_konten', $jk))
            ->get();

        // 2. DATA DAFTAR KONTEN (Tabel dengan pagination & filter)
        $listQuery = Content::with([
            'platforms',
            'planner:id,name,email,role',
            'editor:id,name,email,role',
            'admin:id,name,email,role',
            'instruktur:id,name,email,role',
            'revisions.user:id,name',
            'comments.user:id,name',
        ])
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_kegiatan', 'like', "%{$s}%")
                        ->orWhere('brief', 'like', "%{$s}%")
                        ->orWhere('caption', 'like', "%{$s}%");
                });
            })
            ->when($status, fn($q, $st) => $q->where('status', $st))
            ->when($stage === 'planner', fn($q) => $q->where('status', 'Draft'))
            ->when($stage === 'editor', fn($q) => $q->whereIn('status', ['Proses Editing', 'Revisi']))
            ->when($stage === 'admin', fn($q) => $q->whereIn('status', ['Menunggu Review', 'Tayang']))
            ->when($platformId, fn($q, $pid) => $q->whereHas('platforms', fn($pq) => $pq->where('platforms.id', $pid)))
            ->when($jenisKonten, fn($q, $jk) => $q->where('jenis_konten', $jk));

        $allowedSort = ['id', 'nama_kegiatan', 'tanggal_kegiatan', 'tanggal_posting', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSort, true)) {
            $listQuery->orderBy($sortBy, $sortDir);
        } else {
            $listQuery->latest('tanggal_kegiatan');
        }

        $contents = $listQuery->paginate($perPage)->withQueryString();

        // 3. STATISTIK KONTEN UMUM
        $stats = [
            'total'           => Content::count(),
            'draft'           => Content::where('status', 'Draft')->count(),
            'proses_editing'  => Content::where('status', 'Proses Editing')->count(),
            'revisi'          => Content::where('status', 'Revisi')->count(),
            'menunggu_review' => Content::where('status', 'Menunggu Review')->count(),
            'tayang'          => Content::where('status', 'Tayang')->count(),
        ];

        // 4. USERS & TIM
        $allUsers = User::where('is_active', true)
            ->select('id', 'name', 'email', 'role', 'room_or_desk')
            ->get();

        $planners = $allUsers->filter(fn($u) => $u->hasRole(['super_admin', 'admin', 'medsos_planner']))->values();
        $editors = $allUsers->filter(fn($u) => $u->hasRole(['super_admin', 'admin', 'medsos_editor']))->values();
        $instrukturs = $allUsers->filter(fn($u) => $u->hasRole(['super_admin', 'admin', 'medsos_instruktur']))->values();
        $admins = $allUsers->filter(fn($u) => $u->hasRole(['super_admin', 'admin', 'medsos_admin_platform']))->values();

        // 5. STATISTIK TIM (Evaluasi kontribusi & beban kerja per peran)
        $allContentsCollection = Content::all(['id', 'planner_id', 'editor_id', 'admin_id', 'instruktur_id', 'status']);

        $plannerStats = $allUsers->map(function ($u) use ($allContentsCollection) {
            $userItems = $allContentsCollection->where('planner_id', $u->id);
            return [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'total'   => $userItems->count(),
                'draft'   => $userItems->where('status', 'Draft')->count(),
                'aktif'   => $userItems->whereIn('status', ['Proses Editing', 'Revisi', 'Menunggu Review'])->count(),
                'tayang'  => $userItems->where('status', 'Tayang')->count(),
            ];
        })->filter(fn($item) => $item['total'] > 0)->values();

        $editorStats = $allUsers->map(function ($u) use ($allContentsCollection) {
            $userItems = $allContentsCollection->where('editor_id', $u->id);
            return [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'total'   => $userItems->count(),
                'editing' => $userItems->where('status', 'Proses Editing')->count(),
                'revisi'  => $userItems->where('status', 'Revisi')->count(),
                'review'  => $userItems->where('status', 'Menunggu Review')->count(),
                'selesai' => $userItems->where('status', 'Tayang')->count(),
            ];
        })->filter(fn($item) => $item['total'] > 0)->values();

        $adminStats = $allUsers->map(function ($u) use ($allContentsCollection) {
            $userItems = $allContentsCollection->where('admin_id', $u->id);
            return [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'total'   => $userItems->count(),
                'review'  => $userItems->where('status', 'Menunggu Review')->count(),
                'tayang'  => $userItems->where('status', 'Tayang')->count(),
            ];
        })->filter(fn($item) => $item['total'] > 0)->values();

        $instrukturStats = $allUsers->map(function ($u) use ($allContentsCollection) {
            $userItems = $allContentsCollection->where('instruktur_id', $u->id);
            return [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'total'   => $userItems->count(),
                'tayang'  => $userItems->where('status', 'Tayang')->count(),
            ];
        })->filter(fn($item) => $item['total'] > 0)->values();

        $recentRevisions = Revision::with(['user:id,name', 'content:id,nama_kegiatan,status'])
            ->latest()
            ->take(10)
            ->get();

        $teamStats = [
            'total_konten'      => $stats['total'],
            'dalam_proses'      => $stats['draft'] + $stats['proses_editing'] + $stats['revisi'],
            'siap_tayang'       => $stats['menunggu_review'],
            'sudah_tayang'      => $stats['tayang'],
            'total_revisi'      => Revision::count(),
            'total_komentar'    => Comment::count(),
            'planners'          => $plannerStats,
            'editors'           => $editorStats,
            'admins'            => $adminStats,
            'instrukturs'       => $instrukturStats,
            'recent_revisions'  => $recentRevisions,
        ];

        // 6. STATISTIK MEDSOS (Breakdown platform & format konten)
        $platformsWithCounts = Platform::withCount([
            'contents',
            'contents as tayang_count' => fn($q) => $q->where('status', 'Tayang'),
            'contents as proses_count' => fn($q) => $q->whereIn('status', ['Draft', 'Proses Editing', 'Revisi', 'Menunggu Review']),
        ])->get();

        $formatStats = Content::select('jenis_konten', DB::raw('count(*) as total'))
            ->whereNotNull('jenis_konten')
            ->groupBy('jenis_konten')
            ->orderByDesc('total')
            ->get();

        $statusFunnel = [
            'Draft'           => Content::where('status', 'Draft')->count(),
            'Proses Editing'  => Content::where('status', 'Proses Editing')->count(),
            'Revisi'          => Content::where('status', 'Revisi')->count(),
            'Menunggu Review' => Content::where('status', 'Menunggu Review')->count(),
            'Tayang'          => Content::where('status', 'Tayang')->count(),
        ];

        $medsosStats = [
            'platforms'     => $platformsWithCounts,
            'formats'       => $formatStats,
            'status_funnel' => $statusFunnel,
        ];

        // 7. TIGA STATUS INFORMASI KERJA (Planner, Editor, Admin Platform) & DEADLINE
        $upcomingLimit = Carbon::today()->addDays(3);

        // Planner
        $plannerItems = Content::with(['planner:id,name', 'editor:id,name', 'instruktur:id,name'])
            ->where('status', 'Draft')
            ->orderBy('tanggal_kegiatan', 'asc')
            ->get();
        $plannerDeadlines = $plannerItems->filter(function ($c) use ($upcomingLimit) {
            $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : null;
            return $target && $target->lte($upcomingLimit);
        })->values();

        $plannerInfo = [
            'total'          => $plannerItems->count(),
            'has_work'       => $plannerItems->count() > 0,
            'draft'          => $plannerItems->count(),
            'unassigned'     => $plannerItems->whereNull('planner_id')->count(),
            'need_media'     => $plannerItems->filter(fn($c) => empty($c->link_media_mentah))->count(),
            'deadline_count' => $plannerDeadlines->count(),
            'deadlines'      => $plannerDeadlines->take(3)->map(function ($c) {
                $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : null;
                return [
                    'id'               => $c->id,
                    'nama_kegiatan'    => $c->nama_kegiatan,
                    'tanggal_kegiatan' => $c->tanggal_kegiatan?->format('Y-m-d'),
                    'status'           => $c->status,
                    'planner_name'     => $c->planner?->name ?? $c->instruktur?->name ?? 'Belum Ditugaskan',
                    'has_media'        => !empty($c->link_media_mentah),
                    'is_urgent'        => $target ? ($target->isToday() || $target->isPast()) : false,
                    'is_past'          => $target ? ($target->isPast() && !$target->isToday()) : false,
                    'target_date'      => $target?->format('Y-m-d'),
                ];
            }),
        ];

        // Editor
        $editorItems = Content::with(['planner:id,name', 'editor:id,name'])
            ->whereIn('status', ['Proses Editing', 'Revisi'])
            ->orderBy('tanggal_kegiatan', 'asc')
            ->get();
        $editorDeadlines = $editorItems->filter(function ($c) use ($upcomingLimit) {
            $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : null;
            return $target && $target->lte($upcomingLimit);
        })->values();

        $editorInfo = [
            'total'          => $editorItems->count(),
            'has_work'       => $editorItems->count() > 0,
            'proses_editing' => $editorItems->where('status', 'Proses Editing')->count(),
            'revisi'         => $editorItems->where('status', 'Revisi')->count(),
            'need_result'    => $editorItems->filter(fn($c) => empty($c->link_hasil_edit))->count(),
            'deadline_count' => $editorDeadlines->count(),
            'deadlines'      => $editorDeadlines->take(3)->map(function ($c) {
                $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : null;
                return [
                    'id'               => $c->id,
                    'nama_kegiatan'    => $c->nama_kegiatan,
                    'tanggal_kegiatan' => $c->tanggal_kegiatan?->format('Y-m-d'),
                    'status'           => $c->status,
                    'editor_name'      => $c->editor?->name ?? 'Belum Ditugaskan',
                    'has_result'       => !empty($c->link_hasil_edit),
                    'is_urgent'        => $target ? ($target->isToday() || $target->isPast()) : false,
                    'is_past'          => $target ? ($target->isPast() && !$target->isToday()) : false,
                    'target_date'      => $target?->format('Y-m-d'),
                ];
            }),
        ];

        // Admin Platform
        $adminItems = Content::with(['admin:id,name', 'editor:id,name', 'platforms'])
            ->whereIn('status', ['Menunggu Review', 'Tayang'])
            ->orderBy('tanggal_posting', 'desc')
            ->get();
        $adminPending = $adminItems->where('status', 'Menunggu Review');
        $adminDeadlines = $adminPending->filter(function ($c) use ($upcomingLimit) {
            $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : ($c->tanggal_posting ? Carbon::parse($c->tanggal_posting) : null);
            return $target && $target->lte($upcomingLimit);
        })->values();

        $adminInfo = [
            'total'          => $adminPending->count(),
            'has_work'       => $adminPending->count() > 0,
            'siap_tayang'    => $adminPending->count(),
            'waiting_link'   => $adminPending->filter(fn($c) => empty($c->link_postingan))->count(),
            'tayang'         => $adminItems->where('status', 'Tayang')->count(),
            'deadline_count' => $adminDeadlines->count(),
            'deadlines'      => $adminDeadlines->take(3)->map(function ($c) {
                $target = $c->tanggal_kegiatan ? Carbon::parse($c->tanggal_kegiatan) : ($c->tanggal_posting ? Carbon::parse($c->tanggal_posting) : null);
                return [
                    'id'               => $c->id,
                    'nama_kegiatan'    => $c->nama_kegiatan,
                    'tanggal_posting'  => $c->tanggal_posting?->format('Y-m-d'),
                    'tanggal_kegiatan' => $c->tanggal_kegiatan?->format('Y-m-d'),
                    'status'           => $c->status,
                    'admin_name'       => $c->admin?->name ?? 'Belum Ditugaskan',
                    'has_link'         => !empty($c->link_postingan),
                    'is_urgent'        => $target ? ($target->isToday() || $target->isPast()) : false,
                    'is_past'          => $target ? ($target->isPast() && !$target->isToday()) : false,
                    'target_date'      => $target?->format('Y-m-d'),
                ];
            }),
        ];

        $workflowInfo = [
            'planner' => $plannerInfo,
            'editor'  => $editorInfo,
            'admin'   => $adminInfo,
        ];

        // 8. PLATFORMS & SOCIAL SETTINGS (Pengaturan)
        $platforms = Platform::orderBy('id')->get();
        $socialSettings = SocialSetting::all();

        return Inertia::render('Sosmedhub::Index', [
            'activeTab'          => $tab,
            'currentMonth'       => $month,
            'currentYear'        => $year,
            'calendarContents'   => $calendarContents,
            'contents'           => $contents,
            'stats'              => $stats,
            'workflowInfo'       => $workflowInfo,
            'teamStats'          => $teamStats,
            'medsosStats'        => $medsosStats,
            'platforms'          => $platforms,
            'socialSettings'     => $socialSettings,
            'statusOptions'      => self::STATUS_OPTIONS,
            'jenisKontenOptions' => self::JENIS_KONTEN_OPTIONS,
            'team'               => [
                'all'         => $allUsers,
                'planners'    => $planners->isNotEmpty() ? $planners : $allUsers,
                'editors'     => $editors->isNotEmpty() ? $editors : $allUsers,
                'instrukturs' => $instrukturs->isNotEmpty() ? $instrukturs : $allUsers,
                'admins'      => $admins->isNotEmpty() ? $admins : $allUsers,
            ],
            'filters'            => [
                'tab'          => $tab,
                'month'        => $month,
                'year'         => $year,
                'search'       => $search ?? '',
                'status'       => $status ?? '',
                'stage'        => $stage ?? '',
                'platform_id'  => $platformId ?? '',
                'jenis_konten' => $jenisKonten ?? '',
                'sort_by'      => $sortBy,
                'sort_dir'     => $sortDir,
                'per_page'     => $perPage,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kegiatan'     => 'required|string|max:255',
            'jenis_konten'      => 'required|string|max:100',
            'tanggal_kegiatan'  => 'required|date',
            'rencana_tayang'    => 'nullable|date',
            'tanggal_posting'   => 'nullable|date',
            'planner_id'        => 'nullable|exists:users,id',
            'editor_id'         => 'nullable|exists:users,id',
            'instruktur_id'     => 'nullable|exists:users,id',
            'admin_id'          => 'nullable|exists:users,id',
            'pegawai_id'        => 'nullable|exists:users,id',
            'brief'             => 'nullable|string',
            'caption'           => 'nullable|string',
            'link_referensi'    => 'nullable|string|max:255',
            'link_media_mentah' => 'nullable|string',
            'link_hasil_edit'   => 'nullable|string',
            'link_postingan'    => 'nullable|string|max:255',
            'status'            => 'required|string|max:50',
            'tipe_konten'       => 'nullable|string|in:bahan,final',
            'platform_ids'      => 'nullable|array',
            'platform_ids.*'    => 'exists:sosmedhub_platforms,id',
        ]);

        $userId = $request->user()?->id;
        $tipe = $request->input('tipe_konten', 'final');

        // Otomatisasi Perekaman PIC:
        if ($tipe === 'bahan') {
            // Instruktur atau Pegawai yang mengusulkan ide & menyerahkan bahan mentah awal
            $validated['instruktur_id'] = $userId;
            $validated['planner_id'] = null;
        } else {
            // Admin Planner yang langsung merancang konsep final
            $validated['planner_id'] = $userId;
        }

        // Jika langsung dikirim ke editor saat dibuat
        if ($validated['status'] === 'Proses Editing') {
            $validated['planner_id'] = $userId;
        }

        // Skenario C: 1 Tanggal Utama
        if (empty($validated['rencana_tayang'])) {
            $validated['rencana_tayang'] = $validated['tanggal_kegiatan'];
        }

        $platformIds = $validated['platform_ids'] ?? [];
        unset($validated['platform_ids'], $validated['tipe_konten']);

        $content = Content::create($validated);

        if (!empty($platformIds)) {
            $content->platforms()->sync($platformIds);
        }

        return back()->with('success', $tipe === 'bahan' ? 'Pengajuan bahan kegiatan berhasil disimpan.' : 'Rencana konten berhasil dibuat.');
    }

    public function update(Request $request, Content $content): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kegiatan'     => 'required|string|max:255',
            'jenis_konten'      => 'required|string|max:100',
            'tanggal_kegiatan'  => 'required|date',
            'rencana_tayang'    => 'nullable|date',
            'tanggal_posting'   => 'nullable|date',
            'planner_id'        => 'nullable|exists:users,id',
            'editor_id'         => 'nullable|exists:users,id',
            'instruktur_id'     => 'nullable|exists:users,id',
            'admin_id'          => 'nullable|exists:users,id',
            'pegawai_id'        => 'nullable|exists:users,id',
            'brief'             => 'nullable|string',
            'caption'           => 'nullable|string',
            'link_referensi'    => 'nullable|string|max:255',
            'link_media_mentah' => 'nullable|string',
            'link_hasil_edit'   => 'nullable|string',
            'link_postingan'    => 'nullable|string|max:255',
            'status'            => 'required|string|max:50',
            'platform_ids'      => 'nullable|array',
            'platform_ids.*'    => 'exists:sosmedhub_platforms,id',
        ]);

        $userId = $request->user()?->id;
        $newStatus = $validated['status'];

        // Otomatisasi Perekaman PIC saat next step atau perubahan status:
        // 1. Planner Action: Melengkapi brief & mengirim ke editor
        if ($newStatus === 'Proses Editing') {
            $validated['planner_id'] = $userId;
        }

        // 2. Editor Action: Mengerjakan editing, upload hasil edit, atau kirim ke admin platform
        if (in_array($newStatus, ['Menunggu Review', 'Revisi']) || (!empty($validated['link_hasil_edit']) && empty($content->editor_id))) {
            $validated['editor_id'] = $userId;
        }

        // 3. Admin Platform Action: Menyelesaikan konten atau menempelkan tautan link publikasi
        if ($newStatus === 'Tayang' || !empty($validated['link_postingan'])) {
            $validated['admin_id'] = $userId;
            if ($newStatus === 'Tayang' && empty($validated['tanggal_posting'])) {
                $validated['tanggal_posting'] = now()->toDateString();
            }
        }

        // Skenario C: 1 Tanggal Utama
        if (empty($validated['rencana_tayang'])) {
            $validated['rencana_tayang'] = $validated['tanggal_kegiatan'];
        }

        $platformIds = $validated['platform_ids'] ?? [];
        unset($validated['platform_ids']);

        $content->update($validated);
        $content->platforms()->sync($platformIds);

        return back()->with('success', 'Konten berhasil diperbarui.');
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
            'type' => 'required|in:mentah,hasil',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $folder = $type === 'mentah' ? 'sosmedhub/mentah' : 'sosmedhub/hasil';

        $path = $file->store($folder, 'public');
        $url = asset('storage/' . $path);

        return response()->json([
            'success'   => true,
            'path'      => $path,
            'url'       => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);
    }

    public function updateStatus(Request $request, Content $content): RedirectResponse
    {
        $validated = $request->validate([
            'status'          => 'required|string|max:50',
            'link_postingan'  => 'nullable|url|max:255',
            'tanggal_posting' => 'nullable|date',
            'rencana_tayang'  => 'nullable|date',
        ]);

        $userId = $request->user()?->id;
        $updateData = ['status' => $validated['status']];

        if (!empty($validated['link_postingan'])) {
            $updateData['link_postingan'] = $validated['link_postingan'];
        }
        if (!empty($validated['rencana_tayang'])) {
            $updateData['rencana_tayang'] = $validated['rencana_tayang'];
        }
        if (!empty($validated['tanggal_posting'])) {
            $updateData['tanggal_posting'] = $validated['tanggal_posting'];
        } elseif ($validated['status'] === 'Tayang' && empty($content->tanggal_posting)) {
            $updateData['tanggal_posting'] = now()->toDateString();
        }

        // Perekaman PIC Otomatis pada updateStatus:
        if ($validated['status'] === 'Proses Editing') {
            $updateData['planner_id'] = $userId;
        }
        if (in_array($validated['status'], ['Menunggu Review', 'Revisi'])) {
            $updateData['editor_id'] = $userId;
        }
        if ($validated['status'] === 'Tayang' || !empty($validated['link_postingan'])) {
            $updateData['admin_id'] = $userId;
        }

        $content->update($updateData);

        return back()->with('success', "Status konten diubah menjadi {$validated['status']}.");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,status_draft,status_proses_editing,status_revisi,status_review,status_tayang',
            'ids'    => 'required|array',
            'ids.*'  => 'exists:sosmedhub_contents,id',
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'delete') {
            DB::table('sosmedhub_content_platform')->whereIn('content_id', $ids)->delete();
            DB::table('sosmedhub_revisions')->whereIn('content_id', $ids)->delete();
            DB::table('sosmedhub_comments')->whereIn('content_id', $ids)->delete();
            Content::whereIn('id', $ids)->delete();
            return back()->with('success', count($ids) . ' konten berhasil dihapus.');
        }

        $statusMap = [
            'status_draft'          => 'Draft',
            'status_proses_editing' => 'Proses Editing',
            'status_revisi'         => 'Revisi',
            'status_review'         => 'Menunggu Review',
            'status_tayang'         => 'Tayang',
        ];

        if (isset($statusMap[$action])) {
            $targetStatus = $statusMap[$action];
            $update = ['status' => $targetStatus];
            if ($targetStatus === 'Tayang') {
                $update['tanggal_posting'] = now()->toDateString();
            }
            Content::whereIn('id', $ids)->update($update);
            return back()->with('success', 'Status ' . count($ids) . " konten diubah ke {$targetStatus}.");
        }

        return back();
    }

    public function destroy(Content $content): RedirectResponse
    {
        $content->platforms()->detach();
        $content->revisions()->delete();
        $content->comments()->delete();
        $content->delete();

        return back()->with('success', 'Konten berhasil dihapus.');
    }

    public function addRevision(Request $request, Content $content): RedirectResponse
    {
        $validated = $request->validate([
            'target_revisi' => 'required|string|max:100',
            'catatan'       => 'required|string',
        ]);

        $content->revisions()->create([
            'user_id'       => auth()->id(),
            'target_revisi' => $validated['target_revisi'],
            'catatan'       => $validated['catatan'],
        ]);

        // Otomatis ubah status konten ke Revisi
        $content->update(['status' => 'Revisi']);

        return back()->with('success', 'Catatan revisi berhasil ditambahkan ke konten.');
    }

    public function addComment(Request $request, Content $content): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $content->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $validated['body'],
        ]);

        return back()->with('success', 'Komentar tim berhasil dikirim.');
    }

    public function togglePlatform(Platform $platform): RedirectResponse
    {
        $platform->update(['is_active' => !$platform->is_active]);

        return back()->with('success', "Platform {$platform->name} " . ($platform->is_active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function saveSocialSetting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_name' => 'required|string|max:100',
            'app_id'        => 'nullable|string|max:100',
            'app_secret'    => 'nullable|string|max:255',
            'page_id'       => 'nullable|string|max:100',
            'access_token'  => 'nullable|string',
            'ig_user_id'    => 'nullable|string|max:100',
        ]);

        SocialSetting::updateOrCreate(
            ['provider_name' => $validated['provider_name']],
            [
                'app_id'       => $validated['app_id'] ?? null,
                'app_secret'   => $validated['app_secret'] ?? null,
                'page_id'      => $validated['page_id'] ?? null,
                'access_token' => $validated['access_token'] ?? null,
                'ig_user_id'   => $validated['ig_user_id'] ?? null,
            ]
        );

        return back()->with('success', 'Pengaturan Kredensial Meta Graph API berhasil disimpan.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $contents = Content::with(['platforms', 'planner', 'editor', 'admin', 'instruktur'])
            ->latest('tanggal_kegiatan')
            ->get();

        $filename = 'daftar-konten-sosmedhub-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($contents) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Nama Kegiatan / Konten',
                'Jenis Konten',
                'Status',
                'Platform Target',
                'Tanggal Kegiatan',
                'Tanggal Posting',
                'Planner',
                'Editor',
                'Admin Platform',
                'Instruktur / Narasumber',
                'Link Bahan Mentah',
                'Link Hasil Edit',
                'Link Postingan Live',
                'Brief',
                'Caption',
            ]);

            foreach ($contents as $c) {
                $platformNames = $c->platforms->pluck('name')->implode(', ');
                fputcsv($handle, [
                    $c->id,
                    $c->nama_kegiatan,
                    $c->jenis_konten,
                    $c->status,
                    $platformNames,
                    $c->tanggal_kegiatan ? $c->tanggal_kegiatan->format('Y-m-d') : '',
                    $c->tanggal_posting ? $c->tanggal_posting->format('Y-m-d') : '',
                    $c->planner?->name ?? '',
                    $c->editor?->name ?? '',
                    $c->admin?->name ?? '',
                    $c->instruktur?->name ?? '',
                    $c->link_media_mentah ?? '',
                    $c->link_hasil_edit ?? '',
                    $c->link_postingan ?? '',
                    $c->brief ?? '',
                    $c->caption ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
