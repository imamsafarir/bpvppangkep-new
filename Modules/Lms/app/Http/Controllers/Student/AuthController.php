<?php

namespace Modules\Lms\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Participant;

class AuthController extends Controller
{
    /**
     * Show the dedicated Student LMS Login Page.
     */
    public function showLogin(): Response|RedirectResponse
    {
        // If already logged in as participant in session, go to dashboard
        if (session()->has('lms_participant_id')) {
            $participant = Participant::find(session('lms_participant_id'));
            if ($participant) {
                return redirect()->route('lms.student.dashboard');
            }
        }

        return Inertia::render('Lms::Student/Login');
    }

    /**
     * Check if email is registered in LMS Participants.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));
        $participant = Participant::where('email', $email)
            ->with(['courses' => function ($q) {
                $q->select('lms_courses.id', 'lms_courses.title', 'lms_courses.batch_name', 'lms_courses.status');
            }])
            ->first();

        if (!$participant) {
            return response()->json([
                'registered' => false,
                'message' => 'Alamat email tidak terdaftar dalam database pelatihan LMS BPVP Pangkep. Pastikan email yang dimasukkan sama dengan saat mendaftar.',
            ]);
        }

        return response()->json([
            'registered' => true,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'training_transaction_code' => $participant->training_transaction_code,
                'nik' => $participant->nik,
                'phone' => $participant->phone,
                'gender' => $participant->gender,
                'agency_or_institution' => $participant->agency_or_institution,
                'address' => $participant->address,
            ],
            'courses' => $participant->courses,
        ]);
    }

    /**
     * Confirm profile and establish student session.
     */
    public function confirmProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'nik' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'gender' => 'nullable|in:L,P',
        ]);

        $email = strtolower(trim($validated['email']));
        $participant = Participant::where('email', $email)->first();

        if (!$participant) {
            return back()->withErrors(['email' => 'Peserta dengan email ini tidak ditemukan.']);
        }

        // Establish session
        session(['lms_participant_id' => $participant->id]);

        return redirect()->route('lms.student.dashboard')
            ->with('success', "Selamat datang, {$participant->name}! Data profil Anda telah diverifikasi.");
    }

    /**
     * Student logout.
     */
    public function logout(): RedirectResponse
    {
        session()->forget('lms_participant_id');

        return redirect()->route('lms.student.login')
            ->with('info', 'Anda telah berhasil keluar dari akun LMS.');
    }
}
