<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $table = 'lms_participants';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'training_transaction_code',
        'nik',
        'phone',
        'agency_or_institution',
        'gender',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'participant_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'lms_enrollments', 'participant_id', 'course_id')
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
}
