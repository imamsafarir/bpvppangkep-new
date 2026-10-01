<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'lms_enrollments';

    protected $fillable = [
        'course_id',
        'participant_id',
        'training_transaction_code',
        'status', // enrolled, in_progress, completed
        'attendance_path', // none, live_zoom, self_study
        'attendance_at',
        'progress_percentage',
        'completed_at',
        'certificate_number',
        'certificate_hash',
        'certificate_issued_at',
    ];

    protected $casts = [
        'attendance_at' => 'datetime',
        'completed_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
        'progress_percentage' => 'float',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'enrollment_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'enrollment_id');
    }

    public function moduleAttendances(): HasMany
    {
        return $this->hasMany(ModuleAttendance::class, 'enrollment_id');
    }

    /**
     * Recalculate progress percentage based on completed lessons and passed quizzes.
     */
    public function recalculateProgress(): float
    {
        $totalLessons = Lesson::where('course_id', $this->course_id)->count();

        // Active quizzes in this course that have at least 1 question
        $activeQuizIds = Quiz::where('course_id', $this->course_id)
            ->where('is_active', true)
            ->whereHas('questions')
            ->pluck('id');
        $totalQuizzes = $activeQuizIds->count();

        $totalItems = $totalLessons + $totalQuizzes;

        if ($totalItems === 0) {
            $this->progress_percentage = 0.0;
            if ($this->status === 'in_progress') {
                $this->status = 'enrolled';
            }
            $this->save();

            return 0.0;
        }

        $completedLessons = LessonProgress::where('enrollment_id', $this->id)
            ->where('is_completed', true)
            ->count();

        $passedQuizzes = 0;
        if ($totalQuizzes > 0) {
            $passedQuizzes = QuizAttempt::where('enrollment_id', $this->id)
                ->whereIn('quiz_id', $activeQuizIds)
                ->where('is_passed', true)
                ->distinct('quiz_id')
                ->count('quiz_id');
        }

        $completedItems = $completedLessons + $passedQuizzes;

        $percentage = round(($completedItems / $totalItems) * 100, 2);
        if ($percentage > 100) {
            $percentage = 100.0;
        }

        $this->progress_percentage = $percentage;
        if ($this->status === 'enrolled' && $percentage > 0) {
            $this->status = 'in_progress';
        } elseif ($this->status === 'in_progress' && $percentage === 0.0) {
            $this->status = 'enrolled';
        }
        $this->save();

        return $percentage;
    }

    /**
     * Issue certificate and mark completion.
     */
    public function completeAndIssueCertificate(?string $attendancePath = null): void
    {
        // Never overwrite an existing live_zoom attendance with self_study
        if ($this->attendance_path === 'live_zoom' || $attendancePath === 'live_zoom' || $this->moduleAttendances()->where('attendance_path', 'live_zoom')->exists()) {
            $this->attendance_path = 'live_zoom';
        } elseif (!empty($attendancePath)) {
            $this->attendance_path = $attendancePath;
        } elseif (empty($this->attendance_path) || $this->attendance_path === 'none') {
            $this->attendance_path = 'live_zoom';
        }

        if (empty($this->attendance_at)) {
            $this->attendance_at = now();
        }
        $this->status = 'completed';
        $this->completed_at = now();

        if (empty($this->certificate_hash)) {
            $this->certificate_hash = Str::lower(Str::random(32));
        }

        if (empty($this->certificate_number)) {
            $year = date('Y');
            $this->certificate_number = "BPVP-PANGKEP/LMS/{$year}/{$this->id}";
        }

        $this->certificate_issued_at = now();
        $this->save();
    }
}
