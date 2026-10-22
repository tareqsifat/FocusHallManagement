<x-app-layout title="Students">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Students</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
            <div class="col-span-2">
                <x-input-label for="search" value="Name or registration no." />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" />
            </div>
            <div>
                <x-input-label for="block_id" value="Block" />
                <x-select-input id="block_id" name="block_id" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach ($blocks as $block)
                        <option value="{{ $block->id }}" @selected(request('block_id') == $block->id)>{{ $block->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="department" value="Department" />
                <x-select-input id="department" name="department" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach (config('hall.departments') as $department)
                        <option @selected(request('department') === $department)>{{ $department }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="session" value="Session" />
                <x-select-input id="session" name="session" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach ($sessions as $session)
                        <option @selected(request('session') === $session)>{{ $session }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <x-select-input id="status" name="status" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach (App\Enums\ResidencyStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="col-span-2 md:col-span-6 flex gap-3">
                <x-primary-button>Search</x-primary-button>
                <a href="{{ route('students.index') }}" class="self-center text-sm text-gray-600 hover:underline">Reset</a>
            </div>
        </form>

        <p class="text-sm text-gray-500">{{ $students->total() }} {{ Str::plural('student', $students->total()) }}</p>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($students as $student)
                @php $room = $student->currentAllocation?->seat->room; @endphp
                <a href="{{ route('students.show', $student) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 p-4 hover:bg-gray-50">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">{{ $student->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $student->registration_no }} · {{ $student->department }} · {{ $student->session }}</p>
                    </div>
                    <div class="text-sm sm:text-right">
                        @if ($room)
                            <span class="text-gray-900">{{ $room->block->name }} {{ $room->room_no }}-{{ $student->currentAllocation->seat->label }}</span>
                        @endif
                        <x-residency-badge :status="$student->residency_status" />
                    </div>
                </a>
            @empty
                <p class="p-6 text-sm text-gray-500">No students match these filters.</p>
            @endforelse
        </div>

        {{ $students->links() }}
    </div>
</x-app-layout>
