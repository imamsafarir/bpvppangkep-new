<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (QuizQuestion $question) {
            $courseId = $question->quiz?->course_id;
            if (! empty($courseId)) {
                \Illuminate\Support\Facades\Cache::forget("lms_course_curriculum_{$courseId}");
            }
        });

        static::deleted(function (QuizQuestion $question) {
            $courseId = $question->quiz?->course_id;
            if (! empty($courseId)) {
                \Illuminate\Support\Facades\Cache::forget("lms_course_curriculum_{$courseId}");
            }
        });
    }

    protected $table = 'lms_quiz_questions';

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'order_index',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order_index' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
