<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\LessonProgress;
use Modules\Lms\Models\Participant;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ParticipantImportController extends Controller
{
    /**
     * Enroll a single participant manually.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'training_transaction_code' => 'nullable|string|max:100',
            'nik' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'gender' => 'nullable|in:L,P',
            'agency_or_institution' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $email = strtolower(trim($validated['email']));

        // Find or create participant (auto-sync multi-class by email)
        $participant = Participant::updateOrCreate(
            ['email' => $email],
            [
                'name' => trim($validated['name']),
                'training_transaction_code' => !empty($validated['training_transaction_code']) ? trim($validated['training_transaction_code']) : null,
                'nik' => !empty($validated['nik']) ? trim($validated['nik']) : null,
                'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
                'agency_or_institution' => !empty($validated['agency_or_institution']) ? trim($validated['agency_or_institution']) : 'Masyarakat Umum / Mandiri',
                'gender' => $validated['gender'] ?? null,
                'address' => !empty($validated['address']) ? trim($validated['address']) : null,
            ]
        );

        // Check if already enrolled in this course
        $existingEnrollment = Enrollment::where('course_id', $course->id)
            ->where('participant_id', $participant->id)
            ->first();

        if ($existingEnrollment) {
            return back()->with('warning', "Peserta dengan email '{$email}' sudah terdaftar di kelas ini.");
        }

        $enrollment = Enrollment::create([
            'course_id' => $course->id,
            'participant_id' => $participant->id,
            'training_transaction_code' => !empty($validated['training_transaction_code']) ? trim($validated['training_transaction_code']) : null,
            'status' => 'enrolled',
            'attendance_path' => 'none',
            'progress_percentage' => 0.00,
        ]);

        // Init lesson progress records
        $this->initLessonProgressForEnrollment($enrollment, $course);

        return back()->with('success', "Peserta '{$participant->name}' berhasil ditambahkan ke kelas.");
    }

    /**
     * Import participants from CSV / XLSX file.
     */
    public function import(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            $rows = $this->parseCsv($file->getRealPath());
        } elseif ($ext === 'xlsx') {
            $rows = $this->parseXlsx($file->getRealPath());
        } else {
            return back()->with('error', 'Format file tidak didukung. Harap gunakan file CSV atau Excel (.xlsx).');
        }

        if (empty($rows)) {
            return back()->with('error', 'File kosong atau format kolom tidak dapat dibaca.');
        }

        $successCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $email = isset($row['email']) ? strtolower(trim($row['email'])) : '';
            $name = isset($row['name']) ? trim($row['name']) : '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            if (empty($name)) {
                $name = explode('@', $email)[0];
            }

            $rawCode = isset($row['training_transaction_code']) ? trim((string) $row['training_transaction_code']) : null;
            $code = ($rawCode === '0' || $rawCode === '') ? null : $rawCode;

            $rawNik = isset($row['nik']) ? trim((string) $row['nik']) : null;
            $nik = ($rawNik === '0' || $rawNik === '') ? null : $rawNik;

            $rawPhone = isset($row['phone']) ? trim((string) $row['phone']) : null;
            $phone = ($rawPhone === '0' || $rawPhone === '') ? null : $rawPhone;

            $rawAgency = isset($row['agency']) ? trim((string) $row['agency']) : null;
            $agency = ($rawAgency === '0' || $rawAgency === '' || $rawAgency === null) ? 'Masyarakat Umum / Mandiri' : $rawAgency;

            $gender = isset($row['gender']) ? strtoupper(substr(trim((string) $row['gender']), 0, 1)) : null;
            if ($gender !== 'L' && $gender !== 'P') {
                $gender = null;
            }

            $rawAddress = isset($row['address']) ? trim((string) $row['address']) : null;
            $address = ($rawAddress === '0' || $rawAddress === '') ? null : $rawAddress;

            // Sync participant record
            $participantData = [
                'name' => $name,
            ];
            if ($code !== null) {
                $participantData['training_transaction_code'] = $code;
            }
            if ($nik !== null) {
                $participantData['nik'] = $nik;
            }
            if ($phone !== null) {
                $participantData['phone'] = $phone;
            }
            if ($agency !== null) {
                $participantData['agency_or_institution'] = $agency;
            }
            if ($gender !== null) {
                $participantData['gender'] = $gender;
            }
            if ($address !== null) {
                $participantData['address'] = $address;
            }

            $participant = Participant::updateOrCreate(
                ['email' => $email],
                $participantData
            );

            // Enroll in this course if not already enrolled
            $enrollment = Enrollment::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'participant_id' => $participant->id,
                ],
                [
                    'training_transaction_code' => $code,
                    'status' => 'enrolled',
                    'attendance_path' => 'none',
                    'progress_percentage' => 0.00,
                ]
            );

            if ($code && !$enrollment->training_transaction_code) {
                $enrollment->update(['training_transaction_code' => $code]);
            }

            if ($enrollment->wasRecentlyCreated) {
                $this->initLessonProgressForEnrollment($enrollment, $course);
                $successCount++;
            } else {
                if ($enrollment->attendance_path === 'none' && $enrollment->status !== 'completed') {
                    $enrollment->recalculateProgress();
                }
                $updatedCount++;
            }
        }

        $msg = "Import selesai! {$successCount} peserta baru didaftarkan";
        if ($updatedCount > 0) {
            $msg .= ", {$updatedCount} peserta sudah ada diperbarui";
        }
        if ($skippedCount > 0) {
            $msg .= ", {$skippedCount} baris dilewati (email tidak valid)";
        }

        return back()->with('success', $msg);
    }

    /**
     * Update participant details and enrollment.
     */
    public function update(Request $request, Course $course, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'training_transaction_code' => 'nullable|string|max:100',
            'nik' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'gender' => 'nullable|in:L,P',
            'agency_or_institution' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'status' => 'nullable|in:enrolled,in_progress,completed',
            'attendance_path' => 'nullable|in:live_zoom,self_study,none',
            'attendance_at' => 'nullable|date',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'certificate_number' => 'nullable|string|max:150',
            'daily_attendances' => 'nullable|array',
        ]);

        $email = strtolower(trim($validated['email']));

        $participant = $enrollment->participant;
        if ($participant) {
            $participantUpdate = [
                'name' => trim($validated['name']),
                'email' => $email,
                'training_transaction_code' => !empty($validated['training_transaction_code']) ? trim($validated['training_transaction_code']) : null,
                'nik' => !empty($validated['nik']) ? trim($validated['nik']) : null,
                'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
                'gender' => $validated['gender'] ?? null,
                'address' => !empty($validated['address']) ? trim($validated['address']) : null,
            ];
            if ($request->has('agency_or_institution')) {
                $participantUpdate['agency_or_institution'] = !empty($validated['agency_or_institution']) ? trim($validated['agency_or_institution']) : 'Masyarakat Umum / Mandiri';
            }
            $participant->update($participantUpdate);
        }

        $enrollmentData = [
            'training_transaction_code' => !empty($validated['training_transaction_code']) ? trim($validated['training_transaction_code']) : null,
        ];
        if (!empty($validated['status'])) {
            $enrollmentData['status'] = $validated['status'];
            if ($validated['status'] === 'completed' && empty($enrollment->completed_at)) {
                $enrollmentData['completed_at'] = now();
            }
        }
        if ($request->has('attendance_path')) {
            $enrollmentData['attendance_path'] = $validated['attendance_path'] ?: 'none';
            if ($enrollmentData['attendance_path'] !== 'none' && empty($enrollment->attendance_at)) {
                $enrollmentData['attendance_at'] = now();
            }
        }
        if ($request->has('attendance_at')) {
            $enrollmentData['attendance_at'] = !empty($validated['attendance_at']) ? \Carbon\Carbon::parse($validated['attendance_at']) : null;
        }
        if ($request->has('progress_percentage')) {
            $enrollmentData['progress_percentage'] = (float) $validated['progress_percentage'];
        }
        if ($request->has('certificate_number')) {
            $enrollmentData['certificate_number'] = !empty($validated['certificate_number']) ? trim($validated['certificate_number']) : null;
            if (!empty($enrollmentData['certificate_number']) && empty($enrollment->certificate_hash)) {
                $enrollmentData['certificate_hash'] = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(32));
                $enrollmentData['certificate_issued_at'] = now();
            }
        }

        // Process daily module attendances if submitted
        if ($request->has('daily_attendances') && is_array($request->input('daily_attendances'))) {
            foreach ($request->input('daily_attendances') as $modId => $modPath) {
                $mod = \Modules\Lms\Models\Module::where('course_id', $course->id)->find($modId);
                if (! $mod) continue;

                if (empty($modPath) || $modPath === 'none') {
                    \Modules\Lms\Models\ModuleAttendance::where('enrollment_id', $enrollment->id)
                        ->where('module_id', $modId)
                        ->delete();
                } else {
                    \Modules\Lms\Models\ModuleAttendance::updateOrCreate(
                        [
                            'enrollment_id' => $enrollment->id,
                            'module_id' => $modId,
                        ],
                        [
                            'course_id' => $course->id,
                            'participant_id' => $enrollment->participant_id,
                            'day_number' => $mod->day_number ?? 1,
                            'attendance_path' => $modPath === 'self_study' ? 'self_study' : 'live_zoom',
                            'attended_at' => now(),
                        ]
                    );
                }
            }
            // Auto sync overall attendance_path from daily attendances
            $hasLiveZoom = \Modules\Lms\Models\ModuleAttendance::where('enrollment_id', $enrollment->id)
                ->where('attendance_path', 'live_zoom')->exists();
            $hasSelfStudy = \Modules\Lms\Models\ModuleAttendance::where('enrollment_id', $enrollment->id)
                ->where('attendance_path', 'self_study')->exists();

            if ($hasLiveZoom) {
                $enrollmentData['attendance_path'] = 'live_zoom';
            } elseif ($hasSelfStudy) {
                $enrollmentData['attendance_path'] = 'self_study';
            }
        }

        $enrollment->update($enrollmentData);

        return back()->with('success', "Data peserta '{$participant?->name}' berhasil diperbarui.");
    }

    /**
     * Remove participant enrollment from course.
     */
    public function destroy(Course $course, Enrollment $enrollment): RedirectResponse
    {
        $name = $enrollment->participant->name ?? 'Peserta';
        $enrollment->delete();

        return back()->with('success', "Peserta '{$name}' telah dikeluarkan dari kelas ini.");
    }

    /**
     * Bulk remove participant enrollments from course.
     */
    public function bulkDestroy(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => 'required|array',
            'enrollment_ids.*' => 'integer',
        ]);

        $ids = $validated['enrollment_ids'];
        $count = Enrollment::where('course_id', $course->id)
            ->whereIn('id', $ids)
            ->delete();

        return back()->with('success', "{$count} peserta berhasil dikeluarkan dari kelas.");
    }

    /**
     * Export enrolled participants to CSV.
     */
    public function export(Course $course): StreamedResponse
    {
        $course->load('enrollments.participant');
        $fileName = 'Peserta_LMS_' . str_replace(' ', '_', $course->title) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($course) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Kode Transaksi',
                'Nama Lengkap',
                'Email',
                'NIK',
                'No Telepon',
                'Jenis Kelamin',
                'Alamat',
                'Asal Instansi',
                'Status',
                'Jalur Presensi',
                'Waktu Presensi',
                'Progress Belajar (%)',
                'No Sertifikat',
                'Kode Verifikasi TTE',
            ]);

            foreach ($course->enrollments as $e) {
                $p = $e->participant;
                fputcsv($handle, [
                    $e->id,
                    $e->training_transaction_code ?? ($p->training_transaction_code ?? '-'),
                    $p->name ?? '',
                    $p->email ?? '',
                    $p->nik ? "'" . $p->nik : '-',
                    $p->phone ? "'" . $p->phone : '-',
                    $p->gender === 'L' ? 'Laki-Laki' : ($p->gender === 'P' ? 'Perempuan' : '-'),
                    $p->address ?? '-',
                    $p->agency_or_institution ?? '-',
                    $e->status,
                    $e->attendance_path === 'live_zoom' ? 'Zoom Live' : ($e->attendance_path === 'self_study' ? 'Belajar Mandiri' : 'Belum Hadir'),
                    $e->attendance_at ? $e->attendance_at->format('Y-m-d H:i:s') : '-',
                    $e->progress_percentage . '%',
                    $e->certificate_number ?? '-',
                    $e->certificate_hash ?? '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Initialize lesson progress rows for newly enrolled participant.
     */
    protected function initLessonProgressForEnrollment(Enrollment $enrollment, Course $course): void
    {
        $lessons = $course->lessons;
        if ($lessons->isEmpty()) {
            $enrollment->update([
                'progress_percentage' => 0.00,
                'status' => 'enrolled',
            ]);

            return;
        }

        foreach ($lessons as $lesson) {
            LessonProgress::firstOrCreate([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ], [
                'is_completed' => false,
            ]);
        }
        $enrollment->recalculateProgress();
    }

    /**
     * Parse CSV file with flexible delimiter detection.
     */
    protected function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if (str_contains($firstLine, ';')) {
            $delimiter = ';';
        } elseif (str_contains($firstLine, "\t")) {
            $delimiter = "\t";
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return [];
        }

        // Clean headers of formula markers or quotes
        $cleanHeader = array_map(function ($h) {
            $val = trim((string) $h);
            if (str_starts_with($val, '=')) {
                $val = ltrim($val, '=');
            }
            return trim($val, "\"'\t\n\r ");
        }, $header);

        $colMap = $this->resolveColumnMap($cleanHeader);

        $results = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $item = [];
            foreach ($colMap as $key => $idx) {
                $val = trim((string) ($row[$idx] ?? ''));
                if (str_starts_with($val, '=')) {
                    $val = ltrim($val, '=');
                }
                $item[$key] = trim($val, "\"'\t\n\r ");
            }
            $results[] = $item;
        }

        fclose($handle);
        return $results;
    }

    /**
     * Pure PHP XLSX Parser using ZipArchive and SimpleXML (no third-party dependencies required).
     * Accurately parses formulas, strings, shared strings, and raw values.
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml) {
            $xml = simplexml_load_string($stringsXml);
            foreach ($xml->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string) $val->t;
                } elseif (isset($val->r)) {
                    $t = '';
                    foreach ($val->r as $r) {
                        $t .= (string) $r->t;
                    }
                    $sharedStrings[] = $t;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Read sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $rowValues = [];
            foreach ($rowNode->c as $cell) {
                $type = (string) $cell['t'];
                $cellVal = '';

                // Handle shared strings
                if ($type === 's' && isset($cell->v) && isset($sharedStrings[(int) $cell->v])) {
                    $cellVal = $sharedStrings[(int) $cell->v];
                }
                // Handle formulas e.g. <f>"1911730901-312E788D"</f> or <f>="7314052708820001"</f>
                elseif (isset($cell->f) && trim((string) $cell->f) !== '') {
                    $formula = trim((string) $cell->f);
                    if (str_starts_with($formula, '=')) {
                        $formula = ltrim($formula, '=');
                    }
                    $cellVal = trim($formula, "\"'\t\n\r ");
                    // If formula is empty or yielded nothing, check value node
                    if ($cellVal === '' && isset($cell->v)) {
                        $cellVal = (string) $cell->v;
                    }
                }
                // Handle standard values
                elseif (isset($cell->v)) {
                    $cellVal = (string) $cell->v;
                }

                // Strip any formula artifact '=' or quotes from value
                if (str_starts_with($cellVal, '=')) {
                    $cellVal = ltrim($cellVal, '=');
                }
                $cellVal = trim($cellVal, "\"'\t\n\r ");

                // Determine column index from cell reference e.g. A1, B1
                $cellRef = (string) $cell['r'];
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $colIdx = $this->letterToColumnIndex($colLetters);

                $rowValues[$colIdx] = $cellVal;
            }

            if (!empty($rowValues)) {
                // Fill any missing indices up to max
                $maxIdx = max(array_keys($rowValues));
                $normalizedRow = [];
                for ($i = 0; $i <= $maxIdx; $i++) {
                    $normalizedRow[$i] = $rowValues[$i] ?? '';
                }
                $rows[] = $normalizedRow;
            }
        }

        if (empty($rows)) {
            return [];
        }

        $header = array_shift($rows);
        $colMap = $this->resolveColumnMap($header);

        $results = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($colMap as $key => $idx) {
                $val = trim((string) ($row[$idx] ?? ''));
                if (str_starts_with($val, '=')) {
                    $val = ltrim($val, '=');
                }
                $item[$key] = trim($val, "\"'\t\n\r ");
            }
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Map fuzzy column headers to canonical participant fields.
     */
    protected function resolveColumnMap(array $header): array
    {
        $map = [];
        foreach ($header as $idx => $rawName) {
            $col = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string) $rawName)));

            if (in_array($col, ['kodetransaksi', 'kodetransaksipelatihan', 'notransaksi', 'transactioncode', 'transactionid', 'kode', 'kodependaftaran']) && !isset($map['training_transaction_code'])) {
                $map['training_transaction_code'] = $idx;
            } elseif (in_array($col, ['email', 'surel', 'alamatemail', 'mail']) && !isset($map['email'])) {
                $map['email'] = $idx;
            } elseif (in_array($col, ['nama', 'namalengkap', 'name', 'fullname', 'peserta', 'namapeserta']) && !isset($map['name'])) {
                $map['name'] = $idx;
            } elseif (in_array($col, ['nik', 'ktp', 'noktp', 'nomorindukkependudukan', 'nomoridentitas']) && !isset($map['nik'])) {
                $map['nik'] = $idx;
            } elseif (in_array($col, ['nohp', 'phone', 'telp', 'telepon', 'whatsapp', 'nowa', 'handphone']) && !isset($map['phone'])) {
                $map['phone'] = $idx;
            } elseif (in_array($col, ['instansi', 'asalinstansi', 'perusahaan', 'lembaga', 'agency', 'sekolah', 'kampus']) && !isset($map['agency'])) {
                $map['agency'] = $idx;
            } elseif (in_array($col, ['jk', 'gender', 'jeniskelamin', 'sex']) && !isset($map['gender'])) {
                $map['gender'] = $idx;
            } elseif (in_array($col, ['alamat', 'address', 'domisili', 'tempattinggal', 'alamatktp', 'alamatpeserta']) && !isset($map['address'])) {
                $map['address'] = $idx;
            }
        }

        // Positional fallback if standard columns A-G were provided without matching header names:
        // A (0): Kode, B (1): NIK, C (2): Name, D (3): No HP, E (4): Email, F (5): Gender, G (6): Alamat
        if (!isset($map['email']) && isset($header[4])) {
            $map['training_transaction_code'] = 0;
            $map['nik'] = 1;
            $map['name'] = 2;
            $map['phone'] = 3;
            $map['email'] = 4;
            $map['gender'] = 5;
            $map['address'] = 6;
        }

        // Final fallback: if no email or name found, default to index 1 and 0
        if (!isset($map['email'])) {
            $map['email'] = 1;
        }
        if (!isset($map['name'])) {
            $map['name'] = 0;
        }

        return $map;
    }

    protected function letterToColumnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $idx = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $idx - 1;
    }
}
