<?php

namespace Modules\Lms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Module;
use Modules\Lms\Models\Quiz;
use Modules\Lms\Models\QuizQuestion;

class QuizController extends Controller
{
    /**
     * Create or update a quiz for a module (supports multiple quizzes per module).
     */
    public function storeOrUpdate(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'quiz_id' => 'nullable|integer|exists:lms_quizzes,id',
            'title' => 'required|string|max:255',
            'passing_score' => 'required|integer|min:10|max:100',
            'description' => 'nullable|string|max:1000',
            'time_limit_minutes' => 'nullable|integer|min:1|max:180',
        ]);

        if (!empty($validated['quiz_id'])) {
            $quiz = Quiz::where('id', $validated['quiz_id'])->where('module_id', $module->id)->firstOrFail();
            $quiz->update([
                'title' => $validated['title'],
                'passing_score' => $validated['passing_score'],
                'description' => $validated['description'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'is_active' => true,
            ]);
        } else {
            $quiz = Quiz::create([
                'module_id' => $module->id,
                'course_id' => $module->course_id,
                'title' => $validated['title'],
                'passing_score' => $validated['passing_score'],
                'description' => $validated['description'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'is_active' => true,
            ]);
        }

        // Recalculate progress for enrollments
        $enrollments = Enrollment::where('course_id', $module->course_id)->get();
        foreach ($enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', "Kuis '{$quiz->title}' untuk Unit Kompetensi '{$module->title}' berhasil disimpan.");
    }

    /**
     * Delete a quiz (making the module quiz-free / optional).
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $courseId = $quiz->course_id;
        $moduleTitle = $quiz->module?->title ?? '';

        $quiz->delete();

        // Recalculate progress for enrollments in this course
        $enrollments = Enrollment::where('course_id', $courseId)->get();
        foreach ($enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', "Kuis untuk Unit Kompetensi '{$moduleTitle}' berhasil dihapus.");
    }

    /**
     * Add a question to a quiz.
     */
    public function addQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.key' => 'required|string|max:5',
            'options.*.text' => 'required|string',
            'correct_answer' => 'required|string|max:5',
            'explanation' => 'nullable|string',
            'points' => 'nullable|integer|min:1|max:100',
        ]);

        $nextOrder = (QuizQuestion::where('quiz_id', $quiz->id)->max('order_index') ?? 0) + 1;

        $quiz->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => 'multiple_choice',
            'options' => $validated['options'],
            'correct_answer' => $validated['correct_answer'],
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'] ?? 10,
            'order_index' => $nextOrder,
        ]);

        // Recalculate progress for all enrollments
        $enrollments = Enrollment::where('course_id', $quiz->course_id)->get();
        foreach ($enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        return back()->with('success', 'Butir soal kuis berhasil ditambahkan.');
    }

    /**
     * Update an existing quiz question.
     */
    public function updateQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.key' => 'required|string|max:5',
            'options.*.text' => 'required|string',
            'correct_answer' => 'required|string|max:5',
            'explanation' => 'nullable|string',
            'points' => 'nullable|integer|min:1|max:100',
        ]);

        $question->update([
            'question_text' => $validated['question_text'],
            'options' => $validated['options'],
            'correct_answer' => $validated['correct_answer'],
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'] ?? 10,
        ]);

        return back()->with('success', 'Butir soal kuis berhasil diperbarui.');
    }

    /**
     * Delete a question from a quiz.
     */
    public function deleteQuestion(QuizQuestion $question): RedirectResponse
    {
        $quiz = $question->quiz;
        $question->delete();

        if ($quiz) {
            $enrollments = Enrollment::where('course_id', $quiz->course_id)->get();
            foreach ($enrollments as $enrollment) {
                $enrollment->recalculateProgress();
            }
        }

        return back()->with('success', 'Butir soal kuis berhasil dihapus.');
    }
}
