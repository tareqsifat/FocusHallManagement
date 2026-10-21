<?php

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Requests\ClubAdminsRequest;
use App\Http\Requests\ClubRequest;
use App\Models\Club;
use App\Models\User;
use App\Services\ClubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClubController extends Controller
{
    /** Public directory. */
    public function index(): View
    {
        $clubs = Club::query()
            ->with('admins')
            ->withCount('activeMemberships')
            ->orderBy('name')
            ->paginate(12);

        return view('clubs.index', compact('clubs'));
    }

    public function show(Request $request, Club $club): View
    {
        $club->load('admins')->loadCount('activeMemberships');

        $membership = $request->user()?->studentProfile
            ? $club->memberships()->where('student_profile_id', $request->user()->studentProfile->id)->latest('id')->first()
            : null;

        return view('clubs.show', compact('club', 'membership'));
    }

    public function logo(Club $club): StreamedResponse
    {
        abort_unless($club->logo_path && Storage::disk('local')->exists($club->logo_path), 404);

        return Storage::disk('local')->response($club->logo_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function create(): View
    {
        return view('clubs.form', ['club' => new Club]);
    }

    public function store(ClubRequest $request, ClubService $service): RedirectResponse
    {
        $club = $service->save(new Club, $request->validated(), $request->file('logo'));

        return redirect()->route('clubs.edit', $club)->with('status', 'Club created. Now assign its admins.');
    }

    public function edit(Request $request, Club $club): View
    {
        $club->load('admins');
        $students = $request->user()->can('assignAdmins', $club)
            ? User::where('status', UserStatus::Active)
                ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Student))
                ->with('studentProfile')
                ->orderBy('name')
                ->get()
            : collect();

        return view('clubs.form', compact('club', 'students'));
    }

    public function update(ClubRequest $request, Club $club, ClubService $service): RedirectResponse
    {
        $service->save($club, $request->validated(), $request->file('logo'));

        return redirect()->route('clubs.show', $club)->with('status', 'Club updated.');
    }

    public function updateAdmins(ClubAdminsRequest $request, Club $club, ClubService $service): RedirectResponse
    {
        $service->syncAdmins($club, array_map('intval', $request->validated('admin_ids', [])));

        return back()->with('status', 'Club admins updated.');
    }

    /** Club admin's page: pending requests and current members. */
    public function manage(Club $club): View
    {
        $pending = $club->memberships()->where('status', MembershipStatus::Pending)
            ->with('student.user')->oldest()->get();
        $members = $club->memberships()->where('status', MembershipStatus::Active)
            ->with('student.user')->latest('decided_at')->paginate(25);

        return view('clubs.manage', compact('club', 'pending', 'members'));
    }
}
