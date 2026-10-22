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

test('students see their details read-only and cannot change them', function () {
    $student = User::factory()->student()->create();
    $profile = $student->studentProfile;

    $this->actingAs($student)->get('/profile')
        ->assertOk()
        ->assertSee($profile->registration_no)
        ->assertDontSee('name="email"', false);

    $this->actingAs($student)->patch('/profile', [
        'name' => 'Changed Name',
        'email' => 'changed@student.du.test',
    ])->assertForbidden();

    expect($student->fresh()->name)->not->toBe('Changed Name');
});
