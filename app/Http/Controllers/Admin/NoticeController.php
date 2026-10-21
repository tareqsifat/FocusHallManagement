<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoticeRequest;
use App\Models\Notice;
use App\Policies\NoticePolicy;
use App\Services\NoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notice management, drafts included. Club admins see and edit only their own Club notices. */
class NoticeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notices = Notice::query()
            ->with('author')
            ->when(! $user->isHallAdmin(), fn ($q) => $q->where('category', NoticeCategory::Club)->where('created_by', $user->id))
            ->when(NoticeStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.notices.index', compact('notices'));
    }

    public function create(Request $request): View
    {
        return view('admin.notices.form', [
            'notice' => new Notice(['category' => NoticeCategory::Club]),
            'categories' => NoticePolicy::categoriesFor($request->user()),
        ]);
    }

    public function store(NoticeRequest $request, NoticeService $service): RedirectResponse
    {
        $notice = $service->save(new Notice, $request->validated(), $request->file('attachment'), $request->user());

        return redirect()->route('admin.notices.index')->with('status', "Notice saved as {$notice->status->value}.");
    }

    public function edit(Request $request, Notice $notice): View
    {
        return view('admin.notices.form', [
            'notice' => $notice,
            'categories' => NoticePolicy::categoriesFor($request->user()),
        ]);
    }

    public function update(NoticeRequest $request, Notice $notice, NoticeService $service): RedirectResponse
    {
        $service->save($notice, $request->validated(), $request->file('attachment'), $request->user());

        return redirect()->route('admin.notices.index')->with('status', 'Notice updated.');
    }

    public function destroy(Notice $notice, NoticeService $service): RedirectResponse
    {
        $service->delete($notice);

        return redirect()->route('admin.notices.index')->with('status', 'Notice deleted.');
    }
}
