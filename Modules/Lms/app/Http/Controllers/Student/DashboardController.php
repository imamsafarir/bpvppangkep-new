<?php

namespace Modules\Lms\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lms\Models\Participant;

class DashboardController extends Controller
{
    /**
     * Show LMS Dashboard for logged in Student.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $participantId = session('lms_participant_id');
        if (!$participantId) {
            return redirect()->route('lms.student.login')
                ->with('warning', 'Silakan masukkan email Anda terlebih dahulu untuk mengakses kelas.');
        }

        $participant = Participant::with([
            'enrollments' => function ($q) {
                $q->with(['course' => function ($c) {
                    $c->withCount('lessons');
                }])->orderByDesc('id');
            },
        ])->find($participantId);

        if (!$participant) {
            session()->forget('lms_participant_id');
            return redirect()->route('lms.student.login');
        }

        $totalEnrolled = $participant->enrollments->count();
        $completedCount = $participant->enrollments->where('status', 'completed')->count();
        $inProgressCount = $participant->enrollments->where('status', '!=', 'completed')->count();

        $stats = [
            'total_enrolled' => $totalEnrolled,
            'completed' => $completedCount,
            'in_progress' => $inProgressCount,
        ];

        return Inertia::render('Lms::Student/Dashboard', [
            'participant' => $participant,
            'enrollments' => $participant->enrollments,
            'stats' => $stats,
        ]);
    }
}
