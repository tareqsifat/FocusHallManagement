<?php

use App\Enums\ComplaintStatus;
use App\Enums\RoleName;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Room;
use App\Models\User;
use App\Services\ComplaintStatusService;

beforeEach(function () {
    $this->student = User::factory()->student()->create(['name' => 'Hidden Reporter']);
    $this->tutor = HouseTutor::factory()->create()->user;
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();

    // Filed anonymously, assigned to the tutor, with a comment and a reopen by the student.
    $this->actingAs($this->student)->post(route('complaints.store'), [
        'category' => 'security', 'title' => 'Ragging in the corridor', 'description' => 'Senior students harass juniors at night.',
        'location_text' => 'Second floor corridor', 'is_anonymous' => '1',
    ])->assertSessionHasNoErrors();
    $this->complaint = Complaint::first();
    $this->complaint->forceFill(['assigned_to' => $this->tutor->id])->save();
    $this->actingAs($this->student)->post(route('complaints.comments', $this->complaint), ['body' => 'It happened again last night.']);
    $this->registrationNo = $this->student->studentProfile->registration_no;
});

test('the complaint is stored as anonymous', function () {
    expect($this->complaint->is_anonymous)->toBeTrue();
});

test('provost and super admin can see who filed an anonymous complaint', function (RoleName $role) {
    $viewer = User::factory()->withRole($role)->create();

    $this->actingAs($viewer)->get(route('complaints.show', $this->complaint))
        ->assertOk()->assertSee('Hidden Reporter')->assertSee($this->registrationNo)->assertSee('Anonymous');
    $this->actingAs($viewer)->get(route('complaints.index'))->assertSee('Hidden Reporter');
})->with([RoleName::Provost, RoleName::SuperAdmin]);

test('office staff and the assigned tutor cannot see who filed it anywhere on the page', function (string $who) {
    $viewer = $this->{$who};

    $this->actingAs($viewer)->get(route('complaints.show', $this->complaint))
        ->assertOk()
        ->assertDontSee('Hidden Reporter')
        ->assertDontSee($this->registrationNo)
        ->assertSee('Anonymous student')
        ->assertSee('It happened again last night.');
    $this->actingAs($viewer)->get(route('complaints.index'))
        ->assertSee('Ragging in the corridor')->assertDontSee('Hidden Reporter');
})->with(['office', 'tutor']);

test('the name stays hidden in the status log, including the student\'s own reopen', function () {
    $statuses = app(ComplaintStatusService::class);
    foreach ([ComplaintStatus::UnderReview, ComplaintStatus::InProgress] as $to) {
        $statuses->transition($this->complaint->fresh(), $to, null, $this->office);
    }
    $statuses->transition($this->complaint->fresh(), ComplaintStatus::Resolved, 'Warned the seniors.', $this->office);
    $this->actingAs($this->student)->post(route('complaints.status', $this->complaint), ['to_status' => 'in_progress', 'note' => 'Still happening.'])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->office)->get(route('complaints.show', $this->complaint))
        ->assertDontSee('Hidden Reporter')->assertSee($this->office->name);
});

test('the student who filed it still sees their own name', function () {
    $this->actingAs($this->student)->get(route('complaints.show', $this->complaint))
        ->assertOk()->assertSee('Hidden Reporter');
});

test('complaints are not anonymous unless the student asks', function () {
    $this->actingAs($this->student)->post(route('complaints.store'), [
        'category' => 'water', 'title' => 'Named complaint', 'description' => 'x', 'location_text' => 'Washroom',
    ]);
    $named = Complaint::where('title', 'Named complaint')->first();

    expect($named->is_anonymous)->toBeFalse();
    $this->actingAs($this->office)->get(route('complaints.show', $named))->assertSee('Hidden Reporter');
});

test('the room number of an anonymous complaint is shown only to those who may see the reporter', function () {
    $room = Room::factory()->create(['room_no' => '305']);
    $this->complaint->forceFill(['room_id' => $room->id])->save();

    foreach ([$this->office, $this->tutor] as $viewer) {
        $this->actingAs($viewer)->get(route('complaints.show', $this->complaint))
            ->assertSee("{$room->block->name} block")->assertDontSee('Room 305');
        $this->actingAs($viewer)->get(route('complaints.index'))->assertDontSee('Room 305');
    }

    $provost = User::factory()->withRole(RoleName::Provost)->create();
    $this->actingAs($provost)->get(route('complaints.show', $this->complaint))->assertSee('Room 305');
    $this->actingAs($this->student)->get(route('complaints.show', $this->complaint))->assertSee('Room 305');
});
