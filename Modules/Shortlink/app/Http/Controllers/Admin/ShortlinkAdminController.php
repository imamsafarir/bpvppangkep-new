<?php

namespace Modules\Shortlink\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shortlink\Models\Shortlink;
use Modules\Shortlink\Models\ShortlinkLead;
use Modules\Shortlink\Models\ShortlinkSetting;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortlinkAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $currentTab = $request->query('tab', 'shortlinks');

        // Stats
        $stats = [
            'total_shortlinks'  => Shortlink::count(),
            'total_clicks'      => (int) Shortlink::sum('clicks_count'),
            'total_leads'       => ShortlinkLead::count(),
            'active_shortlinks' => Shortlink::where('is_active', true)->count(),
        ];

        // Shortlinks filtering & sorting
        $search = $request->query('search');
        $status = $request->query('status');
        $capture = $request->query('capture');
        $sortBy = $request->query('sort_by');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $shortlinksQuery = Shortlink::with('createdBy:id,name')
            ->withCount('leads')
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('pegawai_name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%")
                        ->orWhere('destination_url', 'like', "%{$s}%")
                        ->orWhere('custom_title', 'like', "%{$s}%");
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('is_active', (bool) $status);
            })
            ->when($capture !== null && $capture !== '', function ($q) use ($capture) {
                $q->where('is_capture_active', (bool) $capture);
            });

        $allowedColumns = ['id', 'code', 'pegawai_name', 'destination_url', 'clicks_count', 'leads_count', 'is_active', 'created_at'];
        if ($sortBy && in_array($sortBy, $allowedColumns, true)) {
            $shortlinksQuery->orderBy($sortBy, $sortDir);
        } else {
            $shortlinksQuery->latest();
        }

        $shortlinks = $shortlinksQuery->paginate(10, ['*'], 'shortlinks_page')->withQueryString();

        // Leads filtering
        $leadSearch = $request->query('lead_search');
        $leadShortlinkId = $request->query('lead_shortlink_id');

        $leadsQuery = ShortlinkLead::with('shortlink:id,code,pegawai_name')
            ->when($leadSearch, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama', 'like', "%{$s}%")
                        ->orWhere('whatsapp', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('ip_address', 'like', "%{$s}%");
                });
            })
            ->when($leadShortlinkId, function ($q, $id) {
                $q->where('shortlink_id', $id);
            })
            ->latest();

        $leads = $leadsQuery->paginate(15, ['*'], 'leads_page')->withQueryString();

        // Settings & Integration
        $feedToken = ShortlinkSetting::get('spreadsheet_feed_token') ?: 'shortlink_' . Str::random(24);
        if (! ShortlinkSetting::get('spreadsheet_feed_token')) {
            ShortlinkSetting::set('spreadsheet_feed_token', $feedToken);
        }

        $allShortlinks = Shortlink::select('id', 'code', 'pegawai_name')->orderBy('code')->get();

        return Inertia::render('Shortlink::Admin/Index', [
            'stats'         => $stats,
            'currentTab'    => $currentTab,
            'shortlinks'    => $shortlinks,
            'leads'         => $leads,
            'allShortlinks' => $allShortlinks,
            'filters'       => [
                'tab'               => $currentTab,
                'search'            => $search ?? '',
                'status'            => $status ?? '',
                'capture'           => $capture ?? '',
                'sort_by'           => $sortBy ?? '',
                'sort_dir'          => $sortDir,
                'lead_search'       => $leadSearch ?? '',
                'lead_shortlink_id' => $leadShortlinkId ?? '',
            ],
            'settings' => [
                'spreadsheet_feed_token'   => $feedToken,
                'spreadsheet_webhook_url'  => ShortlinkSetting::get('spreadsheet_webhook_url') ?? '',
                'feed_csv_url'             => url('/api/shortlink/feed.csv?token=' . $feedToken),
                'feed_json_url'            => url('/api/shortlink/feed.json?token=' . $feedToken),
                'shortlinks_feed_csv_url'  => url('/api/shortlink/shortlinks.csv?token=' . $feedToken),
                'shortlinks_feed_json_url' => url('/api/shortlink/shortlinks.json?token=' . $feedToken),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pegawai_name'            => 'required|string|max:191',
            'code'                    => 'nullable|string|max:20|unique:shortlink_links,code',
            'destination_url'         => 'required|url',
            'is_capture_active'       => 'boolean',
            'capture_fields'          => 'nullable|array',
            'custom_title'            => 'nullable|string|max:191',
            'custom_description'      => 'nullable|string',
            'custom_button_text'      => 'nullable|string|max:191',
            'spreadsheet_webhook_url' => 'nullable|url',
            'is_active'               => 'boolean',
        ]);

        if (empty($validated['code'])) {
            do {
                $code = Str::random(5);
            } while (Shortlink::where('code', $code)->exists());
            $validated['code'] = $code;
        }

        if (empty($validated['capture_fields'])) {
            $validated['capture_fields'] = ['nama', 'whatsapp'];
        }

        $validated['created_by'] = auth()->id();

        Shortlink::create($validated);

        return back()->with('success', 'Shortlink berhasil dibuat.');
    }

    public function update(Request $request, Shortlink $shortlink): RedirectResponse
    {
        $validated = $request->validate([
            'pegawai_name'            => 'required|string|max:191',
            'code'                    => 'required|string|max:20|unique:shortlink_links,code,' . $shortlink->id,
            'destination_url'         => 'required|url',
            'is_capture_active'       => 'boolean',
            'capture_fields'          => 'nullable|array',
            'custom_title'            => 'nullable|string|max:191',
            'custom_description'      => 'nullable|string',
            'custom_button_text'      => 'nullable|string|max:191',
            'spreadsheet_webhook_url' => 'nullable|url',
            'is_active'               => 'boolean',
        ]);

        if (empty($validated['capture_fields'])) {
            $validated['capture_fields'] = ['nama', 'whatsapp'];
        }

        $shortlink->update($validated);

        return back()->with('success', 'Shortlink berhasil diperbarui.');
    }

    public function toggle(Shortlink $shortlink): RedirectResponse
    {
        return $this->toggleStatus($shortlink);
    }

    public function toggleStatus(Shortlink $shortlink): RedirectResponse
    {
        $shortlink->update([
            'is_active' => ! $shortlink->is_active,
        ]);

        return back()->with('success', 'Status shortlink berhasil diubah.');
    }

    public function destroy(Shortlink $shortlink): RedirectResponse
    {
        $shortlink->leads()->delete();
        $shortlink->delete();

        return back()->with('success', 'Shortlink dan data leads terkait berhasil dihapus.');
    }

    public function destroyLead(ShortlinkLead $lead): RedirectResponse
    {
        $lead->delete();

        return back()->with('success', 'Data lead berhasil dihapus.');
    }

    public function regenerateToken(): RedirectResponse
    {
        $newToken = bin2hex(random_bytes(16));
        ShortlinkSetting::set('spreadsheet_feed_token', $newToken);

        return back()->with('success', 'Token sinkronisasi Google Spreadsheet berhasil diperbarui.');
    }

    public function updateWebhook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spreadsheet_webhook_url' => 'nullable|url|max:500',
        ]);

        ShortlinkSetting::set('spreadsheet_webhook_url', $validated['spreadsheet_webhook_url'] ?? '');

        return back()->with('success', 'Pengaturan Webhook Google Sheets berhasil disimpan.');
    }

    public function testWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');

        try {
            $response = Http::timeout(10)->post($url, [
                'event'        => 'test_ping',
                'shortlink_id' => 0,
                'code'         => 'TEST-CODE',
                'pegawai_name' => 'Admin Penguji BPVP Pangkep',
                'nama'         => 'Contoh Nama Pengunjung (Test)',
                'whatsapp'     => '081234567890',
                'email'        => 'test@bpvppangkep.kemnaker.go.id',
                'ip_address'   => $request->ip(),
                'created_at'   => now()->toIso8601String(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tes Webhook berhasil terkirim! Google Apps Script merespons HTTP ' . $response->status() . '. Silakan cek baris data pada Google Spreadsheet Anda.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Google Apps Script merespons dengan kode HTTP ' . $response->status() . '. Pastikan Web App di-deploy dengan opsi "Who has access: Anyone".',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Webhook URL: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function exportLeads(Request $request): StreamedResponse
    {
        $shortlinkId = $request->query('shortlink_id');
        $search = $request->query('search');

        $query = ShortlinkLead::with('shortlink:id,code,pegawai_name')
            ->when($shortlinkId, function ($q, $id) {
                $q->where('shortlink_id', $id);
            })
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama', 'like', "%{$s}%")
                        ->orWhere('whatsapp', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->latest();

        $filename = 'shortlink-leads-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Kode Shortlink',
                'Nama Pegawai',
                'Nama Lengkap',
                'Nomor WhatsApp',
                'Email',
                'IP Address',
                'User Agent / Perangkat',
                'Waktu Input',
            ]);

            $query->chunk(200, function ($leads) use ($handle) {
                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        $lead->id,
                        $lead->shortlink?->code ?? '-',
                        $lead->shortlink?->pegawai_name ?? '-',
                        $lead->nama ?? '-',
                        $lead->whatsapp ?? '-',
                        $lead->email ?? '-',
                        $lead->ip_address ?? '-',
                        $lead->user_agent ?? '-',
                        $lead->created_at?->format('d/m/Y H:i:s') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:shortlink_links,id',
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'delete') {
            ShortlinkLead::whereIn('shortlink_id', $ids)->delete();
            Shortlink::whereIn('id', $ids)->delete();

            return back()->with('success', count($ids) . ' shortlink berhasil dihapus.');
        }

        if ($action === 'activate') {
            Shortlink::whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', count($ids) . ' shortlink berhasil diaktifkan.');
        }

        if ($action === 'deactivate') {
            Shortlink::whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', count($ids) . ' shortlink berhasil dinonaktifkan.');
        }

        return back();
    }

    public function bulkActionLeads(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:shortlink_leads,id',
        ]);

        $ids = $validated['ids'];
        ShortlinkLead::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' leads berhasil dihapus.');
    }

    public function exportShortlinks(Request $request): StreamedResponse
    {
        $ids = $request->query('ids');
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Shortlink::withCount('leads')
            ->when($ids, function ($q, $idsStr) {
                $idList = is_array($idsStr) ? $idsStr : explode(',', $idsStr);
                $q->whereIn('id', $idList);
            })
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('pegawai_name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%")
                        ->orWhere('destination_url', 'like', "%{$s}%");
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('is_active', (bool) $status);
            })
            ->latest();

        $filename = 'daftar-shortlink-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Kode Shortlink',
                'Tautan Pendek',
                'Nama Pegawai',
                'URL Tujuan',
                'Total Klik',
                'Total Leads',
                'Form Capture',
                'Field Diminta',
                'Judul Form',
                'Deskripsi Form',
                'Teks Tombol',
                'Webhook Google Sheets',
                'Status',
                'Tanggal Dibuat',
            ]);

            $query->chunk(200, function ($shortlinks) use ($handle) {
                foreach ($shortlinks as $sl) {
                    fputcsv($handle, [
                        $sl->id,
                        $sl->code,
                        url('/s/' . $sl->code),
                        $sl->pegawai_name,
                        $sl->destination_url,
                        $sl->clicks_count,
                        $sl->leads_count,
                        $sl->is_capture_active ? 'Aktif' : 'Nonaktif',
                        is_array($sl->capture_fields) ? implode(', ', $sl->capture_fields) : 'nama, whatsapp',
                        $sl->custom_title ?? '',
                        $sl->custom_description ?? '',
                        $sl->custom_button_text ?? '',
                        $sl->spreadsheet_webhook_url ?? '',
                        $sl->is_active ? 'Aktif' : 'Nonaktif',
                        $sl->created_at?->format('d/m/Y H:i:s') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'template-import-shortlink.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'pegawai_name',
                'destination_url',
                'code',
                'is_capture_active',
                'capture_fields',
                'custom_title',
                'custom_description',
                'custom_button_text',
                'spreadsheet_webhook_url',
                'is_active',
            ]);

            // Sample row 1
            fputcsv($handle, [
                'Budi Santoso (Instruktur Las)',
                'https://skillhub.kemnaker.go.id/pelatihan/daftar/contoh-link-1',
                'las01',
                '1',
                'nama,whatsapp',
                'Buku Tamu Pelatihan Las',
                'Silakan isi nama dan whatsapp Anda untuk melanjutkan',
                'Lanjut Daftar',
                '',
                '1',
            ]);

            // Sample row 2
            fputcsv($handle, [
                'Unit Humas BPVP',
                'https://bpvppangkep.kemnaker.go.id/berita/contoh',
                '', // Biarkan kosong untuk auto-generate kode acak
                '0',
                '',
                '',
                '',
                '',
                '',
                '1',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    public function importShortlinks(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                fread($handle, 3);
            }
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            $headers = null;
            while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
                if (! $headers) {
                    $headers = array_map(function ($h) {
                        return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
                    }, $data);
                    continue;
                }

                if (count($data) < 2) {
                    continue;
                }

                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = isset($data[$idx]) ? trim($data[$idx]) : '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->withErrors(['file' => 'File CSV kosong atau tidak memiliki data yang valid.']);
        }

        $importedCount = 0;
        foreach ($rows as $row) {
            $destUrl = $row['destination_url'] ?? $row['url_tujuan'] ?? '';
            $pegawai = $row['pegawai_name'] ?? $row['nama_pegawai'] ?? 'Admin';

            if (empty($destUrl) || ! filter_var($destUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            $code = $row['code'] ?? $row['kode'] ?? '';
            if (empty($code)) {
                do {
                    $code = Str::random(5);
                } while (Shortlink::where('code', $code)->exists());
            } else {
                if (Shortlink::where('code', $code)->exists()) {
                    $code .= '_' . Str::random(3);
                }
            }

            $isCapture = isset($row['is_capture_active'])
                ? in_array(strtolower($row['is_capture_active']), ['1', 'true', 'ya', 'yes', 'aktif'], true)
                : false;

            $fieldsStr = $row['capture_fields'] ?? '';
            $fields = ['nama', 'whatsapp'];
            if (! empty($fieldsStr)) {
                $parsed = array_map('trim', explode(',', strtolower($fieldsStr)));
                $fields = array_values(array_intersect($parsed, ['nama', 'whatsapp', 'email']));
                if (empty($fields)) {
                    $fields = ['nama', 'whatsapp'];
                }
            }

            $isActive = isset($row['is_active'])
                ? ! in_array(strtolower($row['is_active']), ['0', 'false', 'tidak', 'no', 'nonaktif'], true)
                : true;

            Shortlink::create([
                'pegawai_name'            => $pegawai,
                'code'                    => $code,
                'destination_url'         => $destUrl,
                'clicks_count'            => 0,
                'is_capture_active'       => $isCapture,
                'capture_fields'          => $fields,
                'custom_title'            => $row['custom_title'] ?? null,
                'custom_description'      => $row['custom_description'] ?? null,
                'custom_button_text'      => $row['custom_button_text'] ?? null,
                'spreadsheet_webhook_url' => ! empty($row['spreadsheet_webhook_url']) && filter_var($row['spreadsheet_webhook_url'], FILTER_VALIDATE_URL) ? $row['spreadsheet_webhook_url'] : null,
                'is_active'               => $isActive,
                'created_by'              => auth()->id(),
            ]);

            $importedCount++;
        }

        return back()->with('success', "Berhasil mengimpor {$importedCount} shortlink dari template Excel/CSV.");
    }
}
