<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Requests\RejectRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $status = UserStatus::tryFrom($request->query('status', '')) ?? UserStatus::Pending;

        $applicants = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Student))
            ->where('status', $status)
            ->with(['studentProfile', 'roles'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('registrations.index', compact('applicants', 'status'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->status = UserStatus::Active;
        $user->rejection_reason = null;
        $user->save();

        return back()->with('status', "{$user->name} approved.");
    }

    public function reject(RejectRegistrationRequest $request, User $user): RedirectResponse
    {
        $user->status = UserStatus::Rejected;
        $user->rejection_reason = $request->validated('reason');
        $user->save();

        return back()->with('status', "{$user->name} rejected.");
    }
}
