<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StudentRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', ['departments' => config('hall.departments')]);
    }

    public function store(StudentRegistrationRequest $request, RegisterStudent $registerStudent): RedirectResponse
    {
        $user = $registerStudent->handle($request->validated());

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', 'Registration received. You can log in once the hall office approves your account.');
    }
}
