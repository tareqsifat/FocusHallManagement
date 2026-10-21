<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rooms</h2>
            @can('manage-hall')
                <x-link-button :href="route('rooms.create', ['block_id' => request('block_id')])">New room</x-link-button>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="block_id" value="Block" />
                <x-select-input id="block_id" name="block_id" class="mt-1">
                    <option value="">All blocks</option>
                    @foreach ($blocks as $block)
                        <option value="{{ $block->id }}" @selected(request('block_id') == $block->id)>{{ $block->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="availability" value="Availability" />
                <x-select-input id="availability" name="availability" class="mt-1">
                    <option value="">All rooms</option>
                    <option value="free" @selected(request('availability') === 'free')>Has a free seat</option>
                </x-select-input>
            </div>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($rooms as $room)
                <a href="{{ route('rooms.show', $room) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50">
                    <div>
                        <p class="font-medium text-gray-900">{{ $room->block->name }} · Room {{ $room->room_no }}</p>
                        <p class="text-sm text-gray-500">Floor {{ $room->floor }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold">{{ $room->occupied_count }} / {{ $room->capacity }}</p>
                        <p @class(['text-xs', 'text-green-700' => $room->occupied_count < $room->capacity, 'text-gray-500' => $room->occupied_count >= $room->capacity])>
                            {{ $room->occupied_count < $room->capacity ? ($room->capacity - $room->occupied_count).' free' : 'Full' }}
                        </p>
                    </div>
                </a>
            @empty
                <p class="p-6 text-sm text-gray-500">No rooms match.</p>
            @endforelse
        </div>
        {{ $rooms->links() }}
    </div>
</x-app-layout>
