<?php

use App\Enums\ComplaintStatus as S;
use App\Enums\RoleName;
use App\Models\Complaint;
use App\Models\User;

beforeEach(function () {
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->provost = User::factory()->withRole(RoleName::Provost)->create();
    $this->tutor = User::factory()->withRole(RoleName::HouseTutor)->create();
    $this->student = User::factory()->student()->create();
});

function moveTo($test, User $user, Complaint $complaint, S $to, ?string $note = 'note')
{
    return $test->actingAs($user)->post(route('complaints.status', $complaint), ['to_status' => $to->value, 'note' => $note]);
}

test('BR-06: the full happy path is allowed and logged', function () {
    $complaint = Complaint::factory()->create(['student_profile_id' => $this->student->studentProfile->id]);

    foreach ([S::UnderReview, S::InProgress, S::Resolved, S::Closed] as $to) {
        moveTo($this, $this->office, $complaint, $to, 'step')->assertSessionHasNoErrors();
        expect($complaint->fresh()->status)->toBe($to);
    }

    $logs = $complaint->statusLogs()->get();
    expect($logs)->toHaveCount(4)
        ->and($logs->last()->from_status)->toBe(S::Resolved)
        ->and($logs->last()->to_status)->toBe(S::Closed)
        ->and($logs->last()->changed_by)->toBe($this->office->id);
});

test('BR-06: valid transitions', function (S $from, S $to) {
    $complaint = Complaint::factory()->status($from)->create();

    moveTo($this, $this->office, $complaint, $to)->assertSessionHasNoErrors();
    expect($complaint->fresh()->status)->toBe($to);
})->with([
    [S::Submitted, S::UnderReview],
    [S::Submitted, S::Rejected],
    [S::UnderReview, S::InProgress],
    [S::UnderReview, S::Rejected],
    [S::InProgress, S::Resolved],
    [S::Resolved, S::Closed],
]);

test('BR-06: invalid transitions are refused with a clear message', function (S $from, S $to) {
    $complaint = Complaint::factory()->status($from)->create();

    moveTo($this, $this->office, $complaint, $to)
        ->assertSessionHasErrors(['to_status' => "A complaint cannot move from {$from->label()} to {$to->label()}."]);

    expect($complaint->fresh()->status)->toBe($from)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    [S::Submitted, S::InProgress],
    [S::Submitted, S::Resolved],
    [S::Submitted, S::Closed],
    [S::UnderReview, S::Resolved],
    [S::InProgress, S::Closed],
    [S::InProgress, S::Rejected],
    [S::Closed, S::Resolved],
    [S::Rejected, S::UnderReview],
    [S::Submitted, S::Submitted],
]);

test('BR-07: rejecting needs a reason and resolving needs a note', function () {
    $submitted = Complaint::factory()->create();
    $inProgress = Complaint::factory()->status(S::InProgress)->create();

    moveTo($this, $this->office, $submitted, S::Rejected, '')
        ->assertSessionHasErrors(['note' => 'A reason is required to reject a complaint.']);
    moveTo($this, $this->office, $inProgress, S::Resolved, '  ')
        ->assertSessionHasErrors(['note' => 'A resolution note is required to mark a complaint resolved.']);

    moveTo($this, $this->office, $inProgress, S::Resolved, 'Pipe replaced')->assertSessionHasNoErrors();
    expect($inProgress->fresh()->resolved_at)->not->toBeNull();
});

test('the student can reopen a resolved complaint within 7 days', function () {
    $complaint = Complaint::factory()->status(S::Resolved)->create([
        'student_profile_id' => $this->student->studentProfile->id,
        'resolved_at' => now()->subDays(6),
    ]);

    moveTo($this, $this->student, $complaint, S::InProgress, 'Still leaking')->assertSessionHasNoErrors();
    expect($complaint->fresh())->status->toBe(S::InProgress)->resolved_at->toBeNull();
});

test('the student cannot reopen after 7 days', function () {
    $complaint = Complaint::factory()->status(S::Resolved)->create([
        'student_profile_id' => $this->student->studentProfile->id,
        'resolved_at' => now()->subDays(8),
    ]);

    moveTo($this, $this->student, $complaint, S::InProgress)
        ->assertSessionHasErrors(['to_status' => 'A resolved complaint can only be reopened within 7 days of resolution.']);
});

test('only the owner may reopen; staff cannot', function () {
    $complaint = Complaint::factory()->status(S::Resolved)->create();

    moveTo($this, $this->office, $complaint, S::InProgress)->assertForbidden();
    moveTo($this, $this->student, $complaint, S::InProgress)->assertForbidden();
});

test('the student cannot otherwise change status (403)', function () {
    $complaint = Complaint::factory()->status(S::InProgress)->create(['student_profile_id' => $this->student->studentProfile->id]);

    moveTo($this, $this->student, $complaint, S::Resolved)->assertForbidden();
    expect($complaint->fresh()->status)->toBe(S::InProgress);
});

test('provost can close and reject', function () {
    $resolved = Complaint::factory()->status(S::Resolved)->create();
    $submitted = Complaint::factory()->create();

    moveTo($this, $this->provost, $resolved, S::Closed)->assertSessionHasNoErrors();
    moveTo($this, $this->provost, $submitted, S::Rejected, 'Duplicate')->assertSessionHasNoErrors();
});

test('BR-08: an assigned tutor can progress the complaint but not close or reject it', function () {
    $complaint = Complaint::factory()->assignedTo($this->tutor)->create();

    moveTo($this, $this->tutor, $complaint, S::UnderReview)->assertSessionHasNoErrors();
    moveTo($this, $this->tutor, $complaint, S::InProgress)->assertSessionHasNoErrors();
    moveTo($this, $this->tutor, $complaint, S::Resolved, 'Fixed')->assertSessionHasNoErrors();
    moveTo($this, $this->tutor, $complaint, S::Closed)->assertForbidden();

    $other = Complaint::factory()->assignedTo($this->tutor)->create();
    moveTo($this, $this->tutor, $other, S::Rejected, 'No')->assertForbidden();
});

test('BR-08: a tutor cannot open or update a complaint not assigned to them', function () {
    $otherTutor = User::factory()->withRole(RoleName::HouseTutor)->create();
    $complaint = Complaint::factory()->assignedTo($otherTutor)->create(['title' => 'Not yours']);

    $this->actingAs($this->tutor)->get(route('complaints.show', $complaint))->assertForbidden();
    $this->actingAs($this->tutor)->get(route('complaints.index'))->assertDontSee('Not yours');
    moveTo($this, $this->tutor, $complaint, S::UnderReview)->assertForbidden();
    $this->actingAs($this->tutor)->post(route('complaints.comments', $complaint), ['body' => 'hi'])->assertForbidden();

    expect($complaint->fresh()->status)->toBe(S::Submitted);
});

test('the status form only offers moves the user may make', function () {
    $complaint = Complaint::factory()->status(S::Resolved)->assignedTo($this->tutor)->create();

    $this->actingAs($this->tutor)->get(route('complaints.show', $complaint))->assertDontSee('Update status');
    $this->actingAs($this->office)->get(route('complaints.show', $complaint))->assertSee('Update status')->assertDontSee('Reopen');
});
