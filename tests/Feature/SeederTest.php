<?php

use App\Enums\ComplaintStatus;
use App\Enums\NoticeStatus;
use App\Enums\ResidencyStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Block;
use App\Models\Club;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Notice;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed();
});

test('seed data meets Section 10', function () {
    expect(Block::count())->toBe(3)
        ->and(Room::count())->toBeGreaterThanOrEqual(30)
        ->and(Room::min('capacity'))->toBeGreaterThanOrEqual(2)
        ->and(Room::max('capacity'))->toBeLessThanOrEqual(4);

    $students = StudentProfile::with('user')->get();
    expect($students->count())->toBeGreaterThanOrEqual(60)
        ->and($students->where('residency_status', ResidencyStatus::Resident)->count())->toBe(40)
        ->and($students->filter(fn ($s) => $s->residency_status === ResidencyStatus::Attached && $s->user->status === UserStatus::Active)->count())->toBe(15)
        ->and($students->filter(fn ($s) => $s->user->status === UserStatus::Pending)->count())->toBe(5);

    expect(HouseTutor::count())->toBe(5)
        ->and(HouseTutor::has('blocks')->count())->toBe(5)
        ->and(Block::has('houseTutors')->count())->toBe(3);

    expect(Club::count())->toBe(4);
    foreach (Club::withCount(['activeMemberships', 'memberships as pending_count' => fn ($q) => $q->where('status', 'pending')])->get() as $club) {
        expect($club->active_memberships_count)->toBeGreaterThan(0)->and($club->pending_count)->toBeGreaterThan(0);
    }

    expect(Complaint::count())->toBeGreaterThanOrEqual(20)
        ->and(Complaint::distinct()->pluck('status')->count())->toBe(count(ComplaintStatus::cases()))
        ->and(Complaint::overdue()->count())->toBeGreaterThanOrEqual(3)
        ->and(Complaint::where('is_anonymous', true)->count())->toBe(2);

    expect(Notice::count())->toBeGreaterThanOrEqual(10)
        ->and(Notice::where('status', NoticeStatus::Draft)->count())->toBe(2)
        ->and(Notice::where('is_pinned', true)->count())->toBe(2);
});

test('all six demo accounts log in with "password" and hold the right roles', function (string $email, RoleName $role) {
    $this->post('/login', ['email' => $email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', $email)->first();
    $this->assertAuthenticatedAs($user);
    expect($user->hasRole($role))->toBeTrue();
    $this->get(route('dashboard'))->assertOk();
})->with([
    ['admin@kjh.test', RoleName::SuperAdmin],
    ['provost@kjh.test', RoleName::Provost],
    ['office@kjh.test', RoleName::OfficeStaff],
    ['tutor@kjh.test', RoleName::HouseTutor],
    ['clubadmin@student.du.test', RoleName::ClubAdmin],
    ['student@student.du.test', RoleName::Student],
]);

test('demo accounts match their descriptions', function () {
    $tutor = User::where('email', 'tutor@kjh.test')->first();
    expect($tutor->houseTutor->blocks->pluck('name')->all())->toBe(['North']);

    $clubAdmin = User::where('email', 'clubadmin@student.du.test')->first();
    expect($clubAdmin->hasRole(RoleName::Student))->toBeTrue()
        ->and($clubAdmin->administeredClubs()->count())->toBe(1);

    $student = User::where('email', 'student@student.du.test')->first();
    expect($student->studentProfile->residency_status)->toBe(ResidencyStatus::Resident)
        ->and(Complaint::where('assigned_to', $tutor->id)->exists())->toBeTrue();
});

test('tracking IDs follow submission order', function () {
    $ids = Complaint::orderBy('created_at')->orderBy('id')->pluck('tracking_id')->all();
    $sorted = $ids;
    sort($sorted);

    expect($ids)->toBe($sorted)->and($ids[0])->toBe('KJH-'.now()->year.'-0001');
});

test('dashboard figures are consistent with the seeded data', function () {
    $hall = app(DashboardService::class)->hallFigures();
    $complaints = app(DashboardService::class)->complaintFigures();

    expect($hall['occupied_seats'])->toBe(40)
        ->and($hall['pending_registrations'])->toBe(5)
        ->and($complaints['open_total'])->toBe(Complaint::open()->count())
        ->and($complaints['overdue'])->toBe(Complaint::overdue()->count())
        ->and($complaints['avg_resolution_days'])->not->toBeNull();
});

test('the demo tutor sees an anonymous complaint without the name; the provost sees it', function () {
    $complaint = Complaint::where('is_anonymous', true)->where('assigned_to', User::where('email', 'tutor@kjh.test')->value('id'))->firstOrFail();
    $name = $complaint->student->user->name;

    $this->actingAs(User::where('email', 'tutor@kjh.test')->first())->get(route('complaints.show', $complaint))
        ->assertOk()->assertSee('Anonymous student')->assertDontSee($name);
    $this->actingAs(User::where('email', 'provost@kjh.test')->first())->get(route('complaints.show', $complaint))
        ->assertOk()->assertSee($name);
});
