<x-public-layout :title="$club->name">
    <a href="{{ route('clubs.index') }}" class="text-sm text-indigo-700 hover:underline">← All clubs</a>

    <article class="mt-4 rounded-lg bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4">
            <x-club-logo :club="$club" class="h-24 w-24 shrink-0 text-2xl" />
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold">{{ $club->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $club->active_memberships_count }} {{ Str::plural('member', $club->active_memberships_count) }}
                    · Admin: {{ $club->admins->pluck('name')->join(', ') ?: 'not assigned' }}
                </p>
                <p class="mt-3 whitespace-pre-line text-gray-800">{{ $club->description }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            @auth
                @if ($membership && in_array($membership->status, [App\Enums\MembershipStatus::Pending, App\Enums\MembershipStatus::Active], true))
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                        {{ $membership->status === App\Enums\MembershipStatus::Active ? 'You are a member' : 'Your request is pending' }}
                    </span>
                @elsecan('join', $club)
                    @if ($membership)
                        <span class="text-sm text-gray-500">Your previous request was {{ $membership->status->value }}.</span>
                    @endif
                    <form method="POST" action="{{ route('clubs.join', $club) }}">
                        @csrf
                        <x-primary-button>Request to join</x-primary-button>
                    </form>
                @endif
                @can('manageMembers', $club)
                    <x-link-button :href="route('clubs.manage', $club)">Manage members</x-link-button>
                @endcan
                @can('update', $club)
                    <a href="{{ route('clubs.edit', $club) }}" class="text-sm text-indigo-700 hover:underline">Edit club page</a>
                @endcan
            @else
                <a href="{{ route('login') }}" class="text-sm text-indigo-700 hover:underline">Log in to request membership</a>
            @endauth
        </div>
    </article>
</x-public-layout>
