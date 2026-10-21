<?php

namespace App\Http\Controllers;

use App\Enums\ResidencyStatus;
use App\Enums\UserStatus;
use App\Models\Block;
use App\Models\Seat;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $students = StudentProfile::query()
            ->visibleTo($request->user())
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user', 'currentAllocation.seat.room.block'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('registration_no', 'like', "{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->when($request->integer('block_id'), fn ($q, $blockId) => $q
                ->whereHas('currentAllocation.seat.room', fn ($r) => $r->where('block_id', $blockId)))
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))
            ->when($request->query('session'), fn ($q, $s) => $q->where('session', $s))
            ->when(ResidencyStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('residency_status', $s))
            ->orderBy('registration_no')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'blocks' => Block::query()
                ->when(! $request->user()->isHallAdmin(), fn ($q) => $q->whereIn('id', $request->user()->tutorBlockIds()))
                ->orderBy('name')->get(),
            'sessions' => StudentProfile::distinct()->orderByDesc('session')->pluck('session'),
        ]);
    }

    public function show(Request $request, StudentProfile $student): View
    {
        $student->load([
            'user',
            'allocations' => fn ($q) => $q->latest('allocated_at')->latest('id'),
            'allocations.seat.room.block',
            'allocations.allocatedBy',
            'allocations.vacatedBy',
        ]);
        $current = $student->allocations->first(fn ($a) => $a->isActive());

        $freeSeats = $request->user()->can('allocateSeat', $student) && ! $current
            ? Seat::query()
                ->where('is_active', true)
                ->whereDoesntHave('activeAllocation')
                ->with('room.block')
                ->get()
                ->sortBy(fn ($seat) => [$seat->room->block->name, $seat->room->room_no, $seat->label])
                ->groupBy(fn ($seat) => $seat->room->block->name)
            : collect();

        return view('students.show', compact('student', 'current', 'freeSeats'));
    }
}
