<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;

function registrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Tanvir Hasan',
        'registration_no' => '2023123456',
        'department' => 'Physics',
        'session' => '2023-24',
        'phone' => '01712345678',
        'email' => 'tanvir@student.du.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk();
});

test('a student registers as pending and is not logged in', function () {
    $this->post('/register', registrationData())->assertRedirect(route('login'));

    $this->assertGuest();
    $user = User::where('email', 'tanvir@student.du.test')->first();
    expect($user->status)->toBe(UserStatus::Pending)
        ->and($user->hasRole(RoleName::Student))->toBeTrue()
        ->and($user->studentProfile->registration_no)->toBe('2023123456');
});

test('an en dash in the session is normalised', function () {
    $this->post('/register', registrationData(['session' => '2023–24']));

    expect(User::first()->studentProfile->session)->toBe('2023-24');
});

test('registration input is validated', function (array $override, string $field) {
    $this->post('/register', registrationData($override))->assertSessionHasErrors($field);
    expect(User::count())->toBe(0);
})->with([
    'non-university email' => [['email' => 'tanvir@gmail.com'], 'email'],
    'short registration no' => [['registration_no' => '12345'], 'registration_no'],
    'bad phone' => [['phone' => '0171234567'], 'phone'],
    'non-BD phone' => [['phone' => '+8801712345678'], 'phone'],
    'bad session format' => [['session' => '2023'], 'session'],
    'non-consecutive session' => [['session' => '2023-25'], 'session'],
    'unknown department' => [['department' => 'Alchemy'], 'department'],
]);

test('registration number and email must be unique', function () {
    $existing = User::factory()->student()->create();

    $this->post('/register', registrationData(['registration_no' => $existing->studentProfile->registration_no]))
        ->assertSessionHasErrors('registration_no');
    $this->post('/register', registrationData(['email' => $existing->email]))
        ->assertSessionHasErrors('email');
});
