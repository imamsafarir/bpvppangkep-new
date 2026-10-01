<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Participant;

class CourseController extends Controller
{
    /**
     * Display Card Grid Dashboard for LMS Admin.
     */
    public function index(Request $request): Response
    {
        $query = Course::query()
            ->withCount([
                'modules',
                'lessons',
                'enrollments',
                'enrollments as completed_enrollments_count' => function ($q) {
                    $q->where('status', 'completed');
                },
            ]);

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('batch_name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Category filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $courses = $query->orderByDesc('id')->paginate(12)->withQueryString();

        $user = $request->user();
        $courses->getCollection()->transform(function ($course) use ($user) {
            $course->can_manage = $course->canManage($user);
            return $course;
        });

        // Calculate overarching statistics
        $stats = [
            'total_courses' => Course::count(),
            'active_courses' => Course::where('status', 'published')->count(),
            'live_zoom_open' => Course::where('is_zoom_attendance_open', true)->count(),
            'total_participants' => Participant::count(),
            'total_enrollments' => Enrollment::count(),
            'total_certified' => Enrollment::where('status', 'completed')->count(),
        ];

        // Available categories list
        $categories = Course::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return Inertia::render('Lms::Admin/Index', [
            'courses' => $courses,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
                'category' => $request->input('category', 'all'),
            ],
            'authUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_instructor' => $user->hasRole(['admin_lms_instructor', 'instructor']) && ! $user->hasRole(['super_admin', 'admin', 'admin_lms']),
            ],
        ]);
    }

    /**
     * Store a newly created course (+ Buat Kelas Baru).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'batch_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'instructor_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'zoom_date' => 'nullable|date',
            'zoom_start_time' => 'nullable|string|max:10',
            'zoom_end_time' => 'nullable|string|max:10',
            'zoom_link' => 'nullable|url|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
            'status' => 'required|in:draft,published,archived',
            'cover_image' => 'nullable|image|max:3072',
        ]);

        $zoomDate = $validated['zoom_date'] ?? $validated['start_date'] ?? null;
        if (! empty($zoomDate) && ! empty($validated['zoom_start_time'])) {
            try {
                $validated['zoom_start_at'] = Carbon::parse($zoomDate . ' ' . $validated['zoom_start_time']);
            } catch (\Exception $e) {
            }
        }
        if (! empty($zoomDate) && ! empty($validated['zoom_end_time'])) {
            try {
                $validated['zoom_end_at'] = Carbon::parse($zoomDate . ' ' . $validated['zoom_end_time']);
            } catch (\Exception $e) {
            }
        }
        unset($validated['zoom_date'], $validated['zoom_start_time'], $validated['zoom_end_time']);

        $validated['user_id'] = $request->user()->id;
        if (empty($validated['instructor_name'])) {
            $validated['instructor_name'] = $request->user()->name;
        }

        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('lms/covers', 'public');
            $validated['cover_image'] = $path;
        }

        $course = Course::create($validated);

        return redirect()->route('admin.lms.workspace', $course->id)
            ->with('success', "Kelas '{$course->title}' berhasil dibuat. Silakan kelola materi dan peserta!");
    }

    /**
     * 1-Click Duplicate Course (📋 Duplikasi Kelas).
     */
    public function duplicate(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'batch_name' => 'nullable|string|max:100',
            'instructor_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['user_id'] = $request->user()->id;
        if (empty($validated['instructor_name'])) {
            $validated['instructor_name'] = $request->user()->name;
        }

        $newCourse = $course->duplicate($validated);

        return redirect()->route('admin.lms.workspace', $newCourse->id)
            ->with('success', "Kelas '{$newCourse->title}' berhasil diduplikasi beserta seluruh materi!");
    }

    /**
     * 1 Jendela Workspace Manajemen Kelas.
     */
    public function workspace(Request $request, Course $course): Response
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini karena Anda bukan instruktur yang ditugaskan.');
        }
        $course->load([
            'modules' => function ($q) {
                $q->with([
                    'lessons' => function ($l) {
                        $l->orderBy('order_index');
                    },
                    'quiz.questions',
                    'quizzes.questions',
                    'attendances',
                ])->orderBy('day_number')->orderBy('order_index');
            },
            'enrollments' => function ($q) {
                $q->with(['participant', 'moduleAttendances'])
                    ->withCount([
                        'lessonProgress as completed_lessons_count' => function ($lp) {
                            $lp->where('is_completed', true);
                        },
                        'moduleAttendances as attended_days_count',
                    ])
                    ->orderByDesc('id');
            },
        ]);

        $totalLessons = $course->lessons()->count();

        $metrics = [
            'total_modules' => $course->modules->count(),
            'total_lessons' => $totalLessons,
            'total_students' => $course->enrollments->count(),
            'completed_students' => $course->enrollments->where('status', 'completed')->count(),
            'live_zoom_attendees' => $course->enrollments->where('attendance_path', 'live_zoom')->count(),
            'self_study_attendees' => $course->enrollments->where('attendance_path', 'self_study')->count(),
        ];

        return Inertia::render('Lms::Admin/Workspace', [
            'course' => $course,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Update course settings.
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'batch_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'instructor_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'zoom_date' => 'nullable|date',
            'zoom_start_time' => 'nullable|string|max:10',
            'zoom_end_time' => 'nullable|string|max:10',
            'zoom_link' => 'nullable|url|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:100',
            'status' => 'required|in:draft,published,archived',
            'cover_image' => 'nullable|image|max:3072',
            'certificate_number_format' => 'nullable|string|max:100',
        ]);

        $zoomDate = $validated['zoom_date'] ?? $validated['start_date'] ?? ($course->start_date ? $course->start_date->format('Y-m-d') : null);
        if (! empty($zoomDate) && ! empty($validated['zoom_start_time'])) {
            try {
                $validated['zoom_start_at'] = Carbon::parse($zoomDate . ' ' . $validated['zoom_start_time']);
            } catch (\Exception $e) {
            }
        }
        if (! empty($zoomDate) && ! empty($validated['zoom_end_time'])) {
            try {
                $validated['zoom_end_at'] = Carbon::parse($zoomDate . ' ' . $validated['zoom_end_time']);
            } catch (\Exception $e) {
            }
        }
        unset($validated['zoom_date'], $validated['zoom_start_time'], $validated['zoom_end_time']);

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image) {
                Storage::disk('public')->delete($course->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('lms/covers', 'public');
        }

        $course->update($validated);

        return back()->with('success', 'Data kelas berhasil diperbarui!');
    }

    /**
     * Update course status (draft, published, archived).
     */
    public function updateStatus(Request $request, Course $course): RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $updateData = ['status' => $validated['status']];
        if (array_key_exists('start_date', $validated)) {
            $updateData['start_date'] = $validated['start_date'];
        }
        if (array_key_exists('end_date', $validated)) {
            $updateData['end_date'] = $validated['end_date'];
        }

        $course->update($updateData);

        $msg = match ($validated['status']) {
            'published' => "Pelatihan '{$course->title}' resmi DIMULAI dan TAYANG! Peserta kini dapat mengakses ruang kelas.",
            'draft' => "Status pelatihan '{$course->title}' dikembalikan menjadi DRAFT.",
            'archived' => "Pelatihan '{$course->title}' telah diarsipkan.",
        };

        return back()->with('success', $msg);
    }

    /**
     * Update course schedule dates (start_date, end_date).
     */
    public function updateDates(Request $request, Course $course): RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola kelas ini.');
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $course->update($validated);

        return back()->with('success', 'Jadwal durasi pelatihan berhasil diperbarui!');
    }

    /**
     * Soft delete course.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        if (! $course->canManage($request->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kelas ini.');
        }

        $title = $course->title;
        $course->delete();

        return redirect()->route('admin.lms.index')
            ->with('success', "Kelas '{$title}' berhasil dihapus.");
    }
}
