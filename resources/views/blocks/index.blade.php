<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blocks</h2>
            @can('manage-hall')
                <x-link-button :href="route('blocks.create')">New block</x-link-button>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($blocks as $block)
                @php $seats = (int) $block->rooms_sum_capacity; @endphp
                <x-card>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ $block->name }}</p>
                            <p class="text-xs text-gray-500">Code {{ $block->code }}</p>
                        </div>
                        @can('manage-hall')
                            <a href="{{ route('blocks.edit', $block) }}" class="text-sm text-indigo-700 hover:underline">Edit</a>
                        @endcan
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                        <div><dt class="text-gray-500">Rooms</dt><dd class="font-semibold">{{ $block->rooms_count }}</dd></div>
                        <div><dt class="text-gray-500">Seats</dt><dd class="font-semibold">{{ $seats }}</dd></div>
                        <div><dt class="text-gray-500">Free</dt><dd class="font-semibold">{{ $seats - $block->occupied_count }}</dd></div>
                    </dl>
                    <a href="{{ route('rooms.index', ['block_id' => $block->id]) }}" class="mt-4 inline-block text-sm text-indigo-700 hover:underline">View rooms →</a>
                </x-card>
            @empty
                <p class="text-sm text-gray-500">No blocks yet.</p>
            @endforelse
        </div>
        {{ $blocks->links() }}
    </div>
</x-app-layout>
