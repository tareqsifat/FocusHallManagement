<?php

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Models\Complaint;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();

        if ($user->isHallAdmin()) {
            return view('dashboard', [
                'hall' => $dashboard->hallFigures(),
                'complaints' => $dashboard->complaintFigures(),
                'scope' => 'Whole hall',
            ]);
        }

        if ($user->hasRole(RoleName::HouseTutor)) {
            $blocks = $user->houseTutor?->blocks()->orderBy('name')->pluck('name') ?? collect();

            return view('dashboard', [
                'hall' => null,
                'complaints' => $dashboard->complaintFigures($user->tutorBlockIds()),
                'scope' => $blocks->isEmpty() ? 'No blocks assigned' : 'Your blocks: '.$blocks->join(', '),
            ]);
        }

        // Students and club admins get a personal home page instead of hall figures (matrix: no dashboard).
        $profile = $user->studentProfile;

        return view('home', [
            'allocation' => $profile?->currentAllocation()->with('seat.room.block')->first(),
            'openComplaints' => $profile ? Complaint::where('student_profile_id', $profile->id)->open()->count() : 0,
            'memberships' => $profile?->clubMemberships()->with('club')
                ->whereIn('status', [MembershipStatus::Pending, MembershipStatus::Active])->get() ?? collect(),
            'ledClub' => $user->administeredClubs()->first(),
        ]);
    }
}
