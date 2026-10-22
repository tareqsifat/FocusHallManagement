<?php

use App\Enums\RoleName;
use App\Models\HouseTutor;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->withRole(RoleName::SuperAdmin)->create();
    $this->staff = User::factory()->withRole(RoleName::OfficeStaff)->create(['name' => 'Staff Member']);
});

test('only the super admin can open the users screen and update roles', function (RoleName $role) {
    $actor = User::factory()->withRole($role)->create();

    $this->actingAs($actor)->get(route('users.index'))->assertForbidden();
    $this->actingAs($actor)->put(route('users.roles', $this->staff), ['roles' => ['provost']])->assertForbidden();
    expect($this->staff->fresh()->hasRole(RoleName::Provost))->toBeFalse();
})->with([RoleName::Provost, RoleName::OfficeStaff, RoleName::HouseTutor, RoleName::Student]);

test('the super admin updates a staff member\'s roles', function () {
    $this->actingAs($this->admin)->get(route('users.index'))
        ->assertOk()->assertSee('Staff Member')->assertSee('Update role');

    $this->actingAs($this->admin)->put(route('users.roles', $this->staff), ['roles' => ['provost']])
        ->assertSessionHasNoErrors();

    $staff = $this->staff->fresh();
    expect($staff->hasRole(RoleName::Provost))->toBeTrue()
        ->and($staff->hasRole(RoleName::OfficeStaff))->toBeFalse();
});

test('roles that belong to another flow are kept and cannot be set here', function () {
    $tutor = HouseTutor::factory()->create()->user;

    $this->actingAs($this->admin)->put(route('users.roles', $tutor), ['roles' => ['office_staff']])
        ->assertSessionHasNoErrors();
    expect($tutor->fresh()->hasRole(RoleName::HouseTutor, RoleName::OfficeStaff))->toBeTrue()
        ->and($tutor->fresh()->roles)->toHaveCount(2);

    $this->actingAs($this->admin)->put(route('users.roles', $this->staff), ['roles' => ['club_admin']])
        ->assertSessionHasErrors('roles.0');
});

test('student accounts cannot be given staff roles', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($this->admin)->put(route('users.roles', $student), ['roles' => ['provost']])->assertForbidden();
    $this->actingAs($this->admin)->get(route('users.index'))->assertDontSee($student->email);
});

test('the super admin cannot remove their own super admin role or leave a user with none', function () {
    $this->actingAs($this->admin)->put(route('users.roles', $this->admin), ['roles' => ['provost']])
        ->assertSessionHasErrors('roles');
    expect($this->admin->fresh()->hasRole(RoleName::SuperAdmin))->toBeTrue();

    $this->actingAs($this->admin)->put(route('users.roles', $this->staff), [])
        ->assertSessionHasErrors('roles');
    expect($this->staff->fresh()->hasRole(RoleName::OfficeStaff))->toBeTrue();
});
