<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Complaints</h2>
            @can('create', App\Models\Complaint::class)
                <x-link-button :href="route('complaints.create')">New complaint</x-link-button>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <div class="col-span-2">
                <x-input-label for="search" value="Tracking ID or title" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <x-select-input id="status" name="status" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach (App\Enums\ComplaintStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="category" value="Category" />
                <x-select-input id="category" name="category" class="mt-1 block w-full">
                    <option value="">All</option>
                    @foreach (App\Enums\ComplaintCategory::cases() as $category)
                        <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Overdue only
            </label>
            <div class="col-span-2 md:col-span-5 flex gap-3">
                <x-primary-button>Filter</x-primary-button>
                <a href="{{ route('complaints.index') }}" class="self-center text-sm text-gray-600 hover:underline">Reset</a>
            </div>
        </form>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($complaints as $complaint)
                <a href="{{ route('complaints.show', $complaint) }}" class="block p-4 hover:bg-gray-50">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs text-gray-500">{{ $complaint->tracking_id }}</span>
                        <x-complaint-status :status="$complaint->status" />
                        @if ($complaint->isOverdue())
                            <x-overdue-badge />
                        @endif
                    </div>
                    <p class="mt-1 font-medium text-gray-900">{{ $complaint->title }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $complaint->category->label() }} · {{ $complaint->locationLabel() }} · {{ $complaint->student->user->name }}
                        · {{ $complaint->status->isFinished() ? 'submitted '.$complaint->created_at->format('d M Y') : $complaint->daysOpen().' '.Str::plural('day', $complaint->daysOpen()).' open' }}
                        @if ($complaint->assignee) · Assigned to {{ $complaint->assignee->name }} @endif
                    </p>
                </a>
            @empty
                <p class="p-6 text-sm text-gray-500">No complaints found.</p>
            @endforelse
        </div>

        {{ $complaints->links() }}
    </div>
</x-app-layout>
