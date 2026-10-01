<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Module;
use Modules\Lms\Models\ModuleAttendance;

class OnlineMeetingController extends Controller
{
    /**
     * Open Online Meeting attendance session now with specified duration.
     */
    public function openAttendance(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $duration = (int) ($validated['duration_minutes'] ?? 30);

        $course->is_zoom_attendance_open = true;
        $course->zoom_attendance_opened_at = now();
        $course->zoom_attendance_duration_minutes = $duration;
        $course->zoom_attendance_closed_at = now()->addMinutes($duration);
        $course->save();

        $message = "Sesi Absensi Online Meeting berhasil DIBUKA selama {$duration} menit (hingga {$course->zoom_attendance_closed_at->format('H:i')}).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_zoom_attendance_open' => true,
                'zoom_attendance_opened_at' => $course->zoom_attendance_opened_at,
                'zoom_attendance_duration_minutes' => $duration,
                'zoom_attendance_closed_at' => $course->zoom_attendance_closed_at,
                'remaining_seconds' => $course->attendance_remaining_seconds,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Close Online Meeting attendance session immediately.
     */
    public function closeAttendance(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $course->is_zoom_attendance_open = false;
        $course->zoom_attendance_closed_at = now();
        $course->zoom_attendance_scheduled_at = null;
        $course->save();

        $message = "Sesi Absensi Online Meeting untuk kelas '{$course->title}' telah DITUTUP.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_zoom_attendance_open' => false,
                'zoom_attendance_closed_at' => $course->zoom_attendance_closed_at,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Toggle the Online Meeting Attendance Window (Buka / Tutup Absen Online Meeting).
     */
    public function toggleAttendance(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        if ($course->is_attendance_open_now) {
            return $this->closeAttendance($request, $course);
        } else {
            return $this->openAttendance($request, $course);
        }
    }

    /**
     * Set scheduled time for Online Meeting attendance to automatically open.
     */
    public function scheduleAttendance(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'duration_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $course->zoom_attendance_scheduled_at = !empty($validated['scheduled_at']) ? Carbon::parse($validated['scheduled_at'], 'Asia/Makassar') : null;
        $course->zoom_attendance_duration_minutes = (int) $validated['duration_minutes'];
        if ($course->zoom_attendance_scheduled_at) {
            $course->zoom_attendance_closed_at = null;
            $course->zoom_attendance_opened_at = null;
            $course->is_zoom_attendance_open = false;
        }
        $course->save();

        $message = $course->zoom_attendance_scheduled_at
            ? "Jadwal absensi otomatis berhasil disetel untuk {$course->zoom_attendance_scheduled_at->format('d M Y, H:i')} (Durasi: {$course->zoom_attendance_duration_minutes} menit)."
            : "Jadwal absensi otomatis telah dinonaktifkan.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_attendance_scheduled_at' => $course->zoom_attendance_scheduled_at,
                'zoom_attendance_duration_minutes' => $course->zoom_attendance_duration_minutes,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Update Online Meeting session schedule (Start & End Time, Link, ID, Passcode).
     */
    public function updateZoomSchedule(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'zoom_start_at' => 'nullable|date',
            'zoom_end_at' => 'nullable|date',
            'zoom_link' => 'nullable|string|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
        ]);

        if (empty($validated['zoom_start_at'])) {
            $validated['zoom_start_at'] = null;
        }
        if (empty($validated['zoom_end_at'])) {
            $validated['zoom_end_at'] = null;
        }

        $course->update($validated);

        $message = "Jadwal dan tautan Online Meeting untuk kelas '{$course->title}' berhasil diperbarui.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_start_at' => $course->zoom_start_at,
                'zoom_end_at' => $course->zoom_end_at,
                'zoom_status' => $course->zoom_status,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Start Online Meeting session immediately.
     */
    public function startZoomNow(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $course->zoom_start_at = now();
        if (!$course->zoom_end_at || now()->greaterThan($course->zoom_end_at)) {
            $course->zoom_end_at = now()->addHours(2);
        }
        $course->save();

        $message = "Sesi Online Meeting kelas '{$course->title}' telah DIMULAI sekarang (Live hingga {$course->zoom_end_at->format('H:i')}). Peserta kini dapat bergabung ke Online Meeting.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_start_at' => $course->zoom_start_at,
                'zoom_end_at' => $course->zoom_end_at,
                'zoom_status' => $course->zoom_status,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * End Online Meeting session immediately.
     */
    public function endZoomNow(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $course->zoom_end_at = now();
        // Also close attendance if currently open
        if ($course->is_zoom_attendance_open) {
            $course->is_zoom_attendance_open = false;
            $course->zoom_attendance_closed_at = now();
        }
        $course->save();

        $message = "Sesi Online Meeting kelas '{$course->title}' telah DIAKHIRI. Jalur 2 (Belajar Mandiri Susulan) kini terbuka bagi peserta yang terlambat/susulan.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_end_at' => $course->zoom_end_at,
                'zoom_status' => $course->zoom_status,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Admin manual attendance override for a participant.
     */
    public function manualAttendance(Request $request, Course $course, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_path' => 'required|in:live_zoom,self_study',
        ]);

        $enrollment->completeAndIssueCertificate($validated['attendance_path']);

        $name = $enrollment->participant->name ?? 'Peserta';
        return back()->with('success', "Status kehadiran peserta '{$name}' berhasil diperbarui menjadi 'Sudah Mengikuti' dan sertifikat telah diterbitkan.");
    }

    /**
     * Get live monitoring data for the course.
     */
    public function monitoringData(Course $course): JsonResponse
    {
        $course->load([
            'enrollments' => function ($q) {
                $q->with('participant')->orderByDesc('id');
            },
        ]);

        $total = $course->enrollments->count();
        $completed = $course->enrollments->where('status', 'completed')->count();
        $liveZoom = $course->enrollments->where('attendance_path', 'live_zoom')->count();
        $selfStudy = $course->enrollments->where('attendance_path', 'self_study')->count();
        $pending = $total - $completed;

        return response()->json([
            'is_zoom_attendance_open' => $course->is_attendance_open_now,
            'zoom_attendance_opened_at' => $course->zoom_attendance_opened_at,
            'zoom_attendance_closed_at' => $course->zoom_attendance_closed_at,
            'zoom_attendance_duration_minutes' => $course->zoom_attendance_duration_minutes,
            'zoom_attendance_scheduled_at' => $course->zoom_attendance_scheduled_at,
            'attendance_remaining_seconds' => $course->attendance_remaining_seconds,
            'zoom_start_at' => $course->zoom_start_at,
            'zoom_end_at' => $course->zoom_end_at,
            'zoom_status' => $course->zoom_status,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'pending' => $pending,
                'live_zoom' => $liveZoom,
                'self_study' => $selfStudy,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ],
            'enrollments' => $course->enrollments->map(function ($e) {
                return [
                    'id' => $e->id,
                    'participant_name' => $e->participant->name,
                    'participant_email' => $e->participant->email,
                    'participant_nik' => $e->participant->nik,
                    'status' => $e->status,
                    'attendance_path' => $e->attendance_path,
                    'attendance_at' => $e->attendance_at ? $e->attendance_at->format('d M Y, H:i') : null,
                    'progress_percentage' => (float) $e->progress_percentage,
                    'certificate_number' => $e->certificate_number,
                    'certificate_hash' => $e->certificate_hash,
                ];
            }),
        ]);
    }

    /**
     * Update unit-level Online Meeting schedule & credentials.
     */
    public function updateUnitZoomSchedule(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'day_number' => 'nullable|integer|min:1|max:365',
            'scheduled_date' => 'nullable|date',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'zoom_start_at' => 'nullable|date',
            'zoom_end_at' => 'nullable|date',
            'zoom_link' => 'nullable|string|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
        ]);

        // If scheduled_date and start_time / end_time provided, build zoom_start_at and zoom_end_at
        if (! empty($validated['scheduled_date']) && ! empty($validated['start_time'])) {
            try {
                $validated['zoom_start_at'] = Carbon::parse("{$validated['scheduled_date']} {$validated['start_time']}", 'Asia/Makassar');
            } catch (\Throwable $e) {
            }
        }
        if (! empty($validated['scheduled_date']) && ! empty($validated['end_time'])) {
            try {
                $validated['zoom_end_at'] = Carbon::parse("{$validated['scheduled_date']} {$validated['end_time']}", 'Asia/Makassar');
            } catch (\Throwable $e) {
            }
        }

        // Conversely, if zoom_start_at / zoom_end_at provided directly:
        if (! empty($validated['zoom_start_at']) && empty($validated['scheduled_date'])) {
            try {
                $dt = Carbon::parse($validated['zoom_start_at'], 'Asia/Makassar');
                $validated['scheduled_date'] = $dt->toDateString();
                $validated['start_time'] = $dt->format('H:i');
            } catch (\Throwable $e) {
            }
        }
        if (! empty($validated['zoom_end_at']) && empty($validated['end_time'])) {
            try {
                $dt = Carbon::parse($validated['zoom_end_at'], 'Asia/Makassar');
                $validated['end_time'] = $dt->format('H:i');
            } catch (\Throwable $e) {
            }
        }

        if (empty($validated['scheduled_date'])) {
            $validated['scheduled_date'] = null;
        }

        $module->update($validated);

        // SYNC TO COURSE if course is single-day, or this is module 1, or this is today's module
        $course = $module->course;
        $today = now('Asia/Makassar')->toDateString();
        $isTodayModule = ($module->scheduled_date && Carbon::parse($module->scheduled_date)->toDateString() === $today) || $module->day_number === 1;

        if ($isTodayModule || $course->duration_in_days <= 1) {
            if (! empty($module->zoom_link)) {
                $course->zoom_link = $module->zoom_link;
            }
            if (! empty($module->zoom_meeting_id)) {
                $course->zoom_meeting_id = $module->zoom_meeting_id;
            }
            if (! empty($module->zoom_passcode)) {
                $course->zoom_passcode = $module->zoom_passcode;
            }
            if (! empty($module->zoom_start_at)) {
                $course->zoom_start_at = $module->zoom_start_at;
            }
            if (! empty($module->zoom_end_at)) {
                $course->zoom_end_at = $module->zoom_end_at;
            }
            $course->save();
        }

        $message = "Jadwal & tautan Online Meeting untuk Unit '{$module->title}' berhasil disimpan.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'module' => $module,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Start Online Meeting session immediately for a specific unit.
     */
    public function startUnitZoomNow(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $module->zoom_status = 'live';
        $module->zoom_start_at = now();
        if (! $module->zoom_end_at || now()->greaterThan($module->zoom_end_at)) {
            $module->zoom_end_at = now()->addHours(2);
        }
        $module->save();

        $course = $module->course;
        if ($module->zoom_link) {
            $course->zoom_link = $module->zoom_link;
        }
        if ($module->zoom_meeting_id) {
            $course->zoom_meeting_id = $module->zoom_meeting_id;
        }
        if ($module->zoom_passcode) {
            $course->zoom_passcode = $module->zoom_passcode;
        }
        $course->zoom_start_at = $module->zoom_start_at;
        $course->zoom_end_at = $module->zoom_end_at;
        $course->save();

        $message = "Sesi Online Meeting Unit '{$module->title}' (Hari ke-{$module->day_number}) telah DIMULAI (Status: LIVE).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_status' => 'live',
                'zoom_start_at' => $module->zoom_start_at,
                'zoom_end_at' => $module->zoom_end_at,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * End Online Meeting session immediately for a specific unit.
     */
    public function endUnitZoomNow(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $module->zoom_status = 'ended';
        $module->zoom_end_at = now();
        if ($module->is_attendance_open_now) {
            $module->zoom_attendance_closed_at = now();
        }
        $module->save();

        $course = $module->course;
        $course->zoom_end_at = now();
        if ($course->is_attendance_open_now) {
            $course->is_zoom_attendance_open = false;
            $course->zoom_attendance_closed_at = now();
        }
        $course->save();

        $message = "Sesi Online Meeting Unit '{$module->title}' (Hari ke-{$module->day_number}) telah DIAKHIRI.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_status' => 'ended',
                'zoom_end_at' => $module->zoom_end_at,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Open attendance session for a specific unit now with preset/custom duration.
     */
    public function openUnitAttendance(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $duration = (int) ($validated['duration_minutes'] ?? 30);
        $module->zoom_attendance_opened_at = now();
        $module->zoom_attendance_duration_minutes = $duration;
        $module->zoom_attendance_closed_at = now()->addMinutes($duration);
        $module->save();

        // Also sync to course so student classroom gets attendance trigger immediately
        $course = $module->course;
        $course->is_zoom_attendance_open = true;
        $course->zoom_attendance_opened_at = $module->zoom_attendance_opened_at;
        $course->zoom_attendance_duration_minutes = $duration;
        $course->zoom_attendance_closed_at = $module->zoom_attendance_closed_at;
        $course->save();

        $message = "Sesi Absensi Online Meeting untuk Unit '{$module->title}' berhasil DIBUKA selama {$duration} menit (hingga {$module->zoom_attendance_closed_at->format('H:i')}).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_attendance_open_now' => true,
                'opened_at' => $module->zoom_attendance_opened_at,
                'closed_at' => $module->zoom_attendance_closed_at,
                'duration_minutes' => $duration,
                'remaining_seconds' => $module->attendance_remaining_seconds,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Close attendance session for a specific unit.
     */
    public function closeUnitAttendance(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $module->zoom_attendance_closed_at = now();
        $module->zoom_attendance_scheduled_at = null;
        $module->save();

        $course = $module->course;
        $course->is_zoom_attendance_open = false;
        $course->zoom_attendance_closed_at = now();
        $course->zoom_attendance_scheduled_at = null;
        $course->save();

        $message = "Sesi Absensi Online Meeting untuk Unit '{$module->title}' telah DITUTUP.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_attendance_open_now' => false,
                'closed_at' => $module->zoom_attendance_closed_at,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Schedule attendance session for a specific unit.
     */
    public function scheduleUnitAttendance(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        if (! $module->course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'duration_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $module->zoom_attendance_scheduled_at = ! empty($validated['scheduled_at']) ? Carbon::parse($validated['scheduled_at'], 'Asia/Makassar') : null;
        $module->zoom_attendance_duration_minutes = (int) $validated['duration_minutes'];
        if ($module->zoom_attendance_scheduled_at) {
            $module->zoom_attendance_closed_at = null;
            $module->zoom_attendance_opened_at = null;
        }
        $module->save();

        $course = $module->course;
        $course->zoom_attendance_scheduled_at = $module->zoom_attendance_scheduled_at;
        $course->zoom_attendance_duration_minutes = $module->zoom_attendance_duration_minutes;
        if ($course->zoom_attendance_scheduled_at) {
            $course->zoom_attendance_closed_at = null;
            $course->zoom_attendance_opened_at = null;
            $course->is_zoom_attendance_open = false;
        }
        $course->save();

        $message = $module->zoom_attendance_scheduled_at
            ? "Jadwal absensi otomatis Online Meeting Unit '{$module->title}' berhasil disetel untuk {$module->zoom_attendance_scheduled_at->format('d M Y, H:i')} (Durasi: {$module->zoom_attendance_duration_minutes} menit)."
            : "Jadwal absensi otomatis Online Meeting Unit '{$module->title}' telah dinonaktifkan.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'zoom_attendance_scheduled_at' => $module->zoom_attendance_scheduled_at,
                'zoom_attendance_duration_minutes' => $module->zoom_attendance_duration_minutes,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Toggle participant's attendance checkmark for a specific module/day.
     */
    public function toggleModuleAttendance(Request $request, Course $course, Enrollment $enrollment, Module $module): JsonResponse|RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $existing = ModuleAttendance::where('enrollment_id', $enrollment->id)
            ->where('module_id', $module->id)
            ->first();

        $path = $request->input('path');

        if ($request->has('path')) {
            if ($path === 'none' || $path === 'delete' || empty($path)) {
                if ($existing) {
                    $existing->delete();
                }
                $attended = false;
                $activePath = null;
                $message = "Presensi Hari ke-{$module->day_number} ({$module->title}) untuk {$enrollment->participant->name} dibatalkan.";
            } else {
                $targetPath = $path === 'self_study' ? 'self_study' : 'live_zoom';
                if ($existing) {
                    $existing->update([
                        'attendance_path' => $targetPath,
                        'attended_at' => now(),
                    ]);
                } else {
                    ModuleAttendance::create([
                        'course_id' => $course->id,
                        'module_id' => $module->id,
                        'enrollment_id' => $enrollment->id,
                        'participant_id' => $enrollment->participant_id,
                        'day_number' => $module->day_number ?? 1,
                        'attendance_path' => $targetPath,
                        'attended_at' => now(),
                    ]);
                }
                $attended = true;
                $activePath = $targetPath;
                $label = $targetPath === 'live_zoom' ? 'Online Meeting' : 'Belajar Mandiri';
                $message = "Presensi Hari ke-{$module->day_number} ({$module->title}) untuk {$enrollment->participant->name} diset: {$label}.";
            }
        } else {
            // Cycle: Belum Hadir -> Online Meeting -> Belajar Mandiri -> Belum Hadir
            if (! $existing) {
                ModuleAttendance::create([
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'enrollment_id' => $enrollment->id,
                    'participant_id' => $enrollment->participant_id,
                    'day_number' => $module->day_number ?? 1,
                    'attendance_path' => 'live_zoom',
                    'attended_at' => now(),
                ]);
                $attended = true;
                $activePath = 'live_zoom';
                $message = "Presensi Hari ke-{$module->day_number} ({$module->title}) untuk {$enrollment->participant->name} dicatat: Hadir Online Meeting.";
            } elseif ($existing->attendance_path === 'live_zoom') {
                $existing->update([
                    'attendance_path' => 'self_study',
                    'attended_at' => now(),
                ]);
                $attended = true;
                $activePath = 'self_study';
                $message = "Presensi Hari ke-{$module->day_number} ({$module->title}) untuk {$enrollment->participant->name} dialihkan: Belajar Mandiri (Terlambat).";
            } else {
                $existing->delete();
                $attended = false;
                $activePath = null;
                $message = "Presensi Hari ke-{$module->day_number} ({$module->title}) untuk {$enrollment->participant->name} dibatalkan (Belum Hadir).";
            }
        }

        // Synchronize enrollment overall attendance_path
        $anyLiveZoom = ModuleAttendance::where('enrollment_id', $enrollment->id)
            ->where('attendance_path', 'live_zoom')
            ->exists();
        $anySelfStudy = ModuleAttendance::where('enrollment_id', $enrollment->id)
            ->where('attendance_path', 'self_study')
            ->exists();

        if ($anyLiveZoom) {
            $enrollment->attendance_path = 'live_zoom';
            if (empty($enrollment->attendance_at)) {
                $enrollment->attendance_at = now();
            }
        } elseif ($anySelfStudy) {
            $enrollment->attendance_path = 'self_study';
            if (empty($enrollment->attendance_at)) {
                $enrollment->attendance_at = now();
            }
        } else {
            $enrollment->attendance_path = 'none';
        }
        $enrollment->save();

        $totalModules = $course->modules()->count();
        $attendedCount = ModuleAttendance::where('enrollment_id', $enrollment->id)->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'attended' => $attended,
                'attendance_path' => $activePath,
                'attended_count' => $attendedCount,
                'total_modules' => $totalModules,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
