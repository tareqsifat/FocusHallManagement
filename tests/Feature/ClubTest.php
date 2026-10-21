<?php

use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use App\Services\ClubMembershipService;
use App\Services\ClubService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->superAdmin = User::factory()->withRole(RoleName::SuperAdmin)->create();
    $this->club = Club::factory()->create(['name' => 'Debating Club']);
    $this->clubAdmin = User::factory()->student()->create();
    app(ClubService::class)->syncAdmins($this->club, [$this->clubAdmin->id]);
    $this->student = User::factory()->student()->create();
});

function join_club($test, User $user, Club $club)
{
    return $test->actingAs($user)->from(route('clubs.show', $club))->post(route('clubs.join', $club));
}

test('the club directory is public and shows admin and member count', function () {
    $member = User::factory()->student()->create();
    join_club($this, $member, $this->club);
    app(ClubMembershipService::class)->approve(ClubMembership::first(), $this->clubAdmin);
    auth()->logout();

    $this->get(route('clubs.index'))->assertOk()->assertSee('Debating Club')->assertSee($this->clubAdmin->name)->assertSee('1 member');
    $this->get(route('clubs.show', $this->club))->assertOk()->assertSee('Log in to request membership');
});

test('a student requests to join and the club admin approves', function () {
    join_club($this, $this->student, $this->club)->assertSessionHasNoErrors();
    $membership = ClubMembership::first();
    expect($membership->status)->toBe(MembershipStatus::Pending);

    $this->actingAs($this->clubAdmin)->post(route('memberships.approve', $membership))->assertSessionHasNoErrors();
    expect($membership->fresh())
        ->status->toBe(MembershipStatus::Active)
        ->decided_by->toBe($this->clubAdmin->id);
});

test('BR-12: a student cannot request the same club twice while pending or active', function () {
    join_club($this, $this->student, $this->club);
    join_club($this, $this->student, $this->club)
        ->assertSessionHasErrors(['membership' => 'You already have a pending request for this club.']);

    $this->actingAs($this->clubAdmin)->post(route('memberships.approve', ClubMembership::first()));
    join_club($this, $this->student, $this->club)
        ->assertSessionHasErrors(['membership' => 'You are already a member of this club.']);

    expect(ClubMembership::count())->toBe(1);
});

test('BR-12: after a rejection or removal the student may ask again', function () {
    join_club($this, $this->student, $this->club);
    $this->actingAs($this->clubAdmin)->post(route('memberships.reject', ClubMembership::first()));

    join_club($this, $this->student, $this->club)->assertSessionHasNoErrors();
    expect(ClubMembership::count())->toBe(2);
});

test('BR-12 is backed by a unique index on open requests', function () {
    join_club($this, $this->student, $this->club);

    expect(fn () => DB::table('club_memberships')->insert([
        'club_id' => $this->club->id, 'student_profile_id' => $this->student->studentProfile->id, 'status' => 'active',
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('the club admin removes a member; decisions only apply to the right state', function () {
    join_club($this, $this->student, $this->club);
    $membership = ClubMembership::first();

    $this->actingAs($this->clubAdmin)->post(route('memberships.remove', $membership))
        ->assertSessionHasErrors(['membership' => 'This membership is pending, not active.']);

    $this->actingAs($this->clubAdmin)->post(route('memberships.approve', $membership));
    $this->actingAs($this->clubAdmin)->post(route('memberships.remove', $membership))->assertSessionHasNoErrors();
    expect($membership->fresh()->status)->toBe(MembershipStatus::Removed);
});

test('BR-12: a club admin cannot manage another club (403)', function () {
    $otherClub = Club::factory()->create();
    join_club($this, $this->student, $otherClub);
    $membership = ClubMembership::first();

    $this->actingAs($this->clubAdmin)->get(route('clubs.manage', $otherClub))->assertForbidden();
    $this->actingAs($this->clubAdmin)->get(route('clubs.edit', $otherClub))->assertForbidden();
    $this->actingAs($this->clubAdmin)->post(route('memberships.approve', $membership))->assertForbidden();
    expect($membership->fresh()->status)->toBe(MembershipStatus::Pending);
});

test('students cannot decide requests; staff cannot join', function () {
    join_club($this, $this->student, $this->club);

    $this->actingAs($this->student)->post(route('memberships.approve', ClubMembership::first()))->assertForbidden();
    $this->actingAs(User::factory()->withRole(RoleName::OfficeStaff)->create())->post(route('clubs.join', $this->club))->assertForbidden();
});

test('BR-03: a pending student cannot join a club', function () {
    $pending = User::factory()->student()->pending()->create();

    $this->actingAs($pending)->post(route('clubs.join', $this->club))->assertRedirect(route('login'));
    expect(ClubMembership::count())->toBe(0);
});

test('super admin assigns club admins and the role follows', function () {
    $newAdmin = User::factory()->student()->create();

    $this->actingAs($this->superAdmin)->put(route('clubs.admins', $this->club), ['admin_ids' => [$newAdmin->id]])
        ->assertSessionHasNoErrors();

    expect($newAdmin->fresh()->hasRole(RoleName::ClubAdmin))->toBeTrue()
        ->and($this->clubAdmin->fresh()->hasRole(RoleName::ClubAdmin))->toBeFalse()
        ->and($this->club->admins()->pluck('users.id')->all())->toBe([$newAdmin->id]);
});

test('a club admin keeps the role while leading another club', function () {
    $second = Club::factory()->create();
    app(ClubService::class)->syncAdmins($second, [$this->clubAdmin->id]);

    $this->actingAs($this->superAdmin)->put(route('clubs.admins', $this->club), ['admin_ids' => []]);

    expect($this->clubAdmin->fresh()->hasRole(RoleName::ClubAdmin))->toBeTrue();
});

test('only approved students can be club admins; only super admin assigns', function () {
    $office = User::factory()->withRole(RoleName::OfficeStaff)->create();

    $this->actingAs($this->superAdmin)->put(route('clubs.admins', $this->club), ['admin_ids' => [$office->id]])
        ->assertSessionHasErrors('admin_ids.0');
    $this->actingAs($this->clubAdmin)->put(route('clubs.admins', $this->club), ['admin_ids' => [$this->student->id]])
        ->assertForbidden();
});

test('the club admin edits their club page and uploads a logo', function () {
    $this->actingAs($this->clubAdmin)->put(route('clubs.update', $this->club), [
        'name' => 'Debating Society', 'description' => 'Weekly debates.', 'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertSessionHasNoErrors();

    $club = $this->club->fresh();
    expect($club->name)->toBe('Debating Society')->and($club->logo_path)->toStartWith('club-logos/');
    auth()->logout();
    $this->get(route('clubs.logo', $club))->assertOk();
});

test('only super admin creates clubs', function () {
    $this->actingAs($this->clubAdmin)->post(route('clubs.store'), ['name' => 'Chess', 'description' => 'x'])->assertForbidden();
    $this->actingAs($this->superAdmin)->post(route('clubs.store'), ['name' => 'Chess', 'description' => 'Board games.'])
        ->assertSessionHasNoErrors();
    expect(Club::where('name', 'Chess')->exists())->toBeTrue();
});

test('club pages render', function () {
    join_club($this, $this->student, $this->club);

    $this->actingAs($this->clubAdmin)->get(route('clubs.manage', $this->club))->assertOk()->assertSee($this->student->name);
    $this->actingAs($this->superAdmin)->get(route('clubs.create'))->assertOk();
    $this->actingAs($this->superAdmin)->get(route('clubs.edit', $this->club))->assertOk()->assertSee('Club admins');
    $this->actingAs($this->student)->get(route('clubs.show', $this->club))->assertOk()->assertSee('Your request is pending');
});
