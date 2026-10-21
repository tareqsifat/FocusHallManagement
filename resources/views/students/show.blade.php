<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $student->user->name }}</h2>
            <x-residency-badge :status="$student->residency_status" />
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-4 lg:grid-cols-3">
        <x-card title="Details" class="lg:col-span-1">
            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500">Registration no.</dt><dd>{{ $student->registration_no }}</dd></div>
                <div><dt class="text-gray-500">Department</dt><dd>{{ $student->department }}</dd></div>
                <div><dt class="text-gray-500">Session</dt><dd>{{ $student->session }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd>{{ $student->phone }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="break-all">{{ $student->user->email }}</dd></div>
            </dl>
        </x-card>

        <div class="lg:col-span-2 space-y-4">
            <x-card title="Current seat">
                @if ($current)
                    @php $room = $current->seat->room; @endphp
                    <p class="text-lg font-semibold">{{ $room->block->name }} · Room {{ $room->room_no }} · Seat {{ $current->seat->label }}</p>
                    <p class="text-sm text-gray-500">Since {{ $current->allocated_at->format('d M Y') }}</p>

                    @can('vacate', $current)
                        <form method="POST" action="{{ route('allocations.vacate', $current) }}" class="mt-4 grid gap-3 sm:grid-cols-3 items-end">
                            @csrf
                            <div>
                                <x-input-label for="vacated_at" value="Vacate date" />
                                <x-text-input id="vacated_at" name="vacated_at" type="date" class="mt-1 block w-full" :value="old('vacated_at', today()->toDateString())" :min="$current->allocated_at->toDateString()" :max="today()->toDateString()" required />
                                <x-input-error :messages="$errors->get('vacated_at')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="reason" value="Reason" />
                                <x-text-input id="reason" name="reason" class="mt-1 block w-full" :value="old('reason')" required maxlength="255" />
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                            <x-danger-button class="justify-center">Vacate seat</x-danger-button>
                        </form>
                    @endcan
                @else
                    <p class="text-sm text-gray-600">No seat allocated. This student is attached (non-resident).</p>

                    @can('allocateSeat', $student)
                        @if ($freeSeats->isEmpty())
                            <p class="mt-3 text-sm text-amber-700">There are no free seats in the hall.</p>
                        @else
                            <form method="POST" action="{{ route('allocations.store', $student) }}" class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-end">
                                @csrf
                                <div class="flex-1">
                                    <x-input-label for="seat_id" value="Free seat" />
                                    <x-select-input id="seat_id" name="seat_id" class="mt-1 block w-full" required>
                                        <option value="">Select a seat</option>
                                        @foreach ($freeSeats as $blockName => $seats)
                                            <optgroup label="{{ $blockName }}">
                                                @foreach ($seats as $seat)
                                                    <option value="{{ $seat->id }}" @selected(old('seat_id') == $seat->id)>Room {{ $seat->room->room_no }} · Seat {{ $seat->label }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </x-select-input>
                                    <x-input-error :messages="$errors->get('seat_id')" class="mt-2" />
                                </div>
                                <x-primary-button class="justify-center">Allocate</x-primary-button>
                            </form>
                        @endif
                    @endcan
                @endif
            </x-card>

            <x-card title="Allocation history">
                @if ($student->allocations->isEmpty())
                    <p class="text-sm text-gray-500">No allocations yet.</p>
                @else
                    <ol class="divide-y divide-gray-100 text-sm">
                        @foreach ($student->allocations as $allocation)
                            @php $room = $allocation->seat->room; @endphp
                            <li class="py-3">
                                <p class="font-medium">{{ $room->block->name }} {{ $room->room_no }} · Seat {{ $allocation->seat->label }}</p>
                                <p class="text-gray-600">
                                    {{ $allocation->allocated_at->format('d M Y') }} – {{ $allocation->vacated_at?->format('d M Y') ?? 'present' }}
                                    <span class="text-gray-400">· allocated by {{ $allocation->allocatedBy->name }}</span>
                                </p>
                                @if ($allocation->vacated_at)
                                    <p class="text-gray-500">Vacated by {{ $allocation->vacatedBy?->name }}: {{ $allocation->vacate_reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
