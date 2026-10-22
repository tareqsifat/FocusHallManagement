<?php

use App\Enums\ComplaintStatus;
use App\Enums\RoleName;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Room;
use App\Models\User;
use App\Services\SeatAllocationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->student = User::factory()->student()->create();
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
});

function complaintData(array $overrides = []): array
{
    return array_merge([
        'category' => 'water',
        'title' => 'No water on 3rd floor',
        'description' => 'Taps have been dry since morning.',
        'location_text' => 'Third floor washroom',
    ], $overrides);
}

test('a student submits a complaint and gets a KJH-YYYY-NNNN tracking ID', function () {
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData())->assertRedirect();
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData());

    $ids = Complaint::orderBy('id')->pluck('tracking_id')->all();
    $year = now()->year;
    expect($ids)->toBe(["KJH-{$year}-0001", "KJH-{$year}-0002"]);

    $complaint = Complaint::first();
    expect($complaint->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->first()->to_status)->toBe(ComplaintStatus::Submitted);
});

test('BR-09: the tracking sequence restarts each year', function () {
    $this->travelTo(now()->setDate(2026, 12, 31));
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData());
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData());

    $this->travelTo(now()->setDate(2027, 1, 1));
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData());

    expect(Complaint::orderBy('id')->pluck('tracking_id')->all())
        ->toBe(['KJH-2026-0001', 'KJH-2026-0002', 'KJH-2027-0001']);
});

test('a resident student gets their room filled in automatically', function () {
    $room = Room::factory()->create();
    app(SeatAllocationService::class)->allocate($this->student->studentProfile, $room->seats()->first(), $this->office);

    $this->actingAs($this->student)->post(route('complaints.store'), complaintData(['location_text' => '']))
        ->assertSessionHasNoErrors();

    expect(Complaint::first())->room_id->toBe($room->id)->location_text->toBeNull();
});

test('an attached student must give a location', function () {
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData(['location_text' => '']))
        ->assertSessionHasErrors('location_text');
});

test('staff and tutors cannot submit complaints (403)', function () {
    $this->actingAs($this->office)->post(route('complaints.store'), complaintData())->assertForbidden();
});

test('BR-13: photos are validated and stored privately under a generated name', function () {
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData([
        'photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
    ]))->assertSessionHasErrors('photo');

    $this->actingAs($this->student)->post(route('complaints.store'), complaintData([
        'photo' => UploadedFile::fake()->image('big.jpg')->size(3000),
    ]))->assertSessionHasErrors('photo');

    $this->actingAs($this->student)->post(route('complaints.store'), complaintData([
        'photo' => UploadedFile::fake()->image('my leak.jpg'),
    ]))->assertSessionHasNoErrors();

    $complaint = Complaint::first();
    expect($complaint->photo_path)->toStartWith('complaints/')->not->toContain('my leak');
    Storage::disk('local')->assertExists($complaint->photo_path);
});

test('complaint photos are only served to people who may view the complaint', function () {
    $this->actingAs($this->student)->post(route('complaints.store'), complaintData(['photo' => UploadedFile::fake()->image('a.png')]));
    $complaint = Complaint::first();
    $stranger = User::factory()->student()->create();

    $this->actingAs($this->student)->get(route('complaints.photo', $complaint))->assertOk();
    $this->actingAs($this->office)->get(route('complaints.photo', $complaint))->assertOk();
    $this->actingAs($stranger)->get(route('complaints.photo', $complaint))->assertForbidden();
    auth()->logout();
    $this->get(route('complaints.photo', $complaint))->assertRedirect(route('login'));
});

test('students see only their own complaints', function () {
    $mine = Complaint::factory()->create(['student_profile_id' => $this->student->studentProfile->id, 'title' => 'Mine']);
    $theirs = Complaint::factory()->create(['title' => 'Someone else']);

    $this->actingAs($this->student)->get(route('complaints.index'))->assertSee('Mine')->assertDontSee('Someone else');
    $this->actingAs($this->student)->get(route('complaints.show', $theirs))->assertForbidden();
    $this->actingAs($this->student)->get(route('complaints.show', $mine))->assertOk();
});

test('complaints older than 7 days and still open are flagged overdue', function () {
    $old = Complaint::factory()->create(['created_at' => now()->subDays(8), 'title' => 'Old leak']);
    $oldResolved = Complaint::factory()->status(ComplaintStatus::Resolved)->create(['created_at' => now()->subDays(10), 'title' => 'Old fixed']);
    $fresh = Complaint::factory()->create(['created_at' => now()->subDays(6), 'title' => 'New leak']);

    expect($old->isOverdue())->toBeTrue()
        ->and($oldResolved->isOverdue())->toBeFalse()
        ->and($fresh->isOverdue())->toBeFalse()
        ->and(Complaint::overdue()->pluck('id')->all())->toBe([$old->id])
        ->and($old->daysOpen())->toBe(8);

    $this->actingAs($this->office)->get(route('complaints.index', ['overdue' => 1]))
        ->assertSee('Old leak')->assertDontSee('New leak')->assertDontSee('Old fixed');
});

test('both the student and staff can comment, shown in time order', function () {
    $complaint = Complaint::factory()->create(['student_profile_id' => $this->student->studentProfile->id]);

    $this->actingAs($this->student)->post(route('complaints.comments', $complaint), ['body' => 'Still no water.']);
    $this->travel(1)->minutes();
    $this->actingAs($this->office)->post(route('complaints.comments', $complaint), ['body' => 'Plumber on the way.']);

    $this->actingAs($this->student)->get(route('complaints.show', $complaint))
        ->assertSeeInOrder(['Still no water.', 'Plumber on the way.']);
});

test('office staff assign a complaint to a house tutor', function () {
    $tutor = HouseTutor::factory()->create()->user;
    $complaint = Complaint::factory()->create();

    $this->actingAs($this->office)->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertSessionHasNoErrors();
    expect($complaint->fresh()->assigned_to)->toBe($tutor->id);

    // Students are not valid assignees, and students cannot assign.
    $this->actingAs($this->office)->post(route('complaints.assign', $complaint), ['assignee_id' => $this->student->id])
        ->assertSessionHasErrors('assignee_id');
    $this->actingAs($this->student)->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertForbidden();
});

test('a tutor can only be assigned complaints from rooms in their own blocks', function () {
    $room = Room::factory()->create();
    $ownTutor = HouseTutor::factory()->create();
    $ownTutor->blocks()->attach($room->block_id);
    $otherTutor = HouseTutor::factory()->create();
    $otherTutor->blocks()->attach(Room::factory()->create()->block_id);

    $complaint = Complaint::factory()->create(['room_id' => $room->id, 'location_text' => null]);

    $this->actingAs($this->office)->get(route('complaints.show', $complaint))
        ->assertSee($ownTutor->user->name)->assertDontSee($otherTutor->user->name);
    $this->actingAs($this->office)->post(route('complaints.assign', $complaint), ['assignee_id' => $otherTutor->user_id])
        ->assertSessionHasErrors('assignee_id');
    $this->actingAs($this->office)->post(route('complaints.assign', $complaint), ['assignee_id' => $ownTutor->user_id])
        ->assertSessionHasNoErrors();
    expect($complaint->fresh()->assigned_to)->toBe($ownTutor->user_id);
});

test('closed and rejected complaints cannot be reassigned or commented on', function (ComplaintStatus $status) {
    $tutor = HouseTutor::factory()->create()->user;
    $complaint = Complaint::factory()->status($status)->create(['student_profile_id' => $this->student->studentProfile->id]);

    $this->actingAs($this->office)->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])->assertForbidden();
    $this->actingAs($this->office)->post(route('complaints.comments', $complaint), ['body' => 'Late note'])->assertForbidden();
    $this->actingAs($this->student)->post(route('complaints.comments', $complaint), ['body' => 'Late note'])->assertForbidden();
    $this->actingAs($this->office)->get(route('complaints.show', $complaint))
        ->assertOk()->assertDontSee('Post comment')->assertDontSee('Reassign');

    expect($complaint->fresh()->assigned_to)->toBeNull()
        ->and($complaint->comments()->count())->toBe(0);
})->with([ComplaintStatus::Closed, ComplaintStatus::Rejected]);
