<x-app-layout>
    <x-slot name="header">
        <p class="font-mono text-sm text-gray-500">{{ $complaint->tracking_id }}</p>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $complaint->title }}</h2>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600">
            <x-complaint-status :status="$complaint->status" />
            @if ($complaint->isOverdue())
                <x-overdue-badge />
            @endif
            <span>
                @if ($complaint->resolved_at)
                    Resolved in {{ $complaint->daysOpen() }} {{ Str::plural('day', $complaint->daysOpen()) }}
                @elseif (! $complaint->status->isFinished())
                    Open for {{ $complaint->daysOpen() }} {{ Str::plural('day', $complaint->daysOpen()) }}
                @endif
            </span>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <x-card title="Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Category</dt><dd>{{ $complaint->category->label() }}</dd></div>
                    <div><dt class="text-gray-500">Location</dt><dd>{{ $complaint->locationLabel() }}</dd></div>
                    <div><dt class="text-gray-500">Submitted by</dt><dd>{{ $complaint->student->user->name }} ({{ $complaint->student->registration_no }})</dd></div>
                    <div><dt class="text-gray-500">Submitted</dt><dd>{{ $complaint->created_at->format('d M Y, h:i A') }}</dd></div>
                    <div><dt class="text-gray-500">Assigned to</dt><dd>{{ $complaint->assignee?->name ?? 'Not assigned' }}</dd></div>
                </dl>
                <p class="mt-4 whitespace-pre-line text-gray-800">{{ $complaint->description }}</p>
                @if ($complaint->photo_path)
                    <a href="{{ route('complaints.photo', $complaint) }}" target="_blank" class="mt-4 block">
                        <img src="{{ route('complaints.photo', $complaint) }}" alt="Photo attached to the complaint" class="max-h-72 rounded-md border">
                    </a>
                @endif
            </x-card>

            <x-card title="Comments">
                <ol class="space-y-4">
                    @forelse ($complaint->comments as $comment)
                        <li class="text-sm">
                            <p><span class="font-medium">{{ $comment->user->name }}</span> <span class="text-gray-400">· {{ $comment->created_at->format('d M Y, h:i A') }}</span></p>
                            <p class="whitespace-pre-line text-gray-800">{{ $comment->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">No comments yet.</li>
                    @endforelse
                </ol>
                @can('comment', $complaint)
                    <form method="POST" action="{{ route('complaints.comments', $complaint) }}" class="mt-4 space-y-2">
                        @csrf
                        <x-input-label for="body" value="Add a comment" />
                        <textarea id="body" name="body" rows="3" maxlength="2000" required class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" />
                        <x-primary-button>Post comment</x-primary-button>
                    </form>
                @endcan
            </x-card>
        </div>

        <div class="space-y-4">
            @if ($transitions->isNotEmpty())
                <x-card title="Update status">
                    <form method="POST" action="{{ route('complaints.status', $complaint) }}" class="space-y-3">
                        @csrf
                        <fieldset class="space-y-2">
                            <legend class="sr-only">New status</legend>
                            @foreach ($transitions as $to)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="to_status" value="{{ $to->value }}" required @checked(old('to_status') === $to->value) class="text-indigo-600 focus:ring-indigo-500">
                                    {{ $complaint->status === App\Enums\ComplaintStatus::Resolved && $to === App\Enums\ComplaintStatus::InProgress ? 'Reopen (problem not fixed)' : $to->label() }}
                                </label>
                            @endforeach
                        </fieldset>
                        <x-input-error :messages="$errors->get('to_status')" />
                        <div>
                            <x-input-label for="note" value="Note" />
                            <textarea id="note" name="note" rows="3" maxlength="2000" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('note') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Required when rejecting (reason) or resolving (resolution note).</p>
                            <x-input-error :messages="$errors->get('note')" />
                        </div>
                        <x-primary-button>Update</x-primary-button>
                    </form>
                </x-card>
            @endif

            @can('assign', $complaint)
                <x-card title="Assign">
                    <form method="POST" action="{{ route('complaints.assign', $complaint) }}" class="space-y-3">
                        @csrf
                        <x-select-input name="assignee_id" class="block w-full" required aria-label="Assignee">
                            <option value="">Select person</option>
                            @foreach ($assignees as $person)
                                <option value="{{ $person->id }}" @selected($complaint->assigned_to === $person->id)>
                                    {{ $person->name }} ({{ $person->hasRole(App\Enums\RoleName::HouseTutor) ? 'House Tutor' : 'Office Staff' }})
                                </option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('assignee_id')" />
                        <x-primary-button>{{ $complaint->assignee ? 'Reassign' : 'Assign' }}</x-primary-button>
                    </form>
                </x-card>
            @endcan

            <x-card title="Status history">
                <ol class="relative border-s border-gray-200 ms-2 space-y-4">
                    @foreach ($complaint->statusLogs as $log)
                        <li class="ms-4 text-sm">
                            <span class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-indigo-500"></span>
                            <p class="font-medium">
                                @if ($log->from_status) {{ $log->from_status->label() }} → @endif{{ $log->to_status->label() }}
                            </p>
                            <p class="text-gray-500">{{ $log->changedBy->name }} · {{ $log->created_at->format('d M Y, h:i A') }}</p>
                            @if ($log->note)
                                <p class="mt-1 whitespace-pre-line text-gray-700">{{ $log->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>
    </div>
</x-app-layout>
