<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
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
});

require __DIR__.'/auth.php';
