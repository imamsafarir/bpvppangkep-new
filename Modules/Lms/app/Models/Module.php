<?php

namespace Modules\Lms\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Module extends Model
{
    use HasFactory;



    protected $table = 'lms_modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'delivery_mode',
        'duration_days',
        'day_number',
        'scheduled_date',
        'start_time',
        'end_time',
        'zoom_link',
        'zoom_meeting_id',
        'zoom_passcode',
        'zoom_status',
        'zoom_start_at',
        'zoom_end_at',
        'zoom_attendance_opened_at',
        'zoom_attendance_closed_at',
        'zoom_attendance_duration_minutes',
        'zoom_attendance_scheduled_at',
        'notes',
        'order_index',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'day_number' => 'integer',
        'scheduled_date' => 'date:Y-m-d',
        'zoom_start_at' => 'datetime',
        'zoom_end_at' => 'datetime',
        'zoom_attendance_opened_at' => 'datetime',
        'zoom_attendance_closed_at' => 'datetime',
        'zoom_attendance_duration_minutes' => 'integer',
        'zoom_attendance_scheduled_at' => 'datetime',
    ];

    protected $appends = [
        'is_attendance_open_now',
        'attendance_remaining_seconds',
        'zoom_status',
        'zoom_remaining_seconds',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('order_index');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'module_id')->orderBy('id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ModuleAttendance::class, 'module_id');
    }

    /**
     * Get real-time status of Online Meeting session: 'upcoming', 'live', 'ended', or 'unscheduled'.
     */
    public function getZoomStatusAttribute(): string
    {
        $raw = $this->attributes['zoom_status'] ?? null;
        if ($raw === 'ended') {
            return 'ended';
        }

        if (! $this->zoom_start_at) {
            return $raw ?: 'unscheduled';
        }

        $now = Carbon::now('Asia/Makassar');
        $start = Carbon::parse($this->zoom_start_at, 'Asia/Makassar');
        $end = $this->zoom_end_at ? Carbon::parse($this->zoom_end_at, 'Asia/Makassar') : null;

        if ($raw === 'live') {
            if ($end && $now->greaterThan($end)) {
                return 'ended';
            }
            return 'live';
        }

        if ($now->lessThan($start)) {
            return 'upcoming';
        }

        if ($end && $now->greaterThan($end)) {
            return 'ended';
        }

        return 'live';
    }

    /**
     * Get remaining seconds for live Online Meeting in this unit.
     */
    public function getZoomRemainingSecondsAttribute(): int
    {
        $status = $this->zoom_status;
        if ($status !== 'live') {
            return 0;
        }

        $now = Carbon::now('Asia/Makassar');
        $end = $this->zoom_end_at ? Carbon::parse($this->zoom_end_at, 'Asia/Makassar') : null;

        if ($end && $now->lessThanOrEqualTo($end)) {
            return max(0, (int) $now->diffInSeconds($end, false));
        }

        return 0;
    }

    /**
     * Check if attendance window is currently open right now for this unit.
     */
    public function getIsAttendanceOpenNowAttribute(): bool
    {
        $now = Carbon::now('Asia/Makassar');

        // 1. Scheduled attendance window
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

        // 2. Manually opened
        if ($this->zoom_attendance_opened_at) {
            $openedAt = Carbon::parse($this->zoom_attendance_opened_at, 'Asia/Makassar');
            if ($this->zoom_attendance_closed_at) {
                $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
                if ($now->gte($openedAt) && $now->lte($closedAt)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Get remaining seconds for attendance window in this unit.
     */
    public function getAttendanceRemainingSecondsAttribute(): int
    {
        $now = Carbon::now('Asia/Makassar');

        // Check active scheduled attendance first
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

        // Check active manually opened attendance
        if ($this->zoom_attendance_opened_at) {
            $openedAt = Carbon::parse($this->zoom_attendance_opened_at, 'Asia/Makassar');
            if ($this->zoom_attendance_closed_at) {
                $closedAt = Carbon::parse($this->zoom_attendance_closed_at, 'Asia/Makassar');
                if ($now->gte($openedAt) && $now->lte($closedAt)) {
                    return max(0, (int) $now->diffInSeconds($closedAt, false));
                }
            } else {
                return 1800;
            }
        }

        return 0;
    }
}
