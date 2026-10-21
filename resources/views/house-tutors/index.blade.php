<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">House tutors</h2>
            <x-link-button :href="route('house-tutors.create')">New tutor</x-link-button>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($tutors as $tutor)
                @php $active = $tutor->user->isActive(); @endphp
                <div class="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">
                            {{ $tutor->user->name }}
                            @unless ($active)
                                <span class="ms-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">Inactive</span>
                            @endunless
                        </p>
                        <p class="text-sm text-gray-600">{{ $tutor->designation }}, {{ $tutor->department }}</p>
                        <p class="text-sm text-gray-500 break-all">{{ $tutor->user->email }} · {{ $tutor->phone }}</p>
                        <p class="mt-1 text-sm">Blocks: {{ $tutor->blocks->pluck('name')->join(', ') ?: 'none' }}</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('house-tutors.edit', $tutor) }}" class="text-sm text-indigo-700 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('house-tutors.toggle', $tutor) }}">
                            @csrf
                            @if ($active)
                                <x-danger-button>Deactivate</x-danger-button>
                            @else
                                <x-secondary-button type="submit">Reactivate</x-secondary-button>
                            @endif
                        </form>
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">No house tutors yet.</p>
            @endforelse
        </div>
        {{ $tutors->links() }}
    </div>
</x-app-layout>
