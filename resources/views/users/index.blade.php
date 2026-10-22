<x-app-layout title="Users and roles">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users and roles</h2>
        <p class="mt-1 text-sm text-gray-600">
            Staff accounts. House Tutor and Club Admin roles follow the house tutor and club screens; students get theirs at registration.
        </p>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="flex gap-2" role="search">
            <x-text-input name="search" :value="$search" placeholder="Search name or email" class="block w-full sm:w-80" aria-label="Search users" />
            <x-secondary-button type="submit">Search</x-secondary-button>
        </form>

        <x-input-error :messages="$errors->get('roles')" />

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($users as $person)
                <div class="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">
                            {{ $person->name }}
                            @unless ($person->isActive())
                                <span class="ms-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">{{ ucfirst($person->status->value) }}</span>
                            @endunless
                        </p>
                        <p class="text-sm text-gray-600 break-all">{{ $person->email }}</p>
                        <p class="mt-1 flex flex-wrap gap-1 text-xs">
                            @forelse ($person->roles->sortBy(fn ($r) => $r->name->label()) as $role)
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-800">{{ $role->name->label() }}</span>
                            @empty
                                <span class="text-gray-500">No role</span>
                            @endforelse
                        </p>
                    </div>

                    @can('updateRoles', $person)
                        <form method="POST" action="{{ route('users.roles', $person) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 shrink-0">
                            @csrf
                            @method('PUT')
                            @foreach (App\Enums\RoleName::editable() as $role)
                                <label class="inline-flex items-center gap-1.5 text-sm">
                                    <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked($person->hasRole($role)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $role->label() }}
                                </label>
                            @endforeach
                            <x-primary-button>Update role</x-primary-button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">No users found.</p>
            @endforelse
        </div>
        {{ $users->links() }}
    </div>
</x-app-layout>
