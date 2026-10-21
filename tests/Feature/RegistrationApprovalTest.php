<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;

test('a pending student cannot log in', function () {
    $student = User::factory()->student()->pending()->create();

    $this->post('/login', ['email' => $student->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your registration is awaiting approval by the hall office.']);

    $this->assertGuest();
});

test('a rejected student sees the rejection reason at login', function () {
    $student = User::factory()->student()->create(['status' => UserStatus::Rejected]);
    $student->forceFill(['rejection_reason' => 'Not a hall student'])->save();

    $this->post('/login', ['email' => $student->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your registration was rejected: Not a hall student']);
});

test('a logged in user who becomes inactive is logged out of protected pages', function () {
    $student = User::factory()->student()->create();
    $student->forceFill(['status' => UserStatus::Inactive])->save();

    $this->actingAs($student)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('office staff approve a pending student who can then log in', function () {
    $office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $student = User::factory()->student()->pending()->create();

    $this->actingAs($office)->post(route('registrations.approve', $student))->assertRedirect();
    expect($student->fresh()->status)->toBe(UserStatus::Active);

    auth()->logout();
    $this->post('/login', ['email' => $student->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($student);
});

test('rejecting requires a reason', function () {
    $office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $student = User::factory()->student()->pending()->create();

    $this->actingAs($office)->post(route('registrations.reject', $student))->assertSessionHasErrors('reason');
    $this->actingAs($office)->post(route('registrations.reject', $student), ['reason' => 'Duplicate account']);

    expect($student->fresh())
        ->status->toBe(UserStatus::Rejected)
        ->rejection_reason->toBe('Duplicate account');
});

test('a student cannot approve registrations (403)', function () {
    $student = User::factory()->student()->create();
    $applicant = User::factory()->student()->pending()->create();

    $this->actingAs($student)->get(route('registrations.index'))->assertForbidden();
    $this->actingAs($student)->post(route('registrations.approve', $applicant))->assertForbidden();
    expect($applicant->fresh()->status)->toBe(UserStatus::Pending);
});

test('an already decided registration cannot be decided again', function () {
    $office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $student = User::factory()->student()->create();

    $this->actingAs($office)->post(route('registrations.reject', $student), ['reason' => 'x'])->assertForbidden();
});

test('staff see the pending list', function () {
    $provost = User::factory()->withRole(RoleName::Provost)->create();
    $applicant = User::factory()->student()->pending()->create();

    $this->actingAs($provost)->get(route('registrations.index'))->assertOk()->assertSee($applicant->name);
});
