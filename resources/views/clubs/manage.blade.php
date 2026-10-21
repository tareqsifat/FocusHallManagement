<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $club->name }}: members</h2>
            <a href="{{ route('clubs.show', $club) }}" class="text-sm text-indigo-700 hover:underline">Club page</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-card title="Pending requests ({{ $pending->count() }})">
            <ul class="divide-y divide-gray-100">
                @forelse ($pending as $request)
                    <li class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $request->student->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $request->student->registration_no }} · {{ $request->student->department }} · requested {{ $request->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('memberships.approve', $request) }}">@csrf <x-primary-button>Approve</x-primary-button></form>
                            <form method="POST" action="{{ route('memberships.reject', $request) }}">@csrf <x-secondary-button type="submit">Reject</x-secondary-button></form>
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">No pending requests.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Members ({{ $members->total() }})">
            <ul class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <li class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $member->student->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $member->student->registration_no }} · since {{ $member->decided_at?->format('d M Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('memberships.remove', $member) }}" onsubmit="return confirm('Remove this member?')">
                            @csrf
                            <x-danger-button>Remove</x-danger-button>
                        </form>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">No members yet.</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $members->links() }}</div>
        </x-card>
    </div>
</x-app-layout>
