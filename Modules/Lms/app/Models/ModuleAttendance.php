<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleAttendance extends Model
{
    use HasFactory;

    protected $table = 'lms_module_attendances';

    protected $fillable = [
        'course_id',
        'module_id',
        'enrollment_id',
        'participant_id',
        'day_number',
        'attendance_path',
        'attended_at',
        'notes',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'attended_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
}
