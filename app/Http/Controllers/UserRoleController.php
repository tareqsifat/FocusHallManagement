<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\User;
use App\Services\UserRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Super Admin: staff accounts and their roles. Students get their roles through registration and clubs. */
class UserRoleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', RoleName::Student))
            ->with('roles')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function update(UpdateUserRolesRequest $request, User $user, UserRoleService $service): RedirectResponse
    {
        $service->update($user, $request->roles());

        return back()->with('status', "Roles updated for {$user->name}.");
    }
}
