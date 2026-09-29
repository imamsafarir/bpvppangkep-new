<?php

namespace Modules\Shortlink\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shortlink\Models\Shortlink;
use Modules\Shortlink\Models\ShortlinkLead;
use Modules\Shortlink\Models\ShortlinkSetting;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicShortlinkController extends Controller
{
    public function redirect(Request $request, string $code): Response|RedirectResponse
    {
        $shortlink = Shortlink::where('code', $code)->first();

        if (! $shortlink) {
            abort(404, 'Tautan shortlink tidak ditemukan.');
        }

        if (! $shortlink->is_active) {
            abort(403, 'Tautan shortlink ini sedang dinonaktifkan.');
        }

        $shortlink->increment('clicks_count');

        if ($shortlink->is_capture_active) {
            return Inertia::render('Shortlink::Capture', [
                'shortlink' => [
                    'id'                 => $shortlink->id,
                    'code'               => $shortlink->code,
                    'pegawai_name'       => $shortlink->pegawai_name,
                    'custom_title'       => $shortlink->custom_title,
                    'custom_description' => $shortlink->custom_description,
                    'custom_button_text' => $shortlink->custom_button_text,
                ],
                'fields' => $shortlink->capture_fields ?: ['nama', 'whatsapp'],
            ]);
        }

        return redirect()->away($shortlink->destination_url);
    }

    public function submitCapture(Request $request, string $code): RedirectResponse
    {
        $shortlink = Shortlink::where('code', $code)->where('is_active', true)->firstOrFail();

        $activeFields = $shortlink->capture_fields ?: ['nama', 'whatsapp'];

        $rules = [];
        if (in_array('nama', $activeFields, true)) {
            $rules['nama'] = 'required|string|max:191';
        }
        if (in_array('whatsapp', $activeFields, true)) {
            $rules['whatsapp'] = 'required|string|max:50';
        }
        if (in_array('email', $activeFields, true)) {
            $rules['email'] = 'required|email|max:191';
        }

        $validated = $request->validate($rules);

        $lead = ShortlinkLead::create([
            'shortlink_id' => $shortlink->id,
            'nama'         => $validated['nama'] ?? null,
            'whatsapp'     => $validated['whatsapp'] ?? null,
            'email'        => $validated['email'] ?? null,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        $webhookUrl = $shortlink->spreadsheet_webhook_url ?: ShortlinkSetting::get('spreadsheet_webhook_url');

        if ($webhookUrl) {
            try {
                Http::timeout(5)->post($webhookUrl, [
                    'event'        => 'new_lead',
                    'shortlink_id' => $shortlink->id,
                    'code'         => $shortlink->code,
                    'pegawai_name' => $shortlink->pegawai_name,
                    'nama'         => $lead->nama,
                    'whatsapp'     => $lead->whatsapp,
                    'email'        => $lead->email,
                    'ip_address'   => $lead->ip_address,
                    'created_at'   => $lead->created_at?->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('shortlink.thankyou', ['code' => $shortlink->code]);
    }

    public function thankyou(string $code): Response
    {
        $shortlink = Shortlink::where('code', $code)->firstOrFail();

        return Inertia::render('Shortlink::Thankyou', [
            'destinationUrl' => $shortlink->destination_url,
        ]);
    }

    public function feedCsv(Request $request): StreamedResponse
    {
        $token = $request->query('token');
        $validToken = ShortlinkSetting::get('spreadsheet_feed_token');

        if (! $validToken || ! hash_equals((string) $validToken, (string) $token)) {
            abort(401, 'Unauthorized feed token.');
        }

        $shortlinkCode = $request->query('code');

        $query = ShortlinkLead::with('shortlink:id,code,pegawai_name')
            ->when($shortlinkCode, function ($q, $c) {
                $q->whereHas('shortlink', function ($sub) use ($c) {
                    $sub->where('code', $c);
                });
            })
            ->latest();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="shortlink-feed.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Kode Shortlink',
                'Nama Pegawai',
                'Nama Lengkap',
                'Nomor WhatsApp',
                'Email',
                'IP Address',
                'Perangkat',
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
                        $lead->created_at?->format('Y-m-d H:i:s') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function feedJson(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $validToken = ShortlinkSetting::get('spreadsheet_feed_token');

        if (! $validToken || ! hash_equals((string) $validToken, (string) $token)) {
            return response()->json(['error' => 'Unauthorized feed token.'], 401);
        }

        $leads = ShortlinkLead::with('shortlink:id,code,pegawai_name')
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($lead) {
                return [
                    'id'           => $lead->id,
                    'code'         => $lead->shortlink?->code,
                    'pegawai_name' => $lead->shortlink?->pegawai_name,
                    'nama'         => $lead->nama,
                    'whatsapp'     => $lead->whatsapp,
                    'email'        => $lead->email,
                    'ip_address'   => $lead->ip_address,
                    'user_agent'   => $lead->user_agent,
                    'created_at'   => $lead->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'count'  => $leads->count(),
            'data'   => $leads,
        ]);
    }

    public function shortlinksFeedCsv(Request $request): StreamedResponse
    {
        $token = $request->query('token');
        $validToken = ShortlinkSetting::get('spreadsheet_feed_token');

        if (! $validToken || ! hash_equals((string) $validToken, (string) $token)) {
            abort(401, 'Unauthorized feed token.');
        }

        $query = Shortlink::withCount('leads')->latest();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="shortlinks-feed.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Kode Shortlink',
                'Tautan Lengkap',
                'Nama Pegawai',
                'URL Tujuan',
                'Total Klik',
                'Total Leads',
                'Form Capture',
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
                        $sl->is_active ? 'Aktif' : 'Nonaktif',
                        $sl->created_at?->format('Y-m-d H:i:s') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function shortlinksFeedJson(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $validToken = ShortlinkSetting::get('spreadsheet_feed_token');

        if (! $validToken || ! hash_equals((string) $validToken, (string) $token)) {
            return response()->json(['error' => 'Unauthorized feed token.'], 401);
        }

        $shortlinks = Shortlink::withCount('leads')
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($sl) {
                return [
                    'id'                => $sl->id,
                    'code'              => $sl->code,
                    'short_url'         => url('/s/' . $sl->code),
                    'pegawai_name'      => $sl->pegawai_name,
                    'destination_url'   => $sl->destination_url,
                    'clicks_count'      => $sl->clicks_count,
                    'leads_count'       => $sl->leads_count,
                    'is_capture_active' => (bool) $sl->is_capture_active,
                    'is_active'         => (bool) $sl->is_active,
                    'created_at'        => $sl->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'count'  => $shortlinks->count(),
            'data'   => $shortlinks,
        ]);
    }
}
