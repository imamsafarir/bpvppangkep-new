<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Lms\Models\Course;
use Modules\Lms\Services\QrCodeService;

class CertificateBuilderController extends Controller
{
    /**
     * Upload custom A4 Landscape template background.
     */
    public function uploadTemplate(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'template_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
        ]);

        if ($course->certificate_template_path) {
            Storage::disk('public')->delete($course->certificate_template_path);
        }

        $path = $request->file('template_image')->store('lms/certificates/templates', 'public');
        $course->certificate_template_path = $path;
        $course->save();

        return back()->with('success', 'Background template sertifikat berhasil diunggah!');
    }

    /**
     * Update coordinate grid and typography settings.
     */
    public function updateConfig(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'certificate_config' => 'required|array',
            'certificate_number_format' => 'nullable|string|max:100',
        ]);

        $course->certificate_config = $validated['certificate_config'];
        $course->certificate_number_format = 'BPVP-PANGKEP/LMS/{YEAR}/{ID}';
        $course->save();

        return back()->with('success', 'Konfigurasi tata letak sertifikat berhasil disimpan!');
    }

    /**
     * Preview sample certificate PDF with dummy participant.
     */
    public function preview(Request $request, Course $course): Response
    {
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

        $userConfig = $request->input('certificate_config') ?? $course->certificate_config ?? [];
        $config = array_replace_recursive($defaultConfig, $userConfig);
        $templatePath = $course->certificate_template_path;
        $bgDataUri = null;

        if ($templatePath && Storage::disk('public')->exists($templatePath)) {
            $fileContent = Storage::disk('public')->get($templatePath);
            $mime = Storage::disk('public')->mimeType($templatePath);
            $bgDataUri = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        } elseif (file_exists(public_path('images/lms/certificate_default_bg.jpg'))) {
            $fileContent = file_get_contents(public_path('images/lms/certificate_default_bg.jpg'));
            $bgDataUri = 'data:image/jpeg;base64,' . base64_encode($fileContent);
        }

        $dummyUrl = route('lms.verify', 'sample-preview-verification-token');
        $qrDataUri = QrCodeService::generatePngDataUri($dummyUrl, 160);

        $durationDays = $course->duration_in_days ?: 1;

        $data = [
            'course' => $course,
            'config' => $config,
            'bgDataUri' => $bgDataUri,
            'qrDataUri' => $qrDataUri,
            'recipientName' => 'MUHAMMAD IKHLAS, S.T.',
            'certificateNumber' => 'BPVP-PANGKEP/LMS/' . date('Y') . '/1',
            'courseTitle' => $course->title,
            'durationDays' => $durationDays,
            'issueDate' => now()->locale('id')->isoFormat('D MMMM Y'),
            'isPreview' => true,
        ];

        $pdf = Pdf::loadView('lms::certificate_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'Plus Jakarta Sans');

        return $pdf->stream("Preview_Sertifikat_{$course->slug}.pdf");
    }
}
