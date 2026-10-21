<?php

namespace App\Http\Controllers;

use App\Enums\NoticeCategory;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Public notice board: no login needed, published notices only (BR-11). */
class NoticeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $notices = Notice::published()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when(NoticeCategory::tryFrom((string) $request->query('category')), fn ($q, $c) => $q->where('category', $c))
            ->boardOrder()
            ->paginate(10)
            ->withQueryString();

        return view('notices.index', compact('notices'));
    }

    public function show(Notice $notice): View
    {
        // 404 rather than 403 so drafts are not revealed to the public.
        abort_unless(Gate::allows('view', $notice), 404);

        return view('notices.show', compact('notice'));
    }

    public function attachment(Notice $notice): StreamedResponse
    {
        abort_unless(Gate::allows('view', $notice) && $notice->attachment_path, 404);

        return Storage::disk('local')->download($notice->attachment_path, 'notice-'.$notice->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
