<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Participant;

class AllParticipantsController extends Controller
{
    /**
     * Display a master listing of all participants across the entire LMS (Superadmin only).
     */
    public function index(Request $request): Response
    {
        $query = Participant::query()
            ->with([
                'user:id,name,email,role',
            ])
            ->withCount([
                'enrollments as total_enrollments_count',
                'enrollments as completed_enrollments_count' => function ($q) {
                    $q->where('status', 'completed');
                },
                'enrollments as in_progress_enrollments_count' => function ($q) {
                    $q->where('status', 'in_progress');
                },
                'enrollments as certified_enrollments_count' => function ($q) {
                    $q->whereNotNull('certificate_number');
                },
            ]);

        // Search Filter (name, email, nik, phone, agency, transaction code)
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('agency_or_institution', 'like', "%{$search}%")
                    ->orWhere('training_transaction_code', 'like', "%{$search}%");
            });
        }

        // Participation / Status Filter
        $filter = $request->input('filter', 'all');
        if ($filter === 'multiple') {
            $query->has('enrollments', '>', 1);
        } elseif ($filter === 'single') {
            $query->has('enrollments', '=', 1);
        } elseif ($filter === 'none') {
            $query->doesntHave('enrollments');
        } elseif ($filter === 'completed') {
            $query->whereHas('enrollments', fn($q) => $q->where('status', 'completed'));
        } elseif ($filter === 'in_progress') {
            $query->whereHas('enrollments', fn($q) => $q->where('status', 'in_progress'));
        } elseif ($filter === 'certified') {
            $query->whereHas('enrollments', fn($q) => $q->whereNotNull('certificate_number'));
        }

        // Sorting
        $sort = $request->input('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'most_enrolled':
                $query->orderByDesc('total_enrollments_count')->orderBy('name', 'asc');
                break;
            case 'latest':
                $query->orderByDesc('id');
                break;
            case 'oldest':
                $query->orderBy('id', 'asc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }

        $participants = $query->paginate($perPage)->withQueryString();

        // Calculate overarching LMS statistics for dashboard KPI widgets
        $stats = [
            'total_participants' => Participant::count(),
            'multi_training_participants' => Participant::has('enrollments', '>', 1)->count(),
            'total_enrollments' => Enrollment::count(),
            'total_completed' => Enrollment::where('status', 'completed')->count(),
            'total_certified' => Enrollment::whereNotNull('certificate_number')->count(),
        ];

        return Inertia::render('Lms::Admin/Participants', [
            'participants' => $participants,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'filter' => $filter,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'authUser' => $request->user(),
        ]);
    }

    /**
     * Get detailed participation history of a single participant (for modal view / JSON).
     */
    public function show(Request $request, Participant $participant): JsonResponse|Response
    {
        $participant->load([
            'user:id,name,email,role,created_at',
            'enrollments' => function ($q) {
                $q->with([
                    'course:id,title,slug,batch_name,category,start_date,end_date,status',
                    'lessonProgress',
                    'quizAttempts' => function ($qa) {
                        $qa->with('quiz:id,title,passing_score')->latest('id');
                    },
                    'moduleAttendances' => function ($ma) {
                        $ma->with('module:id,title');
                    },
                ])->orderByDesc('id');
            },
        ]);

        // Transform enrollments with computed data
        $enrollments = $participant->enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            $quizAttempts = $enrollment->quizAttempts;
            $highestScore = $quizAttempts->max('score');
            $hasPassedQuiz = $quizAttempts->where('is_passed', true)->isNotEmpty();

            return [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'course_title' => $course?->title ?? 'Kelas Tidak Ditemukan',
                'course_slug' => $course?->slug,
                'course_category' => $course?->category,
                'course_batch' => $course?->batch_name,
                'course_duration_days' => $course?->duration_in_days ?: 1,
                'course_dates' => [
                    'start' => $course?->start_date?->format('d M Y'),
                    'end' => $course?->end_date?->format('d M Y'),
                ],
                'training_transaction_code' => $enrollment->training_transaction_code,
                'status' => $enrollment->status,
                'attendance_path' => $enrollment->attendance_path,
                'attendance_at' => $enrollment->attendance_at?->format('d M Y H:i'),
                'progress_percentage' => round($enrollment->progress_percentage, 1),
                'completed_at' => $enrollment->completed_at?->format('d M Y H:i'),
                'certificate_number' => $enrollment->certificate_number,
                'certificate_download_url' => $enrollment->certificate_number
                    ? route('lms.certificate.download', $enrollment->id)
                    : null,
                'module_attendances_count' => $enrollment->moduleAttendances->count(),
                'quiz_attempts_count' => $quizAttempts->count(),
                'highest_quiz_score' => $highestScore,
                'has_passed_quiz' => $hasPassedQuiz,
            ];
        });

        $detail = [
            'id' => $participant->id,
            'name' => $participant->name,
            'email' => $participant->email,
            'nik' => $participant->nik,
            'phone' => $participant->phone,
            'gender' => $participant->gender,
            'agency_or_institution' => $participant->agency_or_institution,
            'address' => $participant->address,
            'training_transaction_code' => $participant->training_transaction_code,
            'created_at' => $participant->created_at?->format('d M Y H:i'),
            'updated_at' => $participant->updated_at?->format('d M Y H:i'),
            'user' => $participant->user ? [
                'id' => $participant->user->id,
                'name' => $participant->user->name,
                'email' => $participant->user->email,
                'role' => $participant->user->role,
                'created_at' => $participant->user->created_at?->format('d M Y'),
            ] : null,
            'summary' => [
                'total_enrolled' => $enrollments->count(),
                'total_completed' => $enrollments->where('status', 'completed')->count(),
                'total_in_progress' => $enrollments->where('status', 'in_progress')->count(),
                'total_certified' => $enrollments->whereNotNull('certificate_number')->count(),
            ],
            'enrollments' => $enrollments,
        ];

        return response()->json($detail);
    }

    /**
     * Update participant's master profile.
     */
    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:lms_participants,email,' . $participant->id,
            'nik' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'gender' => 'nullable|in:L,P',
            'agency_or_institution' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'training_transaction_code' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($participant, $validated) {
            $participant->update([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'nik' => !empty($validated['nik']) ? trim($validated['nik']) : null,
                'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
                'gender' => $validated['gender'] ?? null,
                'agency_or_institution' => !empty($validated['agency_or_institution']) ? trim($validated['agency_or_institution']) : null,
                'address' => !empty($validated['address']) ? trim($validated['address']) : null,
                'training_transaction_code' => !empty($validated['training_transaction_code']) ? trim($validated['training_transaction_code']) : null,
            ]);

            // Sync linked user login account if present
            if ($participant->user_id && $participant->user) {
                $participant->user->update([
                    'name' => $participant->name,
                    'email' => $participant->email,
                ]);
            }
        });

        return back()->with('success', "Data profil peserta '{$participant->name}' berhasil diperbarui.");
    }

    /**
     * Permanently delete participant and cascade all their records.
     * Requires confirmation by typing 'HAPUS' or the exact participant name.
     */
    public function destroy(Request $request, Participant $participant): RedirectResponse
    {
        $participantName = $participant->name;

        $request->validate([
            'confirmation' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($participantName) {
                    $entered = strtoupper(trim($value));
                    $expected = strtoupper(trim($participantName));
                    if ($entered !== 'HAPUS' && $entered !== $expected) {
                        $fail("Konfirmasi tidak valid. Harap ketik 'HAPUS' atau nama persis peserta ('{$participantName}').");
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($participant) {
            // If the user only has 'user' (student) role, delete the user login account to avoid orphan records
            if ($participant->user_id && $participant->user) {
                $user = $participant->user;
                if ($user->role === 'user' || $user->role === null) {
                    $user->delete();
                }
            }

            // Participant deletion cascades enrollments, lesson progress, quiz attempts, and unit attendances
            $participant->delete();
        });

        return back()->with('success', "Peserta '{$participantName}' beserta seluruh riwayat pelatihan dan sertifikatnya berhasil dihapus secara permanen.");
    }
}
