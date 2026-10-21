<x-public-layout title="Clubs">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">Clubs</h1>
        @can('create', App\Models\Club::class)
            <x-link-button :href="route('clubs.create')">New club</x-link-button>
        @endcan
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @forelse ($clubs as $club)
            <a href="{{ route('clubs.show', $club) }}" class="flex gap-4 rounded-lg bg-white p-4 shadow-sm hover:shadow">
                <x-club-logo :club="$club" class="h-16 w-16 shrink-0 text-lg" />
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $club->name }}</p>
                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $club->description }}</p>
                    <p class="mt-2 text-xs text-gray-500">
                        {{ $club->active_memberships_count }} {{ Str::plural('member', $club->active_memberships_count) }}
                        · Admin: {{ $club->admins->pluck('name')->join(', ') ?: 'not assigned' }}
                    </p>
                </div>
            </a>
        @empty
            <p class="text-sm text-gray-500">No clubs yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $clubs->links() }}</div>
</x-public-layout>
