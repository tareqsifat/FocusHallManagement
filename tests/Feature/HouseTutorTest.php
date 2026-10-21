<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Block;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Room;
use App\Models\User;
use App\Services\SeatAllocationService;

beforeEach(function () {
    $this->provost = User::factory()->withRole(RoleName::Provost)->create();
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->north = Block::factory()->create(['name' => 'North']);
    $this->south = Block::factory()->create(['name' => 'South']);
});

function tutorData(array $blockIds, array $overrides = []): array
{
    return array_merge([
        'name' => 'Dr. Farhana Kabir',
        'email' => 'farhana@kjh.test',
        'password' => 'password',
        'designation' => 'Assistant Professor',
        'department' => 'Physics',
        'phone' => '01811223344',
        'block_ids' => $blockIds,
    ], $overrides);
}

/** A resident student whose seat is in the given block. */
function residentIn(Block $block, string $name): User
{
    $student = User::factory()->student()->create(['name' => $name]);
    $room = Room::factory()->for($block)->create();
    app(SeatAllocationService::class)->allocate($student->studentProfile, $room->seats()->first(), User::factory()->create());

    return $student;
}

test('provost creates a house tutor assigned to blocks who can log in', function () {
    $this->actingAs($this->provost)->post(route('house-tutors.store'), tutorData([$this->north->id, $this->south->id]))
        ->assertSessionHasNoErrors();

    $tutor = HouseTutor::with('user', 'blocks')->first();
    expect($tutor->user->hasRole(RoleName::HouseTutor))->toBeTrue()
        ->and($tutor->blocks->pluck('name')->sort()->values()->all())->toBe(['North', 'South']);

    auth()->logout();
    $this->post('/login', ['email' => 'farhana@kjh.test', 'password' => 'password']);
    $this->assertAuthenticatedAs($tutor->user);
});

test('a block can have more than one tutor', function () {
    HouseTutor::factory()->count(2)->create()->each(fn ($t) => $t->blocks()->attach($this->north));

    expect($this->north->houseTutors()->count())->toBe(2);
});

test('tutor input is validated', function () {
    $this->actingAs($this->provost)->post(route('house-tutors.store'), tutorData([], ['phone' => '123']))
        ->assertSessionHasErrors(['phone', 'block_ids']);
});

test('editing a tutor changes details and blocks and keeps the password when left blank', function () {
    $this->actingAs($this->provost)->post(route('house-tutors.store'), tutorData([$this->north->id]));
    $tutor = HouseTutor::first();
    $hash = $tutor->user->password;

    $this->actingAs($this->provost)->put(route('house-tutors.update', $tutor), tutorData([$this->south->id], ['password' => '', 'designation' => 'Professor']))
        ->assertSessionHasNoErrors();

    $tutor->refresh();
    expect($tutor->designation)->toBe('Professor')
        ->and($tutor->blocks()->pluck('name')->all())->toBe(['South'])
        ->and($tutor->user->fresh()->password)->toBe($hash);
});

test('a deactivated tutor cannot log in or be assigned complaints', function () {
    $tutor = HouseTutor::factory()->create();

    $this->actingAs($this->provost)->post(route('house-tutors.toggle', $tutor));
    expect($tutor->user->fresh()->status)->toBe(UserStatus::Inactive);

    $this->actingAs($this->office)->post(route('complaints.assign', Complaint::factory()->create()), ['assignee_id' => $tutor->user_id])
        ->assertSessionHasErrors('assignee_id');

    auth()->logout();
    $this->post('/login', ['email' => $tutor->user->email, 'password' => 'password'])->assertSessionHasErrors('email');
});

test('only provost and super admin manage tutors (403 for office staff)', function () {
    $tutor = HouseTutor::factory()->create();

    $this->actingAs($this->office)->get(route('house-tutors.index'))->assertForbidden();
    $this->actingAs($this->office)->post(route('house-tutors.store'), tutorData([$this->north->id]))->assertForbidden();
    $this->actingAs($this->office)->post(route('house-tutors.toggle', $tutor))->assertForbidden();
    $this->actingAs(User::factory()->withRole(RoleName::SuperAdmin)->create())->get(route('house-tutors.index'))->assertOk();
});

test('BR-08: a tutor sees only students in their assigned blocks', function () {
    $tutor = HouseTutor::factory()->create();
    $tutor->blocks()->attach($this->north);
    $inNorth = residentIn($this->north, 'Nadia North');
    $inSouth = residentIn($this->south, 'Sohel South');
    $attached = User::factory()->student()->create(['name' => 'Arman Attached']);

    $this->actingAs($tutor->user)->get(route('students.index'))
        ->assertOk()->assertSee('Nadia North')->assertDontSee('Sohel South')->assertDontSee('Arman Attached');

    $this->actingAs($tutor->user)->get(route('students.show', $inNorth->studentProfile))->assertOk();
    $this->actingAs($tutor->user)->get(route('students.show', $inSouth->studentProfile))->assertForbidden();
    $this->actingAs($tutor->user)->get(route('students.show', $attached->studentProfile))->assertForbidden();
});

test('BR-08: a tutor with no blocks sees no students', function () {
    $tutor = HouseTutor::factory()->create();
    residentIn($this->north, 'Nadia North');

    $this->actingAs($tutor->user)->get(route('students.index'))->assertOk()->assertDontSee('Nadia North');
});

test('a tutor cannot allocate seats even for students they can see', function () {
    $tutor = HouseTutor::factory()->create();
    $tutor->blocks()->attach($this->north);
    $student = User::factory()->student()->create();

    $this->actingAs($tutor->user)->post(route('allocations.store', $student->studentProfile), ['seat_id' => 1])->assertForbidden();
});

test('tutor management pages render', function () {
    $tutor = HouseTutor::factory()->create();
    $tutor->blocks()->attach($this->north);

    $this->actingAs($this->provost)->get(route('house-tutors.index'))->assertOk()->assertSee($tutor->user->name)->assertSee('North');
    $this->actingAs($this->provost)->get(route('house-tutors.create'))->assertOk();
    $this->actingAs($this->provost)->get(route('house-tutors.edit', $tutor))->assertOk()->assertSee($tutor->designation);
});
