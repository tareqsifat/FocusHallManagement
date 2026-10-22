<?php

use App\Enums\RoleName;
use App\Models\User;

test('a refused page shows the app 403 page with a way back', function () {
    $student = User::factory()->withRole(RoleName::Student)->create();

    $this->actingAs($student)->get(route('users.index'))
        ->assertForbidden()
        ->assertSee('<title>Access denied · '.config('app.name').'</title>', false)
        ->assertSee('Back to dashboard');
});

test('an unknown page shows the app 404 page to guests', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Go to the notice board');
});

test('signed-in pages name the page in the browser title', function () {
    $staff = User::factory()->withRole(RoleName::OfficeStaff)->create();

    $this->actingAs($staff)->get(route('complaints.index'))
        ->assertSee('<title>Complaints · '.config('app.name').'</title>', false);
});
