<?php

namespace App\Http\Controllers;

use App\Http\Requests\AllocateSeatRequest;
use App\Http\Requests\VacateSeatRequest;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Services\SeatAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class SeatAllocationController extends Controller
{
    public function store(AllocateSeatRequest $request, StudentProfile $student, SeatAllocationService $service): RedirectResponse
    {
        $service->allocate($student, Seat::findOrFail($request->validated('seat_id')), $request->user());

        return back()->with('status', 'Seat allocated.');
    }

    public function vacate(VacateSeatRequest $request, SeatAllocation $allocation, SeatAllocationService $service): RedirectResponse
    {
        $service->vacate($allocation, Carbon::parse($request->validated('vacated_at')), $request->validated('reason'), $request->user());

        return back()->with('status', 'Seat vacated.');
    }
}
