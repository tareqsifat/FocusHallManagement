<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubMembership;
use App\Services\ClubMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClubMembershipController extends Controller
{
    public function store(Request $request, Club $club, ClubMembershipService $service): RedirectResponse
    {
        $service->request($club, $request->user()->studentProfile);

        return back()->with('status', "Request sent to {$club->name}.");
    }

    public function approve(Request $request, ClubMembership $membership, ClubMembershipService $service): RedirectResponse
    {
        $service->approve($membership, $request->user());

        return back()->with('status', 'Request approved.');
    }

    public function reject(Request $request, ClubMembership $membership, ClubMembershipService $service): RedirectResponse
    {
        $service->reject($membership, $request->user());

        return back()->with('status', 'Request rejected.');
    }

    public function remove(Request $request, ClubMembership $membership, ClubMembershipService $service): RedirectResponse
    {
        $service->remove($membership, $request->user());

        return back()->with('status', 'Member removed.');
    }
}
