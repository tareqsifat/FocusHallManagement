<?php

use App\Http\Controllers\Admin\NoticeController as AdminNoticeController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubMembershipController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseTutorController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatAllocationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserRoleController;
use App\Models\Club;
use App\Models\Complaint;
use App\Models\HouseTutor;
use App\Models\Notice;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/notices');

// Public notice board (BR-11: published only).
Route::get('/notices', [NoticeController::class, 'index'])->name('notices.index');
Route::get('/notices/{notice}', [NoticeController::class, 'show'])->name('notices.show');
Route::get('/notices/{notice}/attachment', [NoticeController::class, 'attachment'])->name('notices.attachment');

// Public club directory.
Route::get('/clubs', [ClubController::class, 'index'])->name('clubs.index');
Route::get('/clubs/{club}', [ClubController::class, 'show'])->whereNumber('club')->name('clubs.show');
Route::get('/clubs/{club}/logo', [ClubController::class, 'logo'])->whereNumber('club')->name('clubs.logo');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/users', [UserRoleController::class, 'index'])->can('manageRoles', User::class)->name('users.index');
    Route::put('/users/{user}/roles', [UserRoleController::class, 'update'])->can('updateRoles', 'user')->name('users.roles');

    Route::get('/registrations', [RegistrationController::class, 'index'])
        ->can('reviewRegistrations', User::class)->name('registrations.index');
    Route::post('/registrations/{user}/approve', [RegistrationController::class, 'approve'])
        ->can('decideRegistration', 'user')->name('registrations.approve');
    Route::post('/registrations/{user}/reject', [RegistrationController::class, 'reject'])
        ->can('decideRegistration', 'user')->name('registrations.reject');

    // Hall structure: Provost may view, Office Staff and Super Admin manage.
    // Manage routes first so /rooms/create is not captured by /rooms/{room}.
    Route::middleware('can:manage-hall')->group(function () {
        Route::resource('blocks', BlockController::class)->only(['create', 'store', 'edit', 'update']);
        Route::resource('rooms', RoomController::class)->only(['create', 'store', 'edit', 'update']);
    });
    Route::middleware('can:view-hall')->group(function () {
        Route::resource('blocks', BlockController::class)->only('index');
        Route::resource('rooms', RoomController::class)->only(['index', 'show']);
    });

    Route::get('/students', [StudentController::class, 'index'])
        ->can('viewAny', StudentProfile::class)->name('students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])
        ->can('view', 'student')->name('students.show');
    Route::post('/students/{student}/allocations', [SeatAllocationController::class, 'store'])
        ->can('allocateSeat', 'student')->name('allocations.store');
    Route::post('/allocations/{allocation}/vacate', [SeatAllocationController::class, 'vacate'])
        ->can('vacate', 'allocation')->name('allocations.vacate');

    Route::get('/house-tutors', [HouseTutorController::class, 'index'])->can('viewAny', HouseTutor::class)->name('house-tutors.index');
    Route::get('/house-tutors/create', [HouseTutorController::class, 'create'])->can('create', HouseTutor::class)->name('house-tutors.create');
    Route::post('/house-tutors', [HouseTutorController::class, 'store'])->can('create', HouseTutor::class)->name('house-tutors.store');
    Route::get('/house-tutors/{house_tutor}/edit', [HouseTutorController::class, 'edit'])->can('update', 'house_tutor')->name('house-tutors.edit');
    Route::put('/house-tutors/{house_tutor}', [HouseTutorController::class, 'update'])->can('update', 'house_tutor')->name('house-tutors.update');
    Route::post('/house-tutors/{house_tutor}/toggle', [HouseTutorController::class, 'toggle'])->can('update', 'house_tutor')->name('house-tutors.toggle');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/notices', [AdminNoticeController::class, 'index'])->can('viewAny', Notice::class)->name('notices.index');
        Route::get('/notices/create', [AdminNoticeController::class, 'create'])->can('create', Notice::class)->name('notices.create');
        Route::post('/notices', [AdminNoticeController::class, 'store'])->can('create', Notice::class)->name('notices.store');
        Route::get('/notices/{notice}/edit', [AdminNoticeController::class, 'edit'])->can('update', 'notice')->name('notices.edit');
        Route::put('/notices/{notice}', [AdminNoticeController::class, 'update'])->can('update', 'notice')->name('notices.update');
        Route::delete('/notices/{notice}', [AdminNoticeController::class, 'destroy'])->can('delete', 'notice')->name('notices.destroy');
    });

    Route::get('/clubs/create', [ClubController::class, 'create'])->can('create', Club::class)->name('clubs.create');
    Route::post('/clubs', [ClubController::class, 'store'])->can('create', Club::class)->name('clubs.store');
    Route::get('/clubs/{club}/edit', [ClubController::class, 'edit'])->can('update', 'club')->name('clubs.edit');
    Route::put('/clubs/{club}', [ClubController::class, 'update'])->can('update', 'club')->name('clubs.update');
    Route::put('/clubs/{club}/admins', [ClubController::class, 'updateAdmins'])->can('assignAdmins', 'club')->name('clubs.admins');
    Route::get('/clubs/{club}/manage', [ClubController::class, 'manage'])->can('manageMembers', 'club')->name('clubs.manage');
    Route::post('/clubs/{club}/join', [ClubMembershipController::class, 'store'])->can('join', 'club')->name('clubs.join');
    Route::post('/club-memberships/{membership}/approve', [ClubMembershipController::class, 'approve'])->can('decide', 'membership')->name('memberships.approve');
    Route::post('/club-memberships/{membership}/reject', [ClubMembershipController::class, 'reject'])->can('decide', 'membership')->name('memberships.reject');
    Route::post('/club-memberships/{membership}/remove', [ClubMembershipController::class, 'remove'])->can('decide', 'membership')->name('memberships.remove');

    Route::controller(ComplaintController::class)->prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->can('create', Complaint::class)->name('create');
        Route::post('/', 'store')->can('create', Complaint::class)->name('store');
        Route::get('/{complaint}', 'show')->can('view', 'complaint')->name('show');
        Route::get('/{complaint}/photo', 'photo')->can('view', 'complaint')->name('photo');
        Route::post('/{complaint}/status', 'updateStatus')->name('status'); // authorised in ComplaintStatusRequest + policy
        Route::post('/{complaint}/assign', 'assign')->can('assign', 'complaint')->name('assign');
        Route::post('/{complaint}/comments', 'comment')->can('comment', 'complaint')->name('comments');
    });
});

require __DIR__.'/auth.php';
