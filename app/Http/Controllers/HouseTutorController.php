<?php

namespace App\Http\Controllers;

use App\Http\Requests\HouseTutorRequest;
use App\Models\Block;
use App\Models\HouseTutor;
use App\Services\HouseTutorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HouseTutorController extends Controller
{
    public function index(): View
    {
        $tutors = HouseTutor::query()
            ->with(['user', 'blocks' => fn ($q) => $q->orderBy('name')])
            ->join('users', 'users.id', '=', 'house_tutors.user_id')
            ->orderBy('users.name')
            ->select('house_tutors.*')
            ->paginate(20);

        return view('house-tutors.index', compact('tutors'));
    }

    public function create(): View
    {
        return view('house-tutors.form', ['tutor' => new HouseTutor, 'blocks' => Block::orderBy('name')->get()]);
    }

    public function store(HouseTutorRequest $request, HouseTutorService $service): RedirectResponse
    {
        $tutor = $service->create($request->validated());

        return redirect()->route('house-tutors.index')->with('status', "{$tutor->user->name} added as a house tutor.");
    }

    public function edit(HouseTutor $houseTutor): View
    {
        $houseTutor->load(['user', 'blocks']);

        return view('house-tutors.form', ['tutor' => $houseTutor, 'blocks' => Block::orderBy('name')->get()]);
    }

    public function update(HouseTutorRequest $request, HouseTutor $houseTutor, HouseTutorService $service): RedirectResponse
    {
        $service->update($houseTutor, $request->validated());

        return redirect()->route('house-tutors.index')->with('status', 'House tutor updated.');
    }

    public function toggle(HouseTutor $houseTutor, HouseTutorService $service): RedirectResponse
    {
        $activate = ! $houseTutor->is_active;
        $service->setActive($houseTutor, $activate);

        return back()->with('status', "{$houseTutor->user->name} ".($activate ? 'reactivated.' : 'deactivated.'));
    }
}
