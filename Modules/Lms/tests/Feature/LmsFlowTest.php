<?php

use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Lesson;
use Modules\Lms\Models\Module;
use Modules\Lms\Models\Participant;

test('lms login page can be rendered', function () {
    $response = $this->get('/lms/login');
    $response->assertStatus(200);
});

test('check email endpoint verifies participant registration', function () {
    $participant = Participant::where('email', 'peserta@bpvppangkep.id')->first();

    $response = $this->postJson('/lms/login/check-email', [
        'email' => 'peserta@bpvppangkep.id',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'registered' => true,
            'participant' => [
                'email' => 'peserta@bpvppangkep.id',
            ],
        ]);
});

test('student can confirm profile and access dashboard', function () {
    $response = $this->post('/lms/login/confirm', [
        'email' => 'peserta@bpvppangkep.id',
        'name' => 'MUHAMMAD IKHLAS, S.T.',
        'nik' => '7310041205980001',
        'phone' => '081234567890',
        'agency_or_institution' => 'CV. Celebes Teknik Pangkep',
    ]);

    $response->assertRedirect('/lms/dashboard');
    $this->assertAuthenticated = session('lms_participant_id');

    $dashResponse = $this->withSession(['lms_participant_id' => $this->assertAuthenticated])
        ->get('/lms/dashboard');
    $dashResponse->assertStatus(200);
});

test('dual path live zoom attendance works when window is open and rejects when closed', function () {
    $participant = Participant::where('email', 'nurhaliza@bpvppangkep.id')->first();
    $course = Course::where('slug', 'pelatihan-teknisi-refrigerasi-dan-tata-udara-ac-residential')->first();

    // Ensure zoom attendance is closed
    $course->update(['is_zoom_attendance_open' => false]);

    // Attempting attendance when closed should fail with error
    $failResponse = $this->withSession(['lms_participant_id' => $participant->id])
        ->post("/lms/courses/{$course->id}/attend", ['path' => 'live_zoom']);
    $failResponse->assertSessionHas('error');

    // Open zoom attendance window
    $course->update(['is_zoom_attendance_open' => true]);

    // Now attendance should succeed
    $successResponse = $this->withSession(['lms_participant_id' => $participant->id])
        ->post("/lms/courses/{$course->id}/attend", ['path' => 'live_zoom']);
    $successResponse->assertSessionHas('success');

    $enrollment = Enrollment::where('course_id', $course->id)
        ->where('participant_id', $participant->id)
        ->first();

    expect($enrollment->status)->toBe('completed')
        ->and($enrollment->attendance_path)->toBe('live_zoom')
        ->and($enrollment->certificate_number)->not->toBeNull()
        ->and($enrollment->certificate_hash)->not->toBeNull();
});

test('certificate pdf can be downloaded and renders valid PDF file', function () {
    $enrollment = Enrollment::where('status', 'completed')->whereNotNull('certificate_hash')->first();

    $response = $this->get("/lms/certificates/{$enrollment->id}/download");
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('official qr code tte verification portal returns valid certificate data', function () {
    $enrollment = Enrollment::where('status', 'completed')->whereNotNull('certificate_hash')->first();

    $response = $this->get("/lms/verify/{$enrollment->certificate_hash}");
    $response->assertStatus(200);
});

test('course can be duplicated with 1-click clone duplicating modules and lessons', function () {
    $course = Course::first();
    $initialModulesCount = $course->modules()->count();
    $initialLessonsCount = $course->lessons()->count();

    $cloned = $course->duplicate();

    expect($cloned->id)->not->toBe($course->id)
        ->and($cloned->title)->toContain('(Salinan)')
        ->and($cloned->modules()->count())->toBe($initialModulesCount)
        ->and($cloned->lessons()->count())->toBe($initialLessonsCount)
        ->and($cloned->status)->toBe('draft');
});
