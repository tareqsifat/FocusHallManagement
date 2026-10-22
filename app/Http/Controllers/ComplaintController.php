<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Http\Requests\AssignComplaintRequest;
use App\Http\Requests\ComplaintStatusRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\ComplaintStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $complaints = Complaint::query()
            ->visibleTo($request->user())
            ->with(['student.user', 'room.block', 'assignee'])
            ->when(ComplaintStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s))
            ->when(ComplaintCategory::tryFrom((string) $request->query('category')), fn ($q, $c) => $q->where('category', $c))
            ->when($request->boolean('overdue'), fn ($q) => $q->overdue())
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('tracking_id', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('complaints.index', compact('complaints'));
    }

    public function create(Request $request): View
    {
        $allocation = $request->user()->studentProfile->currentAllocation()->with('seat.room.block')->first();

        return view('complaints.create', ['room' => $allocation?->seat->room]);
    }

    public function store(StoreComplaintRequest $request, ComplaintService $service): RedirectResponse
    {
        $complaint = $service->submit($request->user(), $request->validated(), $request->file('photo'));

        return redirect()->route('complaints.show', $complaint)
            ->with('status', "Complaint submitted. Your tracking ID is {$complaint->tracking_id}.");
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $complaint->load(['student.user', 'room.block', 'assignee', 'statusLogs.changedBy', 'comments.user']);

        $user = $request->user();
        $transitions = collect($complaint->status->allowedTransitions())
            ->filter(fn (ComplaintStatus $to) => $user->can('transition', [$complaint, $to]))
            ->values();
        $assignees = $user->can('assign', $complaint)
            ? User::assignableTo($complaint)->with('roles')->orderBy('name')->get()
            : collect();

        $reporterVisible = $user->can('viewReporter', $complaint);

        return view('complaints.show', compact('complaint', 'transitions', 'assignees', 'reporterVisible'));
    }

    public function updateStatus(ComplaintStatusRequest $request, Complaint $complaint, ComplaintStatusService $service): RedirectResponse
    {
        $to = $request->toStatus();
        Gate::authorize('transition', [$complaint, $to]);

        $service->transition($complaint, $to, $request->validated('note'), $request->user());

        return back()->with('status', "Status changed to {$to->label()}.");
    }

    public function assign(AssignComplaintRequest $request, Complaint $complaint, ComplaintService $service): RedirectResponse
    {
        $assignee = User::findOrFail($request->validated('assignee_id'));
        $service->assign($complaint, $assignee);

        return back()->with('status', "Assigned to {$assignee->name}.");
    }

    public function comment(StoreCommentRequest $request, Complaint $complaint, ComplaintService $service): RedirectResponse
    {
        $service->comment($complaint, $request->user(), $request->validated('body'));

        return back()->with('status', 'Comment added.');
    }

    /** Complaint photos live on the private disk and are only streamed to people who may view the complaint. */
    public function photo(Complaint $complaint): StreamedResponse
    {
        abort_unless($complaint->photo_path && Storage::disk('local')->exists($complaint->photo_path), 404);

        return Storage::disk('local')->response($complaint->photo_path, $complaint->tracking_id.'.'.pathinfo($complaint->photo_path, PATHINFO_EXTENSION), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
