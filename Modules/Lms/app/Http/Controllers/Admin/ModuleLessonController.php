<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Lesson;
use Modules\Lms\Models\LessonProgress;
use Modules\Lms\Models\Module;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ModuleLessonController extends Controller
{
    /**
     * Store new module in course.
     */
    public function storeModule(Request $request, Course $course): RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_number' => 'nullable|integer|min:1|max:365',
            'delivery_mode' => 'nullable|in:sinkronus,asinkronus',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'scheduled_date' => 'nullable|date',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'zoom_link' => 'nullable|string|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $maxOrder = $course->modules()->max('order_index') ?? 0;
        $validated['order_index'] = $maxOrder + 1;
        $validated['course_id'] = $course->id;
        if (empty($validated['day_number'])) {
            $validated['day_number'] = ($course->modules()->max('day_number') ?? 0) + 1;
        }

        if (($validated['delivery_mode'] ?? 'sinkronus') === 'sinkronus' && !empty($validated['scheduled_date'])) {
            if (!empty($validated['start_time'])) {
                try {
                    $validated['zoom_start_at'] = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['start_time'], 'Asia/Makassar');
                    $validated['zoom_status'] = 'upcoming';
                } catch (\Throwable $e) {
                }
            }
            if (!empty($validated['end_time'])) {
                try {
                    $validated['zoom_end_at'] = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['end_time'], 'Asia/Makassar');
                } catch (\Throwable $e) {
                }
            }
        }

        $createdModule = Module::create($validated);

        // SYNC TO COURSE if course is single-day, or this is module 1, or this is today's module
        $today = now('Asia/Makassar')->toDateString();
        $isTodayOrFirst = ($createdModule->scheduled_date && Carbon::parse($createdModule->scheduled_date)->toDateString() === $today) || $createdModule->day_number === 1 || $course->duration_in_days <= 1;

        if ($isTodayOrFirst) {
            $course->zoom_link = $createdModule->zoom_link ?: $course->zoom_link;
            $course->zoom_meeting_id = $createdModule->zoom_meeting_id ?: $course->zoom_meeting_id;
            $course->zoom_passcode = $createdModule->zoom_passcode ?: $course->zoom_passcode;
            if ($createdModule->zoom_start_at) {
                $course->zoom_start_at = $createdModule->zoom_start_at;
            }
            if ($createdModule->zoom_end_at) {
                $course->zoom_end_at = $createdModule->zoom_end_at;
            }
            $course->save();
        }

        return back()->with('success', 'Unit Kompetensi baru berhasil ditambahkan!');
    }

    /**
     * Update existing module.
     */
    public function updateModule(Request $request, Module $module): RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_number' => 'nullable|integer|min:1|max:365',
            'delivery_mode' => 'nullable|in:sinkronus,asinkronus',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'scheduled_date' => 'nullable|date',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'zoom_link' => 'nullable|string|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if (($validated['delivery_mode'] ?? 'sinkronus') === 'sinkronus' && !empty($validated['scheduled_date'])) {
            if (!empty($validated['start_time'])) {
                try {
                    $validated['zoom_start_at'] = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['start_time'], 'Asia/Makassar');
                    if (empty($module->zoom_status) || $module->zoom_status === 'unscheduled') {
                        $validated['zoom_status'] = 'upcoming';
                    }
                } catch (\Throwable $e) {
                }
            }
            if (!empty($validated['end_time'])) {
                try {
                    $validated['zoom_end_at'] = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['end_time'], 'Asia/Makassar');
                } catch (\Throwable $e) {
                }
            }
        }

        $module->update($validated);

        // SYNC TO COURSE if course is single-day, or this is module 1, or this is today's module
        $course = $module->course;
        $today = now('Asia/Makassar')->toDateString();
        $isTodayOrFirst = ($module->scheduled_date && Carbon::parse($module->scheduled_date)->toDateString() === $today) || $module->day_number === 1 || $course->duration_in_days <= 1;

        if ($isTodayOrFirst) {
            $course->zoom_link = $module->zoom_link ?: $course->zoom_link;
            $course->zoom_meeting_id = $module->zoom_meeting_id ?: $course->zoom_meeting_id;
            $course->zoom_passcode = $module->zoom_passcode ?: $course->zoom_passcode;
            if ($module->zoom_start_at) {
                $course->zoom_start_at = $module->zoom_start_at;
            }
            if ($module->zoom_end_at) {
                $course->zoom_end_at = $module->zoom_end_at;
            }
            $course->save();
        }

        return back()->with('success', 'Unit Kompetensi berhasil diperbarui!');
    }

    /**
     * Delete module and all its lessons.
     */
    public function destroyModule(Request $request, Module $module): RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $course = $module->course;
        $module->delete();

        // Recalculate progress for all enrollments in the course
        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', 'Unit Kompetensi beserta isinya berhasil dihapus.');
    }

    /**
     * Reorder modules.
     */
    public function reorderModules(Request $request, Course $course): RedirectResponse
    {
        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:lms_modules,id',
            'orders.*.order_index' => 'required|integer',
        ])['orders'];

        foreach ($orders as $item) {
            Module::where('id', $item['id'])
                ->where('course_id', $course->id)
                ->update(['order_index' => $item['order_index']]);
        }

        return back()->with('success', 'Urutan modul berhasil diperbarui!');
    }

    /**
     * Store new lesson in a module.
     */
    public function storeLesson(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:lms_modules,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:video,article,image,pdf',
            'content_text' => 'nullable|string',
            'video_url' => 'nullable|string|max:500',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:600',
            'media_file' => 'nullable|file|max:51200', // 50MB max upload
        ]);

        $maxOrder = Lesson::where('module_id', $validated['module_id'])->max('order_index') ?? 0;
        $validated['order_index'] = $maxOrder + 1;
        $validated['course_id'] = $course->id;

        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('lms/lessons', 'public');
            $validated['media_path'] = $path;
        }

        unset($validated['media_file']);

        $lesson = Lesson::create($validated);

        // Auto create lesson progress entries for existing enrolled participants
        foreach ($course->enrollments as $enrollment) {
            LessonProgress::firstOrCreate([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ], [
                'is_completed' => false,
            ]);
            $enrollment->recalculateProgress();
        }

        return back()->with('success', 'Pelajaran berhasil ditambahkan ke modul!');
    }

    /**
     * Update existing lesson.
     */
    public function updateLesson(Request $request, Lesson $lesson): RedirectResponse
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:lms_modules,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:video,article,image,pdf',
            'content_text' => 'nullable|string',
            'video_url' => 'nullable|string|max:500',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:600',
            'media_file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('media_file')) {
            if ($lesson->media_path) {
                Storage::disk('public')->delete($lesson->media_path);
            }
            $validated['media_path'] = $request->file('media_file')->store('lms/lessons', 'public');
        }

        unset($validated['media_file']);

        $lesson->update($validated);

        return back()->with('success', 'Pelajaran berhasil diperbarui!');
    }

    /**
     * Delete lesson.
     */
    public function destroyLesson(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->course;
        if ($lesson->media_path) {
            Storage::disk('public')->delete($lesson->media_path);
        }
        $lesson->delete();

        // Recalculate progress for enrollments
        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', 'Pelajaran berhasil dihapus.');
    }

    /**
     * Reorder lessons within a module.
     */
    public function reorderLessons(Request $request, Module $module): RedirectResponse
    {
        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:lms_lessons,id',
            'orders.*.order_index' => 'required|integer',
        ])['orders'];

        foreach ($orders as $item) {
            Lesson::where('id', $item['id'])
                ->where('module_id', $module->id)
                ->update(['order_index' => $item['order_index']]);
        }

        return back()->with('success', 'Urutan pelajaran berhasil diperbarui!');
    }

    /**
     * Download CSV template for importing Unit & Elemen Kompetensi.
     */
    public function downloadTemplate(Course $course): StreamedResponse
    {
        $filename = 'Template_Import_Unit_Elemen_Kompetensi.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header columns
            fputcsv($handle, [
                'Kode Unit',
                'Judul Unit Kompetensi',
                'Deskripsi Unit',
                'Judul Elemen Kompetensi',
                'Tipe Konten (article/video/image)',
                'Estimasi Durasi (Menit)',
                'Isi Materi Teks',
                'URL Video (Opsional)',
            ]);

            // Sample rows matching BPVP / SKKNI vocational training standards
            $sampleRows = [
                [
                    'UK-01',
                    'Menerapkan Prinsip Keselamatan dan Kesehatan Kerja di Tempat Kerja (K3)',
                    'Unit ini mencakup kompetensi penerapan K3, identifikasi bahaya, dan SOP pencegahan kecelakaan kerja.',
                    '1.1 Mengidentifikasi potensi bahaya dan risiko keselamatan di area kerja',
                    'article',
                    15,
                    'Peserta wajib mengenali potensi bahaya fisik, mekanik, kelistrikan, dan bahan kimia di lingkungan bengkel pelatihan sebelum memulai pekerjaan...',
                    '',
                ],
                [
                    'UK-01',
                    'Menerapkan Prinsip Keselamatan dan Kesehatan Kerja di Tempat Kerja (K3)',
                    'Unit ini mencakup kompetensi penerapan K3, identifikasi bahaya, dan SOP pencegahan kecelakaan kerja.',
                    '1.2 Menggunakan Alat Pelindung Diri (APD) sesuai standar operasional',
                    'video',
                    20,
                    'Video panduan penggunaan perlengkapan APD: helm safety, kacamata pelindung, sarung tangan khusus, dan safety shoes sesuai standar Kemnaker.',
                    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                [
                    'UK-02',
                    'Mengoperasikan dan Merawat Peralatan Mesin Kerja',
                    'Unit ini mencakup prosedur standar persiapan, pengoperasian, dan perawatan harian mesin perkakas.',
                    '2.1 Melakukan pemeriksaan awal kelayakan mesin sebelum dioperasikan',
                    'article',
                    10,
                    'Langkah checklist awal: periksa suplai tegangan listrik, pelumasan mesin, sistem pengaman, dan tombol emergency stop...',
                    '',
                ],
                [
                    'UK-02',
                    'Mengoperasikan dan Merawat Peralatan Mesin Kerja',
                    'Unit ini mencakup prosedur standar persiapan, pengoperasian, dan perawatan harian mesin perkakas.',
                    '2.2 Menjalankan mesin sesuai SOP instruksi kerja',
                    'article',
                    15,
                    'Langkah kerja pengoperasian mesin secara bertahap dan pemantauan parameter kerja...',
                    '',
                ],
            ];

            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import curriculum (Unit Kompetensi & Elemen Kompetensi) from CSV/XLSX file.
     */
    public function importCurriculum(Request $request, Course $course): RedirectResponse
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

        $unitsCreated = 0;
        $lessonsCreated = 0;
        $modulesCache = [];

        // Load existing modules to cache
        foreach ($course->modules as $mod) {
            $modulesCache[trim(strtolower($mod->title))] = $mod;
        }

        $currentMaxModuleOrder = $course->modules()->max('order_index') ?? 0;

        foreach ($rows as $row) {
            $unitTitle = trim((string) ($row['judul_unit'] ?? ''));
            $unitCode = trim((string) ($row['kode_unit'] ?? ''));
            $unitDesc = trim((string) ($row['deskripsi_unit'] ?? ''));

            if (empty($unitTitle)) {
                continue;
            }

            // Combine unit code if provided and not already in title
            $fullUnitTitle = $unitTitle;
            if (!empty($unitCode) && !str_contains(strtolower($unitTitle), strtolower($unitCode))) {
                $fullUnitTitle = "{$unitCode} - {$unitTitle}";
            }

            $cacheKey = strtolower($fullUnitTitle);
            if (!isset($modulesCache[$cacheKey])) {
                $currentMaxModuleOrder++;
                $newModule = Module::create([
                    'course_id' => $course->id,
                    'title' => $fullUnitTitle,
                    'description' => !empty($unitDesc) ? $unitDesc : null,
                    'order_index' => $currentMaxModuleOrder,
                ]);
                $modulesCache[$cacheKey] = $newModule;
                $unitsCreated++;
            }

            $module = $modulesCache[$cacheKey];

            // Handle Elemen Kompetensi (Lesson)
            $lessonTitle = trim((string) ($row['judul_elemen'] ?? ''));
            if (!empty($lessonTitle)) {
                $contentType = strtolower(trim((string) ($row['tipe_konten'] ?? 'article')));
                if (!in_array($contentType, ['article', 'video', 'image', 'pdf'])) {
                    $contentType = 'article';
                }

                $rawDuration = (int) ($row['durasi'] ?? 10);
                $duration = $rawDuration > 0 ? $rawDuration : 10;
                $contentText = trim((string) ($row['isi_materi'] ?? ''));
                $videoUrl = trim((string) ($row['video_url'] ?? ''));

                $existingLesson = Lesson::where('module_id', $module->id)
                    ->where('title', $lessonTitle)
                    ->first();

                if (!$existingLesson) {
                    $currentMaxLessonOrder = Lesson::where('module_id', $module->id)->max('order_index') ?? 0;
                    Lesson::create([
                        'module_id' => $module->id,
                        'title' => $lessonTitle,
                        'content_type' => $contentType,
                        'estimated_duration_minutes' => $duration,
                        'content_text' => !empty($contentText) ? $contentText : null,
                        'video_url' => !empty($videoUrl) ? $videoUrl : null,
                        'order_index' => $currentMaxLessonOrder + 1,
                    ]);
                    $lessonsCreated++;
                }
            }
        }

        // Recalculate progress for enrollments
        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', "Import Unit & Elemen Kompetensi berhasil! Berhasil menambahkan {$unitsCreated} Unit Kompetensi dan {$lessonsCreated} Elemen Kompetensi.");
    }

    /**
     * Map fuzzy column headers to canonical curriculum fields.
     */
    protected function resolveCurriculumColumnMap(array $header): array
    {
        $map = [];
        foreach ($header as $idx => $rawName) {
            $col = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string) $rawName)));

            if (in_array($col, ['kodeunit', 'kode', 'code', 'unitcode', 'nourut', 'nomorunit']) && !isset($map['kode_unit'])) {
                $map['kode_unit'] = $idx;
            } elseif (in_array($col, ['judulunit', 'judulunitkompetensi', 'namaunit', 'namaunitkompetensi', 'unitkompetensi', 'unit', 'modul', 'judulmodul']) && !isset($map['judul_unit'])) {
                $map['judul_unit'] = $idx;
            } elseif (in_array($col, ['deskripsiunit', 'deskripsi', 'keteranganunit', 'keterangan', 'uraianunit']) && !isset($map['deskripsi_unit'])) {
                $map['deskripsi_unit'] = $idx;
            } elseif (in_array($col, ['judulelemen', 'judulelemenkompetensi', 'namaelemen', 'namaelemenkompetensi', 'elemenkompetensi', 'elemen', 'pelajaran', 'judulpelajaran', 'materi', 'judulmateri', 'bab']) && !isset($map['judul_elemen'])) {
                $map['judul_elemen'] = $idx;
            } elseif (in_array($col, ['tipekonten', 'tipe', 'contenttype', 'jenis', 'jeniskonten']) && !isset($map['tipe_konten'])) {
                $map['tipe_konten'] = $idx;
            } elseif (in_array($col, ['durasi', 'durasimenit', 'estimasiwaktu', 'menit', 'duration', 'estimasidurasi']) && !isset($map['durasi'])) {
                $map['durasi'] = $idx;
            } elseif (in_array($col, ['isimateri', 'materi', 'teks', 'artikel', 'content', 'deskripsielemen', 'isipelajaran']) && !isset($map['isi_materi'])) {
                $map['isi_materi'] = $idx;
            } elseif (in_array($col, ['urlvideo', 'videourl', 'linkvideo', 'video', 'link']) && !isset($map['video_url'])) {
                $map['video_url'] = $idx;
            }
        }

        // Positional fallback if standard columns:
        // A (0): Kode Unit, B (1): Judul Unit, C (2): Deskripsi, D (3): Judul Elemen, E (4): Tipe, F (5): Durasi, G (6): Isi Teks, H (7): Video URL
        if (!isset($map['judul_unit']) && isset($header[1])) {
            $map['kode_unit'] = 0;
            $map['judul_unit'] = 1;
            $map['deskripsi_unit'] = 2;
            $map['judul_elemen'] = 3;
            $map['tipe_konten'] = 4;
            $map['durasi'] = 5;
            $map['isi_materi'] = 6;
            $map['video_url'] = 7;
        }

        return $map;
    }

    /**
     * Parse CSV file into rows.
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $line = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fseek($handle, 3);
        }
        $delimiter = (substr_count($line, ';') > substr_count($line, ',')) ? ';' : ',';

        $header = null;
        $colMap = [];
        $results = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = $row;
                $colMap = $this->resolveCurriculumColumnMap($header);
                continue;
            }

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
     * Parse XLSX file into rows.
     */
    protected function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }

        // Read shared strings
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

        // Read sheet1
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

                if ($type === 's' && isset($cell->v) && isset($sharedStrings[(int) $cell->v])) {
                    $cellVal = $sharedStrings[(int) $cell->v];
                } elseif (isset($cell->f) && trim((string) $cell->f) !== '') {
                    $formula = trim((string) $cell->f);
                    if (str_starts_with($formula, '=')) {
                        $formula = ltrim($formula, '=');
                    }
                    $cellVal = trim($formula, "\"'\t\n\r ");
                    if ($cellVal === '' && isset($cell->v)) {
                        $cellVal = (string) $cell->v;
                    }
                } elseif (isset($cell->v)) {
                    $cellVal = (string) $cell->v;
                }

                if (str_starts_with($cellVal, '=')) {
                    $cellVal = ltrim($cellVal, '=');
                }
                $cellVal = trim($cellVal, "\"'\t\n\r ");

                $cellRef = (string) $cell['r'];
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $colIdx = $this->letterToColumnIndex($colLetters);

                $rowValues[$colIdx] = $cellVal;
            }

            if (!empty($rowValues)) {
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
        $colMap = $this->resolveCurriculumColumnMap($header);

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
