<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('users cannot delete their own account', function () {
    // Account deletion is removed: it would destroy allocation and complaint history (BR-04).
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/profile')->assertMethodNotAllowed();
    $this->assertNotNull($user->fresh());
});

test('students can update their name and phone', function () {
    $student = User::factory()->student()->create();
    $profile = $student->studentProfile;

    $this->actingAs($student)->get('/profile')
        ->assertOk()
        ->assertSee($profile->registration_no)
        ->assertSee('name="phone"', false)
        ->assertDontSee('name="email"', false);

    $this->actingAs($student)->patch('/profile/student', [
        'name' => 'Changed Name',
        'phone' => '01812345678',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    $this->actingAs($student)->get('/profile')->assertSee('Profile updated.');

    expect($student->fresh()->name)->toBe('Changed Name')
        ->and($profile->fresh()->phone)->toBe('01812345678');
});

test('students cannot change locked record fields', function () {
    $student = User::factory()->student()->create();
    $profile = $student->studentProfile;
    $original = $profile->only(['registration_no', 'department', 'session']);
    $email = $student->email;

    $this->actingAs($student)->patch('/profile/student', [
        'name' => 'Changed Name',
        'phone' => '01812345678',
        'email' => 'changed@student.du.test',
        'registration_no' => '9999999999',
        'department' => 'Other',
        'session' => '2010-11',
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->email)->toBe($email)
        ->and($profile->fresh()->only(['registration_no', 'department', 'session']))->toBe($original);

    // The staff endpoint, which edits email, stays closed to students.
    $this->actingAs($student)->patch('/profile', [
        'name' => 'Changed Name',
        'email' => 'changed@student.du.test',
    ])->assertForbidden();
});

test('student phone must be a Bangladeshi mobile number', function () {
    $student = User::factory()->student()->create();
    $phone = $student->studentProfile->phone;

    $this->actingAs($student)->patch('/profile/student', [
        'name' => $student->name,
        'phone' => '01212345678',
    ])->assertSessionHasErrors('phone');

    expect($student->studentProfile->fresh()->phone)->toBe($phone);
});

test('staff cannot use the student profile endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile/student', [
        'name' => 'Test User',
        'phone' => '01812345678',
    ])->assertForbidden();
});
