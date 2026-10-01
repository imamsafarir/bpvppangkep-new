<?php

namespace Modules\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Services\QrCodeService;

class CertificateController extends Controller
{
    /**
     * Download or stream official A4 Landscape Certificate PDF.
     */
    public function download(Enrollment $enrollment): Response|RedirectResponse
    {
        if ($enrollment->status !== 'completed' || empty($enrollment->certificate_hash)) {
            return back()->with('error', 'Sertifikat belum dapat diunduh karena Anda belum menyelesaikan kelas atau melakukan presensi.');
        }

        $enrollment->load(['course', 'participant']);
        $course = $enrollment->course;
        $participant = $enrollment->participant;
        $defaultConfig = [
            'font_family' => "'Plus Jakarta Sans', Arial, sans-serif",
            'header_kop' => [
                'show' => true,
                'x' => 50,
                'y' => 9,
                'line1' => 'KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA',
                'line2' => 'BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN',
                'font_size_line1' => 10.5,
                'font_size_line2' => 8.5,
                'color_line1' => '#0f2b48',
                'color_line2' => '#b38b25',
                'align' => 'center',
            ],
            'certificate_title' => [
                'show' => true,
                'x' => 50,
                'y' => 18,
                'text' => 'SERTIFIKAT PELATIHAN',
                'font_size' => 24,
                'color' => '#0f2b48',
                'align' => 'center',
            ],
            'certificate_number' => [
                'x' => 50,
                'y' => 27,
                'font_size' => 12,
                'color' => '#475569',
                'align' => 'center',
            ],
            'recipient_name' => [
                'x' => 50,
                'y' => 37,
                'font_size' => 26,
                'color' => '#0f2b48',
                'align' => 'center',
            ],
            'course_title' => [
                'x' => 50,
                'y' => 49,
                'font_size' => 15,
                'color' => '#1e293b',
                'align' => 'center',
            ],
            'issue_date' => [
                'x' => 50,
                'y' => 67,
                'font_size' => 12,
                'color' => '#64748b',
                'align' => 'center',
            ],
            'qr_code' => [
                'x' => 50,
                'y' => 77,
                'size' => 80,
                'align' => 'center',
            ],
        ];

        $userConfig = $course->certificate_config ?? [];
        $config = array_replace_recursive($defaultConfig, $userConfig);

        $bgDataUri = null;
        if ($course->certificate_template_path && Storage::disk('public')->exists($course->certificate_template_path)) {
            $fileContent = Storage::disk('public')->get($course->certificate_template_path);
            $mime = Storage::disk('public')->mimeType($course->certificate_template_path);
            $bgDataUri = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        } elseif (file_exists(public_path('images/lms/certificate_default_bg.jpg'))) {
            $fileContent = file_get_contents(public_path('images/lms/certificate_default_bg.jpg'));
            $bgDataUri = 'data:image/jpeg;base64,' . base64_encode($fileContent);
        }

        $verificationUrl = route('lms.verify', $enrollment->certificate_hash);
        $qrDataUri = QrCodeService::generatePngDataUri($verificationUrl, 160);

        $issueDateFormatted = $enrollment->certificate_issued_at
            ? $enrollment->certificate_issued_at->locale('id')->isoFormat('D MMMM Y')
            : now()->locale('id')->isoFormat('D MMMM Y');

        $data = [
            'course' => $course,
            'config' => $config,
            'bgDataUri' => $bgDataUri,
            'qrDataUri' => $qrDataUri,
            'recipientName' => strtoupper($participant->name),
            'certificateNumber' => $enrollment->certificate_number,
            'courseTitle' => $course->title,
            'durationDays' => $course->duration_in_days ?: 1,
            'issueDate' => $issueDateFormatted,
            'isPreview' => false,
        ];

        $pdf = Pdf::loadView('lms::certificate_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'Plus Jakarta Sans');

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $participant->name);
        $filename = "Sertifikat_LMS_{$safeName}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Official Certificate Verification Portal (Public route for QR scan).
     */
    public function verify(string $hash): InertiaResponse
    {
        $enrollment = Enrollment::with(['course', 'participant'])
            ->where('certificate_hash', $hash)
            ->first();

        if (!$enrollment) {
            return Inertia::render('Lms::Public/VerifyCertificate', [
                'isValid' => false,
                'hash' => $hash,
                'data' => null,
            ]);
        }

        $p = $enrollment->participant;
        $c = $enrollment->course;

        // Mask NIK for privacy e.g. 731004******0001
        $maskedNik = null;
        if (!empty($p->nik)) {
            $len = strlen($p->nik);
            if ($len > 8) {
                $maskedNik = substr($p->nik, 0, 6) . str_repeat('*', max(1, $len - 10)) . substr($p->nik, -4);
            } else {
                $maskedNik = substr($p->nik, 0, 2) . '****' . substr($p->nik, -2);
            }
        }

        $issuedAt = $enrollment->certificate_issued_at
            ? $enrollment->certificate_issued_at->locale('id')->isoFormat('D MMMM Y')
            : ($enrollment->attendance_at ? $enrollment->attendance_at->locale('id')->isoFormat('D MMMM Y') : '-');

        // Resolve clean, proper institution/agency text
        $agency = trim((string) ($p->agency_or_institution ?? ''));
        if (empty($agency) || strcasecmp($agency, $c->batch_name ?? '') === 0 || preg_match('/^batch\s*\d+/i', $agency)) {
            $agency = 'Masyarakat Umum / Mandiri';
        }

        $methodText = match ($enrollment->attendance_path) {
            'live_zoom' => 'Tatap Muka Daring (Live Online Meeting)',
            'self_study' => 'Pembelajaran Mandiri Berbasis LMS (100% Tuntas)',
            default => 'Pelatihan Terverifikasi Tuntas',
        };

        $downloadUrl = route('lms.certificate.download', $enrollment->id);

        return Inertia::render('Lms::Public/VerifyCertificate', [
            'isValid' => true,
            'hash' => $hash,
            'data' => [
                'participant_name' => $p->name,
                'participant_nik' => $maskedNik,
                'agency_or_institution' => $agency,
                'course_title' => $c->title,
                'category' => $c->category ?? 'Pelatihan Vokasi',
                'batch_name' => $c->batch_name ?? 'Reguler',
                'duration_days' => $c->duration_in_days ?: 1,
                'instructor_name' => $c->instructor_name ?? 'Tim Instruktur BPVP Pangkajene dan Kepulauan',
                'certificate_number' => $enrollment->certificate_number,
                'issued_at' => $issuedAt,
                'attendance_method' => $methodText,
                'download_url' => $downloadUrl,
                'institution' => 'Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan',
                'ministry' => 'Kementerian Ketenagakerjaan Republik Indonesia',
            ],
        ]);
    }
}
