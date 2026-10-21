<?php

use App\Enums\RoleName;
use App\Models\Block;
use App\Models\Room;
use App\Models\User;
use App\Services\SeatAllocationService;

beforeEach(function () {
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->block = Block::factory()->create();
});

function roomData(Block $block, array $overrides = []): array
{
    return array_merge(['block_id' => $block->id, 'room_no' => '101', 'floor' => 1, 'capacity' => 3], $overrides);
}

test('creating a room creates one seat per capacity slot', function () {
    $this->actingAs($this->office)->post(route('rooms.store'), roomData($this->block))->assertSessionHasNoErrors();

    expect(Room::first()->seats()->orderBy('label')->pluck('label')->all())->toBe(['A', 'B', 'C']);
});

test('room numbers are unique within a block', function () {
    Room::factory()->for($this->block)->create(['room_no' => '101']);

    $this->actingAs($this->office)->post(route('rooms.store'), roomData($this->block))->assertSessionHasErrors('room_no');
});

test('BR-05: capacity cannot drop below occupied seats', function () {
    $room = Room::factory()->for($this->block)->capacity(3)->create();
    $seats = $room->seats()->orderBy('label')->get();
    $service = app(SeatAllocationService::class);
    $service->allocate(User::factory()->student()->create()->studentProfile, $seats[0], $this->office);
    $service->allocate(User::factory()->student()->create()->studentProfile, $seats[2], $this->office);

    $this->actingAs($this->office)->put(route('rooms.update', $room), roomData($this->block, ['room_no' => $room->room_no, 'capacity' => 1]))
        ->assertSessionHasErrors(['capacity' => 'Capacity cannot be lower than the 2 currently occupied seats.']);

    // Dropping to 2 retires the free seat B, not the occupied seat C.
    $this->actingAs($this->office)->put(route('rooms.update', $room), roomData($this->block, ['room_no' => $room->room_no, 'capacity' => 2]))
        ->assertSessionHasNoErrors();
    expect($room->fresh()->capacity)->toBe(2)
        ->and($room->seats()->where('is_active', true)->orderBy('label')->pluck('label')->all())->toBe(['A', 'C']);

    // Raising it again restores B before adding D.
    $this->actingAs($this->office)->put(route('rooms.update', $room), roomData($this->block, ['room_no' => $room->room_no, 'capacity' => 4]));
    expect($room->seats()->where('is_active', true)->orderBy('label')->pluck('label')->all())->toBe(['A', 'B', 'C', 'D']);
});

test('provost can view but not change hall structure', function () {
    $provost = User::factory()->withRole(RoleName::Provost)->create();
    $room = Room::factory()->for($this->block)->create();

    $this->actingAs($provost)->get(route('rooms.index'))->assertOk();
    $this->actingAs($provost)->get(route('rooms.show', $room))->assertOk();
    $this->actingAs($provost)->get(route('rooms.create'))->assertForbidden();
    $this->actingAs($provost)->post(route('blocks.store'), ['name' => 'East', 'code' => 'E'])->assertForbidden();
});

test('students cannot see hall structure or the student list', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('rooms.index'))->assertForbidden();
    $this->actingAs($student)->get(route('students.index'))->assertForbidden();
});

test('a student can view their own profile but not another student', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    $this->actingAs($student)->get(route('students.show', $student->studentProfile))->assertOk();
    $this->actingAs($student)->get(route('students.show', $other->studentProfile))->assertForbidden();
});

test('student list searches and filters', function () {
    $resident = User::factory()->student()->create(['name' => 'Arif Hossain']);
    $attached = User::factory()->student()->create(['name' => 'Bithi Akter']);
    $room = Room::factory()->for($this->block)->create();
    app(SeatAllocationService::class)->allocate($resident->studentProfile, $room->seats()->first(), $this->office);

    $this->actingAs($this->office)->get(route('students.index', ['search' => 'Arif']))
        ->assertSee('Arif Hossain')->assertDontSee('Bithi Akter');
    $this->actingAs($this->office)->get(route('students.index', ['search' => $attached->studentProfile->registration_no]))
        ->assertSee('Bithi Akter')->assertDontSee('Arif Hossain');
    $this->actingAs($this->office)->get(route('students.index', ['status' => 'attached']))
        ->assertSee('Bithi Akter')->assertDontSee('Arif Hossain');
    $this->actingAs($this->office)->get(route('students.index', ['block_id' => $this->block->id]))
        ->assertSee('Arif Hossain')->assertDontSee('Bithi Akter');
});

test('office pages render', function () {
    $room = Room::factory()->for($this->block)->create();
    $student = User::factory()->student()->create();

    foreach ([route('blocks.index'), route('blocks.create'), route('blocks.edit', $this->block), route('rooms.index'),
        route('rooms.create'), route('rooms.show', $room), route('rooms.edit', $room), route('students.index'),
        route('students.show', $student->studentProfile)] as $url) {
        $this->actingAs($this->office)->get($url)->assertOk();
    }
});
