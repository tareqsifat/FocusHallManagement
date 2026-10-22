<x-app-layout title="Student registrations">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student registrations</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <nav class="flex gap-2 text-sm" aria-label="Filter by status">
            @foreach ([App\Enums\UserStatus::Pending, App\Enums\UserStatus::Active, App\Enums\UserStatus::Rejected] as $option)
                <a href="{{ route('registrations.index', ['status' => $option->value]) }}"
                   @class(['px-3 py-1.5 rounded-full border', 'bg-indigo-600 text-white border-indigo-600' => $status === $option, 'bg-white text-gray-700 border-gray-300' => $status !== $option])>
                    {{ ucfirst($option->value) }}
                </a>
            @endforeach
        </nav>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($applicants as $applicant)
                <div class="p-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">{{ $applicant->name }}</p>
                        <p class="text-sm text-gray-600 break-all">{{ $applicant->email }}</p>
                        <p class="text-sm text-gray-600">
                            Reg. {{ $applicant->studentProfile?->registration_no }} · {{ $applicant->studentProfile?->department }} · {{ $applicant->studentProfile?->session }} · {{ $applicant->studentProfile?->phone }}
                        </p>
                        <p class="text-xs text-gray-500">Registered {{ $applicant->created_at->diffForHumans() }}</p>
                        @if ($applicant->rejection_reason)
                            <p class="text-sm text-red-700 mt-1">Reason: {{ $applicant->rejection_reason }}</p>
                        @endif
                    </div>

                    @can('decideRegistration', $applicant)
                        <div class="flex flex-col gap-2 md:items-end shrink-0">
                            <form method="POST" action="{{ route('registrations.approve', $applicant) }}">
                                @csrf
                                <x-primary-button>Approve</x-primary-button>
                            </form>
                            <details class="w-full md:w-72">
                                <summary class="cursor-pointer text-sm text-red-700">Reject…</summary>
                                <form method="POST" action="{{ route('registrations.reject', $applicant) }}" class="mt-2 space-y-2">
                                    @csrf
                                    <x-input-label for="reason-{{ $applicant->id }}" value="Reason" />
                                    <x-text-input id="reason-{{ $applicant->id }}" name="reason" class="w-full" required maxlength="500" />
                                    <x-danger-button>Reject</x-danger-button>
                                </form>
                            </details>
                        </div>
                    @endcan
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">No {{ $status->value }} registrations.</p>
            @endforelse
        </div>

        {{ $applicants->links() }}
    </div>
</x-app-layout>
