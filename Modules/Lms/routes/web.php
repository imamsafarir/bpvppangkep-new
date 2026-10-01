<?php

use Illuminate\Support\Facades\Route;
use Modules\Lms\Http\Controllers\Admin\AllParticipantsController;
use Modules\Lms\Http\Controllers\Admin\CertificateBuilderController;
use Modules\Lms\Http\Controllers\Admin\CourseController;
use Modules\Lms\Http\Controllers\Admin\OnlineMeetingController;
use Modules\Lms\Http\Controllers\Admin\ModuleLessonController;
use Modules\Lms\Http\Controllers\Admin\ParticipantImportController;
use Modules\Lms\Http\Controllers\Admin\QuizController;
use Modules\Lms\Http\Controllers\CertificateController;
use Modules\Lms\Http\Controllers\Student\AuthController;
use Modules\Lms\Http\Controllers\Student\ClassroomController;
use Modules\Lms\Http\Controllers\Student\DashboardController;

/*
|--------------------------------------------------------------------------
| Public LMS Routes (Verification & Certificate Download)
|--------------------------------------------------------------------------
*/

Route::get('/lms/verify/{hash}', [CertificateController::class, 'verify'])->name('lms.verify');
Route::get('/lms/certificates/{enrollment}/download', [CertificateController::class, 'download'])->name('lms.certificate.download');

/*
|--------------------------------------------------------------------------
| Student LMS Flow (Authentication, Dashboard, Classroom, Attendance)
|--------------------------------------------------------------------------
*/
Route::prefix('lms')->name('lms.student.')->group(function () {
    Route::get('/', function () {
        return session()->has('lms_participant_id')
            ? redirect()->route('lms.student.dashboard')
            : redirect()->route('lms.student.login');
    });

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/check-email', [AuthController::class, 'checkEmail'])->name('check-email');
    Route::post('/login/confirm', [AuthController::class, 'confirmProfile'])->name('confirm-profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/learn/{course:slug}', [ClassroomController::class, 'show'])->name('classroom');
    Route::post('/lessons/{lesson}/complete', [ClassroomController::class, 'markLessonComplete'])->name('lesson.complete');
    Route::post('/courses/{course}/attend', [ClassroomController::class, 'submitAttendance'])->name('attend');
    Route::post('/quizzes/{quiz}/submit', [ClassroomController::class, 'submitQuiz'])->name('quiz.submit');
});

/*
|--------------------------------------------------------------------------
| Admin LMS Flow (Card Grid Workspace, 4 Management Tabs)
| Protected by Auth & LMS Admin Roles
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin_lms,admin,super_admin,admin_lms_instructor,instructor'])
    ->prefix('admin/lms')
    ->name('admin.lms.')
    ->group(function () {
        // Main Card Grid Dashboard
        Route::get('/', [CourseController::class, 'index'])->name('index');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::match(['put', 'post'], '/{course}', [CourseController::class, 'update'])->name('update');
        Route::post('/{course}/update', [CourseController::class, 'update'])->name('update.post');
        Route::patch('/{course}/status', [CourseController::class, 'updateStatus'])->name('status');
        Route::patch('/{course}/dates', [CourseController::class, 'updateDates'])->name('dates');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->name('destroy');
        Route::post('/{course}/duplicate', [CourseController::class, 'duplicate'])->name('duplicate');

        // 1 Jendela Workspace Manajemen Kelas
        Route::get('/{course}/workspace', [CourseController::class, 'workspace'])->name('workspace');

        // TAB 1: Materi & Pelajaran
        Route::post('/{course}/modules', [ModuleLessonController::class, 'storeModule'])->name('modules.store');
        Route::put('/modules/{module}', [ModuleLessonController::class, 'updateModule'])->name('modules.update');
        Route::delete('/modules/{module}', [ModuleLessonController::class, 'destroyModule'])->name('modules.destroy');
        Route::post('/{course}/modules/reorder', [ModuleLessonController::class, 'reorderModules'])->name('modules.reorder');

        Route::post('/{course}/lessons', [ModuleLessonController::class, 'storeLesson'])->name('lessons.store');
        Route::post('/lessons/{lesson}/update', [ModuleLessonController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [ModuleLessonController::class, 'destroyLesson'])->name('lessons.destroy');
        Route::post('/modules/{module}/lessons/reorder', [ModuleLessonController::class, 'reorderLessons'])->name('lessons.reorder');
        Route::get('/{course}/curriculum/template', [ModuleLessonController::class, 'downloadTemplate'])->name('curriculum.template');
        Route::post('/{course}/curriculum/import', [ModuleLessonController::class, 'importCurriculum'])->name('curriculum.import');

        // Kuis Opsional per Unit Kompetensi
        Route::post('/modules/{module}/quiz', [QuizController::class, 'storeOrUpdate'])->name('quiz.store');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quiz.destroy');
        Route::post('/quizzes/{quiz}/questions', [QuizController::class, 'addQuestion'])->name('quiz.questions.add');
        Route::put('/quiz-questions/{question}', [QuizController::class, 'updateQuestion'])->name('quiz.questions.update');
        Route::delete('/quiz-questions/{question}', [QuizController::class, 'deleteQuestion'])->name('quiz.questions.delete');

        // TAB 2: Peserta & Import Excel
        Route::post('/{course}/participants', [ParticipantImportController::class, 'store'])->name('participants.store');
        Route::post('/{course}/participants/import', [ParticipantImportController::class, 'import'])->name('participants.import');
        Route::put('/{course}/enrollments/{enrollment}', [ParticipantImportController::class, 'update'])->name('enrollments.update');
        Route::delete('/{course}/enrollments/{enrollment}', [ParticipantImportController::class, 'destroy'])->name('enrollments.destroy');
        Route::post('/{course}/enrollments/bulk-delete', [ParticipantImportController::class, 'bulkDestroy'])->name('enrollments.bulk-destroy');
        Route::get('/{course}/participants/export', [ParticipantImportController::class, 'export'])->name('participants.export');

        // TAB 3: Online Meeting & Monitoring Kehadiran (Kelas & Unit)
        Route::post('/{course}/zoom-toggle', [OnlineMeetingController::class, 'toggleAttendance'])->name('zoom.toggle');
        Route::post('/{course}/zoom/attendance/open', [OnlineMeetingController::class, 'openAttendance'])->name('zoom.attendance.open');
        Route::post('/{course}/zoom/attendance/close', [OnlineMeetingController::class, 'closeAttendance'])->name('zoom.attendance.close');
        Route::post('/{course}/zoom/attendance/schedule', [OnlineMeetingController::class, 'scheduleAttendance'])->name('zoom.attendance.schedule');
        Route::post('/{course}/zoom/schedule', [OnlineMeetingController::class, 'updateZoomSchedule'])->name('zoom.schedule');
        Route::post('/{course}/zoom/start', [OnlineMeetingController::class, 'startZoomNow'])->name('zoom.start');
        Route::post('/{course}/zoom/end', [OnlineMeetingController::class, 'endZoomNow'])->name('zoom.end');
        Route::post('/{course}/enrollments/{enrollment}/manual-attendance', [OnlineMeetingController::class, 'manualAttendance'])->name('attendance.manual');
        Route::get('/{course}/monitoring', [OnlineMeetingController::class, 'monitoringData'])->name('monitoring.data');

        // Unit-level Online Meeting & Attendance Controls (Multi-Day / Per Unit)
        Route::post('/modules/{module}/zoom-schedule', [OnlineMeetingController::class, 'updateUnitZoomSchedule'])->name('modules.zoom.schedule');
        Route::post('/modules/{module}/zoom/schedule', [OnlineMeetingController::class, 'updateUnitZoomSchedule']);
        Route::post('/modules/{module}/zoom-start', [OnlineMeetingController::class, 'startUnitZoomNow'])->name('modules.zoom.start');
        Route::post('/modules/{module}/zoom/start', [OnlineMeetingController::class, 'startUnitZoomNow']);
        Route::post('/modules/{module}/zoom-end', [OnlineMeetingController::class, 'endUnitZoomNow'])->name('modules.zoom.end');
        Route::post('/modules/{module}/zoom/end', [OnlineMeetingController::class, 'endUnitZoomNow']);
        Route::post('/modules/{module}/attendance-open', [OnlineMeetingController::class, 'openUnitAttendance'])->name('modules.attendance.open');
        Route::post('/modules/{module}/attendance/open', [OnlineMeetingController::class, 'openUnitAttendance']);
        Route::post('/modules/{module}/zoom/attendance/open', [OnlineMeetingController::class, 'openUnitAttendance']);
        Route::post('/modules/{module}/attendance-close', [OnlineMeetingController::class, 'closeUnitAttendance'])->name('modules.attendance.close');
        Route::post('/modules/{module}/attendance/close', [OnlineMeetingController::class, 'closeUnitAttendance']);
        Route::post('/modules/{module}/zoom/attendance/close', [OnlineMeetingController::class, 'closeUnitAttendance']);
        Route::post('/modules/{module}/attendance-schedule', [OnlineMeetingController::class, 'scheduleUnitAttendance'])->name('modules.attendance.schedule');
        Route::post('/modules/{module}/attendance/schedule', [OnlineMeetingController::class, 'scheduleUnitAttendance']);
        Route::post('/modules/{module}/zoom/attendance/schedule', [OnlineMeetingController::class, 'scheduleUnitAttendance']);
        Route::post('/{course}/enrollments/{enrollment}/modules/{module}/toggle-attendance', [OnlineMeetingController::class, 'toggleModuleAttendance'])->name('attendance.module.toggle');

        // TAB 4: Pengaturan Sertifikat A4 & TTE
        Route::post('/{course}/certificate/template', [CertificateBuilderController::class, 'uploadTemplate'])->name('certificate.template');
        Route::post('/{course}/certificate/config', [CertificateBuilderController::class, 'updateConfig'])->name('certificate.config');
        Route::get('/{course}/certificate/preview', [CertificateBuilderController::class, 'preview'])->name('certificate.preview');
    });

/*
|--------------------------------------------------------------------------
| Superadmin Master Data Peserta LMS (Lihat Seluruh Data, Histori, Edit & Hapus)
| Protected strictly by Auth & super_admin Role
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('admin/lms/participants')
    ->name('admin.lms.participants.')
    ->group(function () {
        Route::get('/', [AllParticipantsController::class, 'index'])->name('index');
        Route::get('/{participant}', [AllParticipantsController::class, 'show'])->name('show');
        Route::put('/{participant}', [AllParticipantsController::class, 'update'])->name('update');
        Route::delete('/{participant}', [AllParticipantsController::class, 'destroy'])->name('destroy');
    });
