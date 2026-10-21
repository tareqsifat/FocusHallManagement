<?php

use App\Enums\ResidencyStatus;
use App\Enums\RoleName;
use App\Models\Room;
use App\Models\SeatAllocation;
use App\Models\User;
use App\Services\SeatAllocationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->room = Room::factory()->capacity(2)->create();
    [$this->seatA, $this->seatB] = $this->room->seats()->orderBy('label')->get()->all();
});

function allocate($test, User $student, $seat)
{
    return $test->actingAs($test->office)
        ->from(route('students.show', $student->studentProfile))
        ->post(route('allocations.store', $student->studentProfile), ['seat_id' => $seat->id]);
}

test('allocating a seat makes the student resident', function () {
    $student = User::factory()->student()->create();

    allocate($this, $student, $this->seatA)->assertSessionHasNoErrors();

    expect($student->studentProfile->fresh()->residency_status)->toBe(ResidencyStatus::Resident)
        ->and($this->seatA->activeAllocation()->first()->student_profile_id)->toBe($student->studentProfile->id);
});

test('BR-01: a student cannot hold two seats', function () {
    $student = User::factory()->student()->create();
    allocate($this, $student, $this->seatA);

    allocate($this, $student, $this->seatB)
        ->assertSessionHasErrors(['seat_id' => 'This student already holds a seat. Vacate it first.']);

    expect(SeatAllocation::count())->toBe(1);
});

test('BR-02: a seat cannot be given to two students', function () {
    $first = User::factory()->student()->create();
    $second = User::factory()->student()->create();
    allocate($this, $first, $this->seatA);

    allocate($this, $second, $this->seatA)
        ->assertSessionHasErrors(['seat_id' => 'This seat is already occupied.']);

    expect($this->seatA->allocations()->count())->toBe(1);
});

test('BR-01 and BR-02 are backed by unique constraints in the database', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    app(SeatAllocationService::class)->allocate($student->studentProfile, $this->seatA, $this->office);

    $insert = fn ($seat, $profile) => DB::table('seat_allocations')->insert([
        'seat_id' => $seat->id, 'student_profile_id' => $profile->id,
        'allocated_at' => today(), 'allocated_by' => $this->office->id,
    ]);

    expect(fn () => $insert($this->seatB, $student->studentProfile))->toThrow(UniqueConstraintViolationException::class)
        ->and(fn () => $insert($this->seatA, $other->studentProfile))->toThrow(UniqueConstraintViolationException::class);
});

test('BR-02: a retired seat cannot be allocated, so a room never exceeds capacity', function () {
    $this->seatB->update(['is_active' => false]);
    $student = User::factory()->student()->create();

    allocate($this, $student, $this->seatB)->assertSessionHasErrors('seat_id');
});

test('BR-03: an unapproved student cannot be allocated a seat', function () {
    $pending = User::factory()->student()->pending()->create();

    allocate($this, $pending, $this->seatA)
        ->assertSessionHasErrors(['seat_id' => 'Only approved students can be allocated a seat.']);
});

test('BR-04: vacating ends the allocation, keeps history and frees the seat', function () {
    $student = User::factory()->student()->create();
    allocate($this, $student, $this->seatA);
    $allocation = SeatAllocation::first();

    $this->actingAs($this->office)->post(route('allocations.vacate', $allocation), [
        'vacated_at' => today()->toDateString(), 'reason' => 'Graduated',
    ])->assertSessionHasNoErrors();

    expect($allocation->fresh())
        ->vacated_at->not->toBeNull()
        ->vacate_reason->toBe('Graduated')
        ->vacated_by->toBe($this->office->id)
        ->and($student->studentProfile->fresh()->residency_status)->toBe(ResidencyStatus::Attached);

    // The seat can now go to someone else and both rows remain.
    allocate($this, User::factory()->student()->create(), $this->seatA)->assertSessionHasNoErrors();
    expect($this->seatA->allocations()->count())->toBe(2);
});

test('vacating needs a reason, a past date, and an active allocation', function () {
    $student = User::factory()->student()->create();
    allocate($this, $student, $this->seatA);
    $allocation = SeatAllocation::first();
    $url = route('allocations.vacate', $allocation);

    $this->actingAs($this->office)->post($url, ['vacated_at' => today()->toDateString()])->assertSessionHasErrors('reason');
    $this->actingAs($this->office)->post($url, ['vacated_at' => today()->addDay()->toDateString(), 'reason' => 'x'])->assertSessionHasErrors('vacated_at');
    $this->actingAs($this->office)->post($url, ['vacated_at' => today()->subDay()->toDateString(), 'reason' => 'x'])->assertSessionHasErrors('vacated_at');

    $this->actingAs($this->office)->post($url, ['vacated_at' => today()->toDateString(), 'reason' => 'Left']);
    $this->actingAs($this->office)->post($url, ['vacated_at' => today()->toDateString(), 'reason' => 'Again'])
        ->assertSessionHasErrors(['vacated_at' => 'This allocation has already been vacated.']);
});

test('students and house tutors cannot allocate or vacate (403)', function () {
    $student = User::factory()->student()->create();
    $tutor = User::factory()->withRole(RoleName::HouseTutor)->create();

    $this->actingAs($student)->post(route('allocations.store', $student->studentProfile), ['seat_id' => $this->seatA->id])->assertForbidden();
    $this->actingAs($tutor)->post(route('allocations.store', $student->studentProfile), ['seat_id' => $this->seatA->id])->assertForbidden();

    allocate($this, $student, $this->seatA);
    $this->actingAs($student)->post(route('allocations.vacate', SeatAllocation::first()), ['vacated_at' => today()->toDateString(), 'reason' => 'x'])->assertForbidden();
    expect(SeatAllocation::first()->isActive())->toBeTrue();
});
