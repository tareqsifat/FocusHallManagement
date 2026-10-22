<x-app-layout :title="'Room '.$room->room_no">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $room->block->name }} · Room {{ $room->room_no }}</h2>
                <p class="text-sm text-gray-500">Floor {{ $room->floor }} · Capacity {{ $room->capacity }}</p>
            </div>
            @can('manage-hall')
                <x-link-button :href="route('rooms.edit', $room)">Edit</x-link-button>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($room->seats->where('is_active', true) as $seat)
                @php $occupant = $seat->activeAllocation?->student; @endphp
                <x-card :class="'border-l-4 '.($occupant ? 'border-gray-400' : 'border-green-500')">
                    <p class="text-sm text-gray-500">Seat {{ $seat->label }}</p>
                    @if ($occupant)
                        <a href="{{ route('students.show', $occupant) }}" class="font-medium text-indigo-700 hover:underline">{{ $occupant->user->name }}</a>
                        <p class="text-xs text-gray-500">Since {{ $seat->activeAllocation->allocated_at->format('d M Y') }}</p>
                    @else
                        <p class="font-medium text-green-700">Free</p>
                    @endif
                </x-card>
            @endforeach
        </div>
    </div>
</x-app-layout>
