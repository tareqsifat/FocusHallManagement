<?php

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Enums\RoleName;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->office = User::factory()->withRole(RoleName::OfficeStaff)->create();
    $this->clubAdmin = User::factory()->student()->withRole(RoleName::ClubAdmin)->create();
});

function noticeData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Hall closed for Puja holidays',
        'body' => 'The dining hall will be closed from 1 to 5 October.',
        'category' => 'hall_office',
        'status' => 'published',
        'is_pinned' => '0',
    ], $overrides);
}

test('BR-11: drafts never appear on public pages', function () {
    $published = Notice::factory()->create(['title' => 'Public one']);
    $draft = Notice::factory()->draft()->create(['title' => 'Secret draft']);

    $this->get(route('notices.index'))->assertOk()->assertSee('Public one')->assertDontSee('Secret draft');
    $this->get(route('notices.index', ['search' => 'Secret']))->assertDontSee('Secret draft');
    $this->get(route('notices.show', $draft))->assertNotFound();
    $this->get(route('notices.show', $published))->assertOk();

    // Logged-in students cannot see drafts either.
    $this->actingAs(User::factory()->student()->create())->get(route('notices.show', $draft))->assertNotFound();
});

test('a draft attachment cannot be downloaded publicly', function () {
    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData([
        'status' => 'draft', 'attachment' => UploadedFile::fake()->create('routine.pdf', 100, 'application/pdf'),
    ]));
    $notice = Notice::first();
    auth()->logout();

    $this->get(route('notices.attachment', $notice))->assertNotFound();
});

test('the public list shows pinned first, then newest first', function () {
    Notice::factory()->create(['title' => 'Old notice', 'published_at' => now()->subDays(5)]);
    Notice::factory()->create(['title' => 'New notice', 'published_at' => now()->subDay()]);
    Notice::factory()->pinned()->create(['title' => 'Pinned old notice', 'published_at' => now()->subDays(10)]);

    $this->get(route('notices.index'))->assertSeeInOrder(['Pinned old notice', 'New notice', 'Old notice']);
});

test('the public list filters by category and searches by title', function () {
    Notice::factory()->create(['title' => 'Debate finals', 'category' => NoticeCategory::Club]);
    Notice::factory()->create(['title' => 'Water outage', 'category' => NoticeCategory::HallOffice]);

    $this->get(route('notices.index', ['category' => 'club']))->assertSee('Debate finals')->assertDontSee('Water outage');
    $this->get(route('notices.index', ['search' => 'water']))->assertSee('Water outage')->assertDontSee('Debate finals');
});

test('office staff publish a notice with a PDF that anyone can download', function () {
    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData([
        'attachment' => UploadedFile::fake()->create('my routine.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    $notice = Notice::first();
    expect($notice->status)->toBe(NoticeStatus::Published)
        ->and($notice->published_at)->not->toBeNull()
        ->and($notice->attachment_path)->toStartWith('notices/')->not->toContain('my routine');

    auth()->logout();
    $this->get(route('notices.attachment', $notice))->assertOk()->assertDownload("notice-{$notice->id}.pdf");
});

test('BR-13: attachments must be PDFs of at most 5 MB', function () {
    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData([
        'attachment' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
    ]))->assertSessionHasErrors('attachment');

    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData([
        'attachment' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
    ]))->assertSessionHasErrors('attachment');

    expect(Notice::count())->toBe(0);
});

test('publishing a draft later sets the published date', function () {
    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData(['status' => 'draft']));
    $notice = Notice::first();
    expect($notice->published_at)->toBeNull();

    $this->actingAs($this->office)->put(route('admin.notices.update', $notice), noticeData());
    expect($notice->fresh()->published_at)->not->toBeNull();
});

test('club admins can publish Club notices only, and edit only their own', function () {
    $this->actingAs($this->clubAdmin)->post(route('admin.notices.store'), noticeData(['category' => 'provost_office']))
        ->assertSessionHasErrors(['category' => 'You can only publish notices in the Club category.']);

    $this->actingAs($this->clubAdmin)->post(route('admin.notices.store'), noticeData(['category' => 'club']))
        ->assertSessionHasNoErrors();

    $hallNotice = Notice::factory()->create(['category' => NoticeCategory::HallOffice]);
    $otherClubNotice = Notice::factory()->create(['category' => NoticeCategory::Club]);
    $this->actingAs($this->clubAdmin)->get(route('admin.notices.edit', $hallNotice))->assertForbidden();
    $this->actingAs($this->clubAdmin)->delete(route('admin.notices.destroy', $otherClubNotice))->assertForbidden();
    $this->actingAs($this->clubAdmin)->get(route('admin.notices.index'))->assertDontSee($otherClubNotice->title);
});

test('students and house tutors cannot manage notices (403)', function () {
    $notice = Notice::factory()->create();

    foreach ([User::factory()->student()->create(), User::factory()->withRole(RoleName::HouseTutor)->create()] as $user) {
        $this->actingAs($user)->get(route('admin.notices.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.notices.store'), noticeData())->assertForbidden();
        $this->actingAs($user)->delete(route('admin.notices.destroy', $notice))->assertForbidden();
    }
    expect(Notice::count())->toBe(1);
});

test('deleting a notice removes its attachment file', function () {
    $this->actingAs($this->office)->post(route('admin.notices.store'), noticeData([
        'attachment' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
    ]));
    $notice = Notice::first();
    $path = $notice->attachment_path;

    $this->actingAs($this->office)->delete(route('admin.notices.destroy', $notice));

    expect(Notice::count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

test('notice management pages render', function () {
    $notice = Notice::factory()->draft()->create(['created_by' => $this->office->id]);

    $this->actingAs($this->office)->get(route('admin.notices.index'))->assertOk()->assertSee($notice->title);
    $this->actingAs($this->office)->get(route('admin.notices.create'))->assertOk();
    $this->actingAs($this->office)->get(route('admin.notices.edit', $notice))->assertOk();
});

test('BR-11: a draft is not visible at its public URL even to its editors', function () {
    $notice = Notice::factory()->draft()->create(['created_by' => $this->office->id]);
    $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

    $this->actingAs($this->office)->get(route('notices.show', $notice))->assertNotFound();
    $this->actingAs($admin)->get(route('notices.show', $notice))->assertNotFound();
    $this->actingAs($this->office)->get(route('notices.attachment', $notice))->assertNotFound();
});
