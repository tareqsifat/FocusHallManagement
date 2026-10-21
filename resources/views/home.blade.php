<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Welcome, {{ Auth::user()->name }}</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-4 md:grid-cols-3">
        <x-card title="Your seat">
            @if ($allocation)
                @php $room = $allocation->seat->room; @endphp
                <p class="text-lg font-semibold">{{ $room->block->name }} · Room {{ $room->room_no }} · Seat {{ $allocation->seat->label }}</p>
                <p class="text-sm text-gray-500">Since {{ $allocation->allocated_at->format('d M Y') }}</p>
            @else
                <p class="text-sm text-gray-600">No seat allocated. You are an attached (non-resident) student.</p>
            @endif
            @if (Auth::user()->studentProfile)
                <a href="{{ route('students.show', Auth::user()->studentProfile) }}" class="mt-3 inline-block text-sm text-indigo-700 hover:underline">Your hall profile →</a>
            @endif
        </x-card>

        <x-card title="Your complaints">
            <p class="text-3xl font-semibold tabular-nums">{{ $openComplaints }}</p>
            <p class="text-sm text-gray-500">open</p>
            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('complaints.index') }}" class="text-indigo-700 hover:underline">View all →</a>
                @can('create', App\Models\Complaint::class)
                    <a href="{{ route('complaints.create') }}" class="text-indigo-700 hover:underline">New complaint →</a>
                @endcan
            </div>
        </x-card>

        <x-card title="Your clubs">
            @if ($ledClub)
                <p class="text-sm">You lead <a href="{{ route('clubs.show', $ledClub) }}" class="font-medium text-indigo-700 hover:underline">{{ $ledClub->name }}</a>.</p>
                <a href="{{ route('clubs.manage', $ledClub) }}" class="mt-1 inline-block text-sm text-indigo-700 hover:underline">Manage members →</a>
            @endif
            <ul class="mt-2 space-y-1 text-sm">
                @forelse ($memberships as $membership)
                    <li>
                        <a href="{{ route('clubs.show', $membership->club) }}" class="text-gray-900 hover:underline">{{ $membership->club->name }}</a>
                        <span class="text-gray-500">· {{ $membership->status === App\Enums\MembershipStatus::Active ? 'member' : 'request pending' }}</span>
                    </li>
                @empty
                    <li class="text-gray-600">You have not joined any club yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('clubs.index') }}" class="mt-3 inline-block text-sm text-indigo-700 hover:underline">Browse clubs →</a>
        </x-card>
    </div>
</x-app-layout>
