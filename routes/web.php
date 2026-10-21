<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatAllocationController;
use App\Http\Controllers\StudentController;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

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
});

require __DIR__.'/auth.php';
