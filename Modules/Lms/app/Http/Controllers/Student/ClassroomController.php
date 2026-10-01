<?php

namespace Modules\Lms\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Lesson;
use Modules\Lms\Models\LessonProgress;
use Modules\Lms\Models\Participant;
use Modules\Lms\Models\Quiz;
use Modules\Lms\Models\QuizAttempt;

class ClassroomController extends Controller
{
    /**
     * Show LMS Classroom interface (Dual-Path Zoom + Self-Study Modules).
     */
    public function show(Request $request, Course $course): Response|RedirectResponse
    {
        $participantId = session('lms_participant_id');
        if (!$participantId) {
            return redirect()->route('lms.student.login')
                ->with('warning', 'Silakan masuk dengan email Anda terlebih dahulu.');
        }

        $participant = Participant::find($participantId);
        if (!$participant) {
            session()->forget('lms_participant_id');
            return redirect()->route('lms.student.login');
        }

        // Get enrollment
        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$enrollment) {
            return redirect()->route('lms.student.dashboard')
                ->with('error', 'Anda tidak terdaftar dalam kelas pelatihan ini.');
        }

        if ($course->status === 'draft') {
            return redirect()->route('lms.student.dashboard')
                ->with('warning', 'Kelas pelatihan ini masih dalam status draft dan belum dibuka oleh admin.');
        }

        // Load modules, lessons, and active quizzes with questions
        $course->load([
            'modules' => function ($q) {
                $q->with([
                    'lessons' => function ($l) {
                        $l->orderBy('order_index');
                    },
                    'quiz' => function ($qz) {
                        $qz->where('is_active', true)
                            ->with(['questions' => function ($qu) {
                                $qu->select('id', 'quiz_id', 'question_text', 'question_type', 'options', 'points', 'order_index')
                                    ->orderBy('order_index');
                            }]);
                    },
                    'quizzes' => function ($qz) {
                        $qz->where('is_active', true)
                            ->with(['questions' => function ($qu) {
                                $qu->select('id', 'quiz_id', 'question_text', 'question_type', 'options', 'points', 'order_index')
                                    ->orderBy('order_index');
                            }]);
                    },
                ])->orderBy('day_number')->orderBy('order_index');
            },
        ]);

        // Get list of completed lesson IDs for this enrollment
        $completedLessonIds = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('is_completed', true)
            ->pluck('lesson_id')
            ->toArray();

        // Get student's quiz attempts for this enrollment
        $quizAttempts = QuizAttempt::where('enrollment_id', $enrollment->id)
            ->get()
            ->keyBy('quiz_id');

        // Recalculate progress to ensure precision
        $progressPercentage = $enrollment->recalculateProgress();

        // 1-day vs Multi-day and daily schedule info (Asia/Makassar)
        $durationDays = $course->duration_in_days;
        $isSingleDay = ($course->start_date && $course->end_date && $course->start_date->equalTo($course->end_date)) || $durationDays <= 1;

        $today = now('Asia/Makassar')->toDateString();
        $tomorrow = now('Asia/Makassar')->addDay()->toDateString();

        $todayDayNumber = 1;
        if ($course->start_date) {
            $diffDays = Carbon::parse($course->start_date, 'Asia/Makassar')->startOfDay()->diffInDays(now('Asia/Makassar')->startOfDay(), false) + 1;
            $todayDayNumber = max(1, (int) $diffDays);
        }

        $modules = $course->modules;
        $todayModule = $modules->first(function ($m, $idx) use ($today, $todayDayNumber) {
            $mDay = $m->day_number ?: ($idx + 1);
            return ($mDay === $todayDayNumber) || ($m->scheduled_date && $m->scheduled_date->toDateString() === $today);
        });
        if (! $todayModule && $isSingleDay && $modules->isNotEmpty()) {
            $todayModule = $modules->first();
        }

        $tomorrowModule = $modules->first(function ($m) use ($tomorrow) {
            return $m->scheduled_date && $m->scheduled_date->toDateString() === $tomorrow;
        });
        $nextUpcomingModule = $modules->first(function ($m) use ($today) {
            return $m->scheduled_date && $m->scheduled_date->toDateString() > $today;
        });
        if (! $tomorrowModule && ! $nextUpcomingModule && $modules->count() > 1) {
            $nextUpcomingModule = $modules->get(1);
        }

        $enrollment->load('moduleAttendances');
        $attendedModuleIds = $enrollment->moduleAttendances->pluck('module_id')->toArray();
        $attendedDayNumbers = $enrollment->moduleAttendances->pluck('day_number')->toArray();

        if ($isSingleDay) {
            $hasAttendedToday = !empty($enrollment->attendance_at) || $enrollment->moduleAttendances->isNotEmpty();
        } else {
            $hasAttendedToday = $todayModule
                ? (in_array($todayModule->id, $attendedModuleIds) || in_array($todayDayNumber, $attendedDayNumbers))
                : in_array($todayDayNumber, $attendedDayNumbers);
        }

        $hasAttendedLiveZoom = ($enrollment->attendance_path === 'live_zoom')
            || $enrollment->moduleAttendances->where('attendance_path', 'live_zoom')->isNotEmpty();

        $hasAttendedSelfStudy = ($enrollment->attendance_path === 'self_study')
            || $enrollment->moduleAttendances->where('attendance_path', 'self_study')->isNotEmpty();

        $todayAttendance = $todayModule ? $enrollment->moduleAttendances->firstWhere('module_id', $todayModule->id) : null;
        $todayAttendancePath = $todayAttendance ? $todayAttendance->attendance_path : ($enrollment->attendance_path ?: 'none');

        $dailySchedule = [
            'is_single_day' => $isSingleDay,
            'duration_days' => $durationDays,
            'current_day_number' => $todayDayNumber,
            'today_date' => $today,
            'today_module' => $todayModule,
            'tomorrow_module' => $tomorrowModule ?? $nextUpcomingModule,
            'today_mode' => $todayModule ? $todayModule->delivery_mode : ($course->zoom_start_at ? 'sinkronus' : 'asinkronus'),
            'today_zoom_time' => $todayModule && $todayModule->start_time
                ? "{$todayModule->start_time}" . ($todayModule->end_time ? " - {$todayModule->end_time}" : "") . " WITA"
                : ($todayModule && $todayModule->zoom_start_at
                    ? $todayModule->zoom_start_at->format('H:i') . ($todayModule->zoom_end_at ? ' - ' . $todayModule->zoom_end_at->format('H:i') : '') . ' WITA'
                    : ($course->zoom_start_at ? $course->zoom_start_at->format('H:i') . ($course->zoom_end_at ? ' - ' . $course->zoom_end_at->format('H:i') : '') . ' WITA' : null)),
            'today_notes' => $todayModule ? $todayModule->notes : null,
            'tomorrow_zoom_time' => ($tomorrowModule ?? $nextUpcomingModule) && ($tomorrowModule ?? $nextUpcomingModule)->delivery_mode === 'sinkronus' ? (($tomorrowModule ?? $nextUpcomingModule)->start_time . ' - ' . ($tomorrowModule ?? $nextUpcomingModule)->end_time . ' WITA') : null,
            'tomorrow_mode' => ($tomorrowModule ?? $nextUpcomingModule) ? ($tomorrowModule ?? $nextUpcomingModule)->delivery_mode : null,
            'tomorrow_notes' => ($tomorrowModule ?? $nextUpcomingModule) ? ($tomorrowModule ?? $nextUpcomingModule)->notes : null,
            'attended_module_ids' => $attendedModuleIds,
            'attended_day_numbers' => $attendedDayNumbers,
            'has_attended_today' => $hasAttendedToday,
            'has_attended_zoom' => $hasAttendedLiveZoom,
            'has_attended_self_study' => $hasAttendedSelfStudy,
            'today_attendance_path' => $todayAttendancePath,
        ];

        // Sync active unit meeting & attendance properties to course for student view
        if ($todayModule) {
            if ($todayModule->zoom_link) {
                $course->zoom_link = $todayModule->zoom_link;
            }
            if ($todayModule->zoom_meeting_id) {
                $course->zoom_meeting_id = $todayModule->zoom_meeting_id;
            }
            if ($todayModule->zoom_passcode) {
                $course->zoom_passcode = $todayModule->zoom_passcode;
            }
            if ($todayModule->zoom_start_at) {
                $course->zoom_start_at = $todayModule->zoom_start_at;
            } elseif ($todayModule->start_time) {
                try {
                    $schedDate = $todayModule->scheduled_date ? $todayModule->scheduled_date->toDateString() : $today;
                    $course->zoom_start_at = Carbon::parse("{$schedDate} {$todayModule->start_time}", 'Asia/Makassar');
                } catch (\Throwable $e) {
                }
            }
            if ($todayModule->zoom_end_at) {
                $course->zoom_end_at = $todayModule->zoom_end_at;
            } elseif ($todayModule->end_time) {
                try {
                    $schedDate = $todayModule->scheduled_date ? $todayModule->scheduled_date->toDateString() : $today;
                    $course->zoom_end_at = Carbon::parse("{$schedDate} {$todayModule->end_time}", 'Asia/Makassar');
                } catch (\Throwable $e) {
                }
            }
            if ($todayModule->zoom_status) {
                $course->zoom_status = $todayModule->zoom_status;
            }
            if ($todayModule->is_attendance_open_now) {
                $course->is_zoom_attendance_open = true;
                $course->zoom_attendance_opened_at = $todayModule->zoom_attendance_opened_at ?: now();
                $course->zoom_attendance_closed_at = $todayModule->zoom_attendance_closed_at;
                $course->zoom_attendance_duration_minutes = $todayModule->zoom_attendance_duration_minutes;
            }
        }

        return Inertia::render('Lms::Student/Classroom', [
            'course' => $course,
            'enrollment' => $enrollment,
            'participant' => $participant,
            'completedLessonIds' => $completedLessonIds,
            'quizAttempts' => $quizAttempts,
            'progressPercentage' => $progressPercentage,
            'dailySchedule' => $dailySchedule,
        ]);
    }

    /**
     * Mark a specific lesson as completed by participant.
     */
    public function markLessonComplete(Request $request, Lesson $lesson): JsonResponse
    {
        $participantId = session('lms_participant_id');
        if (!$participantId) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $enrollment = Enrollment::where('course_id', $lesson->course_id)
            ->where('participant_id', $participantId)
            ->first();

        if (!$enrollment) {
            return response()->json(['error' => 'Enrollment not found'], 403);
        }

        $progress = LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
            ]
        );

        if (!$progress->is_completed) {
            $progress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        $newPercentage = $enrollment->recalculateProgress();

        return response()->json([
            'success' => true,
            'progress_percentage' => $newPercentage,
            'is_all_completed' => $newPercentage >= 100.0,
        ]);
    }

    /**
     * Submit attendance via Jalur 1 (Live Zoom) or Jalur 2 (Belajar Mandiri / Susulan).
     */
    public function submitAttendance(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $participantId = session('lms_participant_id');
        if (!$participantId) {
            return redirect()->route('lms.student.login');
        }

        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('participant_id', $participantId)
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'Pendaftaran kelas tidak ditemukan.');
        }

        if ($enrollment->status === 'completed') {
            return back()->with('info', 'Anda sudah menyelesaikan pembelajaran untuk kelas ini.');
        }

        $path = $request->input('path', 'live_zoom'); // 'live_zoom', 'self_study_checkin', 'complete_course', etc.
        $action = $request->input('action');

        $durationDays = $course->duration_in_days;
        $isSingleDay = ($course->start_date && $course->end_date && $course->start_date->equalTo($course->end_date)) || $durationDays <= 1;

        // Detect course completion request:
        // 1. Explicit action or path = 'complete_course' / 'complete'
        // 2. Legacy 'self_study' without checkin action
        // 3. Sent as 'live_zoom' while already attended and progress is >= 100%
        $isCompletionRequest = $action === 'complete_course'
            || $action === 'complete'
            || in_array($path, ['complete_course', 'complete'])
            || ($path === 'self_study' && $action !== 'checkin');

        if (! $isCompletionRequest && $path === 'live_zoom') {
            $hasAnyAttendance = ! empty($enrollment->attendance_at) || $enrollment->moduleAttendances()->exists();
            if ($hasAnyAttendance && (float) $enrollment->progress_percentage >= 100.0) {
                $isCompletionRequest = true;
            }
        }

        if ($isCompletionRequest) {
            if ($isSingleDay) {
                $hasAttendedAny = ! empty($enrollment->attendance_at) || $enrollment->moduleAttendances()->exists();
                if (! $hasAttendedAny && ! $course->is_self_study_unlocked) {
                    return back()->with('error', 'Pelatihan 1 hari wajib melakukan presensi di Jalur 1 (Status Pembelajaran & Absensi) terlebih dahulu.');
                }
            }

            $totalLessons = Lesson::where('course_id', $course->id)->count();
            if ($totalLessons === 0) {
                return back()->with('error', 'Belum ada modul materi pelajaran yang dapat diselesaikan pada kelas ini.');
            }

            $currentProgress = $enrollment->recalculateProgress();
            if ($currentProgress < 100.0) {
                return back()->with('error', "Anda belum menyelesaikan seluruh materi pelajaran (Progress saat ini: {$currentProgress}%). Silakan selesaikan semua materi terlebih dahulu.");
            }

            // Preserve live_zoom if student attended online meeting
            $hasLiveZoom = ($enrollment->attendance_path === 'live_zoom')
                || $enrollment->moduleAttendances()->where('attendance_path', 'live_zoom')->exists();

            $effectivePath = $hasLiveZoom ? 'live_zoom' : ($enrollment->attendance_path ?: 'self_study');

            $enrollment->completeAndIssueCertificate($effectivePath);

            $successMsg = 'Selamat! Anda telah menyelesaikan seluruh pembelajaran (100%). Sertifikat kelulusan resmi Anda telah diterbitkan!';

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'certificate_number' => $enrollment->certificate_number,
                    'certificate_hash' => $enrollment->certificate_hash,
                ]);
            }

            return back()->with('success', $successMsg);
        }

        if ($path === 'live_zoom') {
            $isCourseOpen = $course->is_attendance_open_now;
            $openModule = $course->modules()->get()->first(function ($m) {
                return $m->is_attendance_open_now;
            });

            if (! $isCourseOpen && ! $openModule) {
                return back()->with('error', 'Sesi absensi online saat ini belum dibuka oleh Instruktur/Admin atau batas waktu telah berakhir.');
            }

            $today = now('Asia/Makassar')->toDateString();
            $todayDayNumber = 1;
            if ($course->start_date) {
                $diffDays = Carbon::parse($course->start_date, 'Asia/Makassar')->startOfDay()->diffInDays(now('Asia/Makassar')->startOfDay(), false) + 1;
                $todayDayNumber = max(1, (int) $diffDays);
            }

            $allModules = $course->modules()->orderBy('day_number')->orderBy('order_index')->get();
            $todayModule = $allModules->first(function ($m, $idx) use ($today, $todayDayNumber) {
                $mDay = $m->day_number ?: ($idx + 1);

                return ($mDay === $todayDayNumber) || ($m->scheduled_date && $m->scheduled_date->toDateString() === $today);
            });

            $targetModule = $openModule ?: ($todayModule ?: $allModules->first());
            $targetDayNumber = $targetModule ? ($targetModule->day_number ?? $todayDayNumber) : $todayDayNumber;

            if ($targetModule) {
                \Modules\Lms\Models\ModuleAttendance::firstOrCreate([
                    'course_id' => $course->id,
                    'module_id' => $targetModule->id,
                    'enrollment_id' => $enrollment->id,
                ], [
                    'participant_id' => $enrollment->participant_id,
                    'day_number' => $targetDayNumber,
                    'attendance_path' => 'live_zoom',
                    'attended_at' => now(),
                ]);
            }

            $enrollment->attendance_path = 'live_zoom';
            $enrollment->attendance_at = now();
            if ($enrollment->status === 'enrolled') {
                $enrollment->status = 'in_progress';
            }
            $enrollment->save();

            $msg = $isSingleDay
                ? 'Presensi sesi Live Online Meeting berhasil dicatat! Jalur 2 (Materi & Evaluasi) telah dibuka. Silakan selesaikan seluruh materi dan kuis di Jalur 2 untuk menerbitkan sertifikat kelulusan.'
                : "Presensi Hari Ke-{$targetDayNumber} berhasil dicatat! Silakan lanjutkan pembelajaran Anda.";

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'has_attended' => true,
                ]);
            }

            return back()->with('success', $msg);
        } elseif ($path === 'self_study_checkin') {
            // Find today's module / target module
            $today = now('Asia/Makassar')->toDateString();
            $todayDayNumber = 1;
            if ($course->start_date) {
                $diffDays = Carbon::parse($course->start_date, 'Asia/Makassar')->startOfDay()->diffInDays(now('Asia/Makassar')->startOfDay(), false) + 1;
                $todayDayNumber = max(1, (int) $diffDays);
            }

            $allModules = $course->modules()->orderBy('day_number')->orderBy('order_index')->get();
            $todayModule = $allModules->first(function ($m, $idx) use ($today, $todayDayNumber) {
                $mDay = $m->day_number ?: ($idx + 1);

                return ($mDay === $todayDayNumber) || ($m->scheduled_date && $m->scheduled_date->toDateString() === $today);
            });

            $targetModule = $todayModule ?: $allModules->first();
            $targetDayNumber = $targetModule ? ($targetModule->day_number ?? $todayDayNumber) : $todayDayNumber;

            // If online meeting was scheduled today and hasn't ended yet, prevent premature self-study checkin
            $isScheduled = false;
            $hasEnded = false;
            if ($todayModule) {
                $isScheduled = (bool) ($todayModule->zoom_start_at || ($todayModule->scheduled_date && $todayModule->start_time));
                $hasEnded = ($todayModule->zoom_status === 'ended');
            } elseif ($course->zoom_start_at) {
                $isScheduled = true;
                $hasEnded = ($course->zoom_status === 'ended');
            }

            if ($isScheduled && ! $hasEnded) {
                return back()->with('warning', 'Presensi susulan mandiri baru dapat dilakukan setelah waktu sesi tatap muka online yang dijadwalkan telah selesai.');
            }

            if ($targetModule) {
                \Modules\Lms\Models\ModuleAttendance::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'module_id' => $targetModule->id,
                        'enrollment_id' => $enrollment->id,
                    ],
                    [
                        'participant_id' => $enrollment->participant_id,
                        'day_number' => $targetDayNumber,
                        'attendance_path' => 'self_study',
                        'attended_at' => now(),
                    ]
                );
            }

            // Keep live_zoom if already attended online, otherwise set to self_study
            $hasLiveZoom = \Modules\Lms\Models\ModuleAttendance::where('enrollment_id', $enrollment->id)
                ->where('attendance_path', 'live_zoom')
                ->exists();

            if (! $hasLiveZoom) {
                $enrollment->attendance_path = 'self_study';
            }
            if (empty($enrollment->attendance_at)) {
                $enrollment->attendance_at = now();
            }
            if ($enrollment->status === 'enrolled') {
                $enrollment->status = 'in_progress';
            }
            $enrollment->save();

            $msg = $isSingleDay
                ? 'Presensi Belajar Mandiri (Susulan) berhasil dicatat! Jalur 2 (Materi & Evaluasi) telah dibuka. Silakan pelajari unit materi dan selesaikan kuis.'
                : "Presensi Belajar Mandiri Hari Ke-{$targetDayNumber} berhasil dicatat! Silakan lanjutkan pembelajaran Anda di Jalur 2.";

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'has_attended' => true,
                    'attendance_path' => 'self_study',
                ]);
            }

            return back()->with('success', $msg);
        } else {
            return back()->with('error', 'Metode presensi tidak valid.');
        }
    }

    /**
     * Submit student answers for a module quiz.
     */
    public function submitQuiz(Request $request, Quiz $quiz): JsonResponse
    {
        $participantId = session('lms_participant_id');
        if (!$participantId) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $enrollment = Enrollment::where('course_id', $quiz->course_id)
            ->where('participant_id', $participantId)
            ->first();

        if (!$enrollment) {
            return response()->json(['error' => 'Pendaftaran kelas tidak ditemukan.'], 403);
        }

        $submittedAnswers = $request->input('answers', []); // [question_id => "A"]
        $questions = $quiz->questions()->get();

        if ($questions->isEmpty()) {
            return response()->json(['error' => 'Kuis belum memiliki butir soal.'], 422);
        }

        $totalQuestions = $questions->count();
        $correctCount = 0;
        $feedback = [];

        foreach ($questions as $q) {
            $userAns = $submittedAnswers[$q->id] ?? null;
            $isCorrect = ($userAns !== null && strtoupper(trim((string) $userAns)) === strtoupper(trim((string) $q->correct_answer)));

            if ($isCorrect) {
                $correctCount++;
            }

            $feedback[] = [
                'question_id' => $q->id,
                'user_answer' => $userAns,
                'is_correct' => $isCorrect,
                'correct_answer' => $q->correct_answer,
                'explanation' => $q->explanation,
            ];
        }

        $calculatedScore = round(($correctCount / $totalQuestions) * 100, 1);
        $isPassed = $calculatedScore >= $quiz->passing_score;

        // Save or update attempt
        $attempt = QuizAttempt::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'enrollment_id' => $enrollment->id,
                'participant_id' => $participantId,
            ],
            [
                'score' => $calculatedScore,
                'is_passed' => $isPassed,
                'answers' => $submittedAnswers,
                'completed_at' => now(),
            ]
        );

        $newPercentage = $enrollment->recalculateProgress();

        return response()->json([
            'success' => true,
            'score' => $calculatedScore,
            'is_passed' => $isPassed,
            'passing_score' => $quiz->passing_score,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'feedback' => $feedback,
            'progress_percentage' => $newPercentage,
            'attempt' => $attempt,
        ]);
    }
}
