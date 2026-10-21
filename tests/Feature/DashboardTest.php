<?php

use App\Enums\ComplaintCategory as Cat;
use App\Enums\ComplaintStatus as S;
use App\Enums\RoleName;
use App\Models\Block;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Room;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\SeatAllocationService;

beforeEach(function () {
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->north = Block::factory()->create(['name' => 'North']);
    $this->south = Block::factory()->create(['name' => 'South']);
    $this->northRoom = Room::factory()->for($this->north)->capacity(4)->create();
    $this->southRoom = Room::factory()->for($this->south)->capacity(4)->create();

    // 3 of 8 seats occupied.
    $seats = $this->northRoom->seats()->orderBy('label')->get();
    foreach ([0, 1] as $i) {
        app(SeatAllocationService::class)->allocate(User::factory()->student()->create()->studentProfile, $seats[$i], $this->office);
    }
    app(SeatAllocationService::class)->allocate(User::factory()->student()->create()->studentProfile, $this->southRoom->seats()->first(), $this->office);

    User::factory()->student()->pending()->count(2)->create();

    $complaint = fn (Room $room, S $status, Cat $category, array $extra = []) => Complaint::factory()
        ->status($status)->create(array_merge(['room_id' => $room->id, 'category' => $category], $extra));

    // North: 2 open water (1 overdue), 1 open internet, 1 resolved 2 days after submission.
    $complaint($this->northRoom, S::Submitted, Cat::Water, ['created_at' => now()->subDays(9)]);
    $complaint($this->northRoom, S::InProgress, Cat::Water);
    $complaint($this->northRoom, S::UnderReview, Cat::Internet);
    $complaint($this->northRoom, S::Resolved, Cat::Water, ['created_at' => now()->subDays(3), 'resolved_at' => now()->subDay()]);
    // South: 1 open electricity (overdue), 1 closed 4 days after submission, 1 rejected.
    $complaint($this->southRoom, S::Submitted, Cat::Electricity, ['created_at' => now()->subDays(12)]);
    $complaint($this->southRoom, S::Closed, Cat::Furniture, ['created_at' => now()->subDays(6), 'resolved_at' => now()->subDays(2)]);
    $complaint($this->southRoom, S::Rejected, Cat::Other);
    // Resolved 40 days ago: outside the 30-day window.
    $complaint($this->southRoom, S::Closed, Cat::Water, ['created_at' => now()->subDays(50), 'resolved_at' => now()->subDays(40)]);
});

test('hall figures match the data', function () {
    expect(app(DashboardService::class)->hallFigures())->toBe([
        'total_seats' => 8,
        'occupied_seats' => 3,
        'occupancy_percent' => 37.5,
        'pending_registrations' => 2,
    ]);
});

test('complaint figures for the whole hall', function () {
    $figures = app(DashboardService::class)->complaintFigures();

    expect($figures['open_total'])->toBe(4)
        ->and($figures['open_by_category'])->toBe([
            'Water' => 2, 'Electricity' => 1, 'Internet' => 1, 'Cleanliness' => 0,
            'Furniture' => 0, 'Security' => 0, 'Other' => 0,
        ])
        ->and($figures['overdue'])->toBe(2)
        ->and($figures['resolved_last_30_days'])->toBe(2)
        ->and($figures['avg_resolution_days'])->toBe(3.0); // (2 + 4) / 2
});

test('a house tutor sees complaint figures for their blocks only', function () {
    $figures = app(DashboardService::class)->complaintFigures([$this->north->id]);

    expect($figures['open_total'])->toBe(3)
        ->and($figures['open_by_category']['Electricity'])->toBe(0)
        ->and($figures['overdue'])->toBe(1)
        ->and($figures['avg_resolution_days'])->toBe(2.0);

    $tutor = HouseTutor::factory()->create();
    $tutor->blocks()->attach($this->north);
    $this->actingAs($tutor->user)->get(route('dashboard'))
        ->assertOk()->assertSee('Your blocks: North')->assertDontSee('Pending registrations');
});

test('average resolution is empty when nothing was resolved recently', function () {
    expect(app(DashboardService::class)->complaintFigures([Block::factory()->create()->id])['avg_resolution_days'])->toBeNull();
});

test('provost and office staff see the full dashboard', function () {
    foreach ([$this->office, User::factory()->withRole(RoleName::Provost)->create()] as $user) {
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()->assertSee('Whole hall')->assertSee('Pending registrations')->assertSee('37.5%');
    }
});

test('students get a personal home page, not hall figures', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('dashboard'))
        ->assertOk()->assertSee('Your seat')->assertDontSee('Pending registrations')->assertDontSee('Occupancy');
});
