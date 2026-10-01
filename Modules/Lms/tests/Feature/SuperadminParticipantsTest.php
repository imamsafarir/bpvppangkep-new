<?php

use App\Models\User;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Participant;

test('superadmin can access all participants master page', function () {
    $superadmin = User::firstOrCreate(
        ['email' => 'superadmin_test@bpvppangkep.id'],
        [
            'name' => 'Super Admin Test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]
    );

    $response = $this->actingAs($superadmin)->get('/admin/lms/participants');
    $response->assertStatus(200);
});

test('non-superadmin is forbidden from accessing all participants master page', function () {
    $regularAdmin = User::firstOrCreate(
        ['email' => 'regular_admin_test@bpvppangkep.id'],
        [
            'name' => 'Admin Regular Test',
            'password' => bcrypt('password'),
            'role' => 'admin_lms',
        ]
    );

    $response = $this->actingAs($regularAdmin)->get('/admin/lms/participants');
    $response->assertStatus(403);
});

test('superadmin can search participants by name', function () {
    $superadmin = User::where('role', 'like', '%super_admin%')->first()
        ?? User::factory()->create(['role' => 'super_admin']);

    $uniqueName = 'TEST_UNIQUE_PARTICIPANT_' . time();
    $participant = Participant::create([
        'name' => $uniqueName,
        'email' => 'unique_' . time() . '@test.id',
        'nik' => '7310000000000001',
    ]);

    $response = $this->actingAs($superadmin)->get("/admin/lms/participants?search={$uniqueName}");
    $response->assertStatus(200);

    // Clean up
    $participant->delete();
});

test('superadmin can view participant detail and history via JSON', function () {
    $superadmin = User::where('role', 'like', '%super_admin%')->first()
        ?? User::factory()->create(['role' => 'super_admin']);

    $participant = Participant::create([
        'name' => 'Peserta Detail Test',
        'email' => 'detail_test_' . time() . '@bpvppangkep.id',
        'nik' => '7310000000000002',
    ]);

    $response = $this->actingAs($superadmin)->getJson("/admin/lms/participants/{$participant->id}");
    $response->assertStatus(200)
        ->assertJson([
            'id' => $participant->id,
            'name' => 'Peserta Detail Test',
            'summary' => [
                'total_enrolled' => 0,
            ],
        ]);

    $participant->delete();
});

test('superadmin can update participant profile', function () {
    $superadmin = User::where('role', 'like', '%super_admin%')->first()
        ?? User::factory()->create(['role' => 'super_admin']);

    $participant = Participant::create([
        'name' => 'Nama Sebelum Update',
        'email' => 'update_before_' . time() . '@bpvppangkep.id',
        'nik' => '7310000000000003',
    ]);

    $updatedEmail = 'update_after_' . time() . '@bpvppangkep.id';

    $response = $this->actingAs($superadmin)->put("/admin/lms/participants/{$participant->id}", [
        'name' => 'Nama Setelah Update',
        'email' => $updatedEmail,
        'nik' => '7310000000000004',
        'phone' => '081122334455',
        'gender' => 'L',
        'agency_or_institution' => 'Instansi Baru',
        'address' => 'Alamat Baru No 123',
    ]);

    $response->assertSessionHas('success');

    $participant->refresh();
    expect($participant->name)->toBe('Nama Setelah Update');
    expect($participant->email)->toBe($updatedEmail);
    expect($participant->nik)->toBe('7310000000000004');

    $participant->delete();
});

test('superadmin delete participant requires confirmation string and rejects invalid confirmation', function () {
    $superadmin = User::where('role', 'like', '%super_admin%')->first()
        ?? User::factory()->create(['role' => 'super_admin']);

    $participant = Participant::create([
        'name' => 'Peserta Akan Dihapus',
        'email' => 'delete_test_' . time() . '@bpvppangkep.id',
    ]);

    // Invalid confirmation string should fail validation
    $failResponse = $this->actingAs($superadmin)->delete("/admin/lms/participants/{$participant->id}", [
        'confirmation' => 'SALAH_KETIK',
    ]);
    $failResponse->assertSessionHasErrors('confirmation');
    expect(Participant::find($participant->id))->not->toBeNull();

    // Valid confirmation string 'HAPUS' should succeed
    $successResponse = $this->actingAs($superadmin)->delete("/admin/lms/participants/{$participant->id}", [
        'confirmation' => 'HAPUS',
    ]);
    $successResponse->assertSessionHas('success');
    expect(Participant::find($participant->id))->toBeNull();
});
