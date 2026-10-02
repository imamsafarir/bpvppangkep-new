<?php

namespace Modules\Lms\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, SoftDeletes;



    protected $table = 'lms_courses';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'category',
        'cover_image',
        'status',
        'batch_name',
        'start_date',
        'end_date',
        'instructor_name',
        'zoom_link',
        'zoom_meeting_id',
        'zoom_passcode',
        'zoom_start_at',
        'zoom_end_at',
        'is_zoom_attendance_open',
        'zoom_attendance_opened_at',
        'zoom_attendance_duration_minutes',
        'zoom_attendance_closed_at',
        'zoom_attendance_scheduled_at',
        'certificate_template_path',
        'certificate_config',
        'certificate_number_format',
    ];

    protected $casts = [
        'is_zoom_attendance_open' => 'boolean',
        'zoom_attendance_opened_at' => 'datetime',
        'zoom_start_at' => 'datetime',
        'zoom_end_at' => 'datetime',
        'zoom_attendance_closed_at' => 'datetime',
        'zoom_attendance_scheduled_at' => 'datetime',
        'zoom_attendance_duration_minutes' => 'integer',
        'certificate_config' => 'array',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_attendance_open_now',
        'zoom_status',
        'is_self_study_unlocked',
        'attendance_remaining_seconds',
        'duration_in_days',
    ];

    /**
     * Get total training duration in days.
     */
    public function getDurationInDaysAttribute(): int
    {
        if ($this->start_date && $this->end_date) {
            return (int) $this->start_date->diffInDays($this->end_date) + 1;
        }
        return 1;
    }

    /**
     * Check if Zoom attendance window is currently open (active).
     */
    public function getIsAttendanceOpenNowAttribute(): bool
    {
        $now = Carbon::now('Asia/Makassar');

        // 1. Check if any module's attendance is currently open
        if ($this->relationLoaded('modules') && $this->modules->isNotEmpty()) {
            return $this->modules->contains(function ($m) {
                return $m->is_attendance_open_now;
            });
        }

        // 2. Scheduled attendance window at course level
        if ($this->zoom_attendance_scheduled_at) {
            $schedAt = Carbon::parse($this->zoom_attendance_scheduled_at, 'Asia/Makassar');
            $duration = $this->zoom_attendance_duration_minutes ?: 30;
            $closeTime = $schedAt->copy()->addMinutes($duration);

            $isExplicitlyClosed = false;
            if ($this->zoom_attendance_closed_at) {
                $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
                if ($closedAt->gte($schedAt)) {
                    $isExplicitlyClosed = true;
                }
            }

            if (! $isExplicitlyClosed && $now->between($schedAt, $closeTime)) {
                return true;
            }
        }

        // 3. Manually opened by admin at course level
        if ($this->is_zoom_attendance_open) {
            if ($this->zoom_attendance_closed_at) {
                $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
                return $now->lte($closedAt);
            }
            return true;
        }

        return false;
    }

    protected ?string $runtimeZoomStatus = null;

    /**
     * Mutator to prevent saving virtual zoom_status to database table lms_courses.
     */
    public function setZoomStatusAttribute($value): void
    {
        $this->runtimeZoomStatus = $value;
    }

    /**
     * Get real-time status of Zoom / Online Meeting session: 'upcoming', 'live', 'ended', or 'unscheduled'.
     */
    public function getZoomStatusAttribute(): string
    {
        if ($this->runtimeZoomStatus) {
            return $this->runtimeZoomStatus;
        }

        $raw = $this->attributes['zoom_status'] ?? null;
        if ($raw === 'ended') {
            return 'ended';
        }

        $startAt = $this->zoom_start_at;
        $endAt = $this->zoom_end_at;

        // Fallback to active module if course's own zoom_start_at is null or course has modules
        if ($this->relationLoaded('modules') && $this->modules->isNotEmpty()) {
            $today = Carbon::now('Asia/Makassar')->toDateString();
            $todayModule = $this->modules->first(function ($m) use ($today) {
                return $m->scheduled_date && Carbon::parse($m->scheduled_date)->toDateString() === $today;
            });

            if ($todayModule && $todayModule->zoom_status !== 'unscheduled') {
                return $todayModule->zoom_status;
            }

            $activeModule = $this->modules->first(function ($m) {
                return $m->zoom_status === 'live';
            }) ?: $this->modules->first(function ($m) {
                return $m->zoom_status === 'upcoming';
            });

            if ($activeModule && $activeModule->zoom_status !== 'unscheduled') {
                return $activeModule->zoom_status;
            }
        }

        if (! $startAt) {
            return $raw ?: 'unscheduled';
        }

        $now = Carbon::now('Asia/Makassar');
        if ($now->lessThan($startAt)) {
            return 'upcoming';
        }

        if ($endAt && $now->greaterThan($endAt)) {
            return 'ended';
        }

        return 'live';
    }

    /**
     * Check if Self-Study (Jalur 2) is unlocked for student.
     * Must go through Jalur 1 (Zoom) first; Jalur 2 unlocks only after Zoom time has ended.
     */
    public function getIsSelfStudyUnlockedAttribute(): bool
    {
        // If zoom is unscheduled, allow self study
        if (!$this->zoom_start_at) {
            return true;
        }

        // Only unlocked when zoom session has ended (waktu sesi Zoom telah lewat)
        return $this->zoom_status === 'ended';
    }

    /**
     * Calculate remaining seconds for attendance window.
     */
    public function getAttendanceRemainingSecondsAttribute(): int
    {
        $now = Carbon::now('Asia/Makassar');

        // Check active module first
        if ($this->relationLoaded('modules') && $this->modules->isNotEmpty()) {
            $openMod = $this->modules->first(function ($m) {
                return $m->is_attendance_open_now;
            });
            if ($openMod) {
                return $openMod->attendance_remaining_seconds;
            }
            return 0;
        }

        // Check scheduled attendance at course level
        if ($this->zoom_attendance_scheduled_at) {
            $schedAt = Carbon::parse($this->zoom_attendance_scheduled_at, 'Asia/Makassar');
            $duration = $this->zoom_attendance_duration_minutes ?: 30;
            $closeTime = $schedAt->copy()->addMinutes($duration);

            $isExplicitlyClosed = false;
            if ($this->zoom_attendance_closed_at) {
                $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
                if ($closedAt->gte($schedAt)) {
                    $isExplicitlyClosed = true;
                }
            }

            if (! $isExplicitlyClosed && $now->between($schedAt, $closeTime)) {
                return max(0, (int) $now->diffInSeconds($closeTime, false));
            }
        }

        // Check manually opened at course level
        if ($this->is_zoom_attendance_open && $this->zoom_attendance_closed_at) {
            $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
            return max(0, (int) $now->diffInSeconds($closedAt, false));
        }

        return 0;
    }

    /**
     * Default certificate configuration if empty.
     */
    public function getCertificateConfigAttribute($value): array
    {
        $decoded = $value ? json_decode($value, true) : [];
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return array_merge([
            'canvas_width_mm' => 297,
            'canvas_height_mm' => 210,
            'show_grid' => false,
            // Text coordinates in percentage of canvas width & height (or mm)
            'recipient_name' => [
                'x' => 50, // center 50%
                'y' => 45, // 45% from top
                'font_size' => 28,
                'font_weight' => 'bold',
                'color' => '#1e293b',
                'align' => 'center',
            ],
            'certificate_number' => [
                'x' => 50,
                'y' => 35,
                'font_size' => 14,
                'font_weight' => 'normal',
                'color' => '#475569',
                'align' => 'center',
            ],
            'course_title' => [
                'x' => 50,
                'y' => 55,
                'font_size' => 20,
                'font_weight' => 'bold',
                'color' => '#0f172a',
                'align' => 'center',
            ],
            'issue_date' => [
                'x' => 50,
                'y' => 65,
                'font_size' => 13,
                'font_weight' => 'normal',
                'color' => '#64748b',
                'align' => 'center',
            ],
            'qr_code' => [
                'x' => 50,
                'y' => 80,
                'size' => 80, // px
                'align' => 'center',
            ],
        ], $decoded);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'course_id')->orderBy('order_index');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'course_id')->orderBy('order_index');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'lms_enrollments', 'course_id', 'participant_id')
            ->withPivot([
                'training_transaction_code',
                'status',
                'attendance_path',
                'attendance_at',
                'progress_percentage',
                'completed_at',
                'certificate_number',
                'certificate_hash',
                'certificate_issued_at',
            ])
            ->withTimestamps();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Check if given user can manage (edit/update/delete) this course.
     */
    public function canManage(?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'admin', 'admin_lms'])) {
            return true;
        }

        if ($user->hasRole(['admin_lms_instructor', 'instructor'])) {
            if ($this->user_id && (int) $this->user_id === (int) $user->id) {
                return true;
            }
            if (empty($this->user_id) && !empty($this->instructor_name) && strcasecmp(trim($this->instructor_name), trim($user->name)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Duplicate course with modules, lessons, and quizzes.
     *
     * @param array<string, mixed> $overrides
     */
    public function duplicate(array $overrides = []): self
    {
        $newCourse = $this->replicate(['slug', 'created_at', 'updated_at']);
        $newCourse->fill($overrides);

        if (empty($overrides['title'])) {
            $newCourse->title = $this->title . ' (Salinan)';
        }

        $baseSlug = Str::slug($newCourse->title);
        $slug = $baseSlug;
        $counter = 1;
        while (self::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $newCourse->slug = $slug;
        $newCourse->status = $overrides['status'] ?? 'draft';
        $newCourse->is_zoom_attendance_open = false;
        $newCourse->zoom_attendance_opened_at = null;
        $newCourse->save();

        // Copy modules, their lessons, and quizzes
        $this->loadMissing(['modules.lessons', 'modules.quiz.questions']);
        foreach ($this->modules as $mod) {
            $newMod = $mod->replicate(['course_id', 'created_at', 'updated_at']);
            $newMod->course_id = $newCourse->id;
            $newMod->save();

            foreach ($mod->lessons as $lesson) {
                $newLesson = $lesson->replicate(['course_id', 'module_id', 'created_at', 'updated_at']);
                $newLesson->course_id = $newCourse->id;
                $newLesson->module_id = $newMod->id;
                $newLesson->save();
            }

            if ($mod->quiz) {
                $newQuiz = $mod->quiz->replicate(['course_id', 'module_id', 'created_at', 'updated_at']);
                $newQuiz->course_id = $newCourse->id;
                $newQuiz->module_id = $newMod->id;
                $newQuiz->save();

                foreach ($mod->quiz->questions as $question) {
                    $newQuestion = $question->replicate(['quiz_id', 'created_at', 'updated_at']);
                    $newQuestion->quiz_id = $newQuiz->id;
                    $newQuestion->save();
                }
            }
        }

        return $newCourse;
    }
}
