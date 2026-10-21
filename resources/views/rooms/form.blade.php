<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $room->exists ? "Edit room {$room->room_no}" : 'New room' }}</h2>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-card>
            <form method="POST" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}" class="space-y-4">
                @csrf
                @if ($room->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="block_id" value="Block" />
                    <x-select-input id="block_id" name="block_id" class="mt-1 block w-full" required>
                        <option value="">Select block</option>
                        @foreach ($blocks as $block)
                            <option value="{{ $block->id }}" @selected(old('block_id', $room->block_id) == $block->id)>{{ $block->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('block_id')" class="mt-2" />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="room_no" value="Room no." />
                        <x-text-input id="room_no" name="room_no" class="mt-1 block w-full" :value="old('room_no', $room->room_no)" required maxlength="10" />
                        <x-input-error :messages="$errors->get('room_no')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="floor" value="Floor" />
                        <x-text-input id="floor" name="floor" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('floor', $room->floor)" required />
                        <x-input-error :messages="$errors->get('floor')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="capacity" value="Capacity" />
                        <x-text-input id="capacity" name="capacity" type="number" min="1" max="6" class="mt-1 block w-full" :value="old('capacity', $room->capacity)" required />
                        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                    </div>
                </div>
                <p class="text-xs text-gray-500">Seats are labelled A, B, C… Lowering capacity retires free seats; it can never go below the number of occupied seats.</p>

                <div class="flex items-center gap-4">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ $room->exists ? route('rooms.show', $room) : route('rooms.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
