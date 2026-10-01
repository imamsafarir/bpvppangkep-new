<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $table = 'lms_quiz_attempts';

    protected $fillable = [
        'quiz_id',
        'enrollment_id',
        'participant_id',
        'score',
        'is_passed',
        'answers',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'float',
        'is_passed' => 'boolean',
        'answers' => 'array',
        'completed_at' => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
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
