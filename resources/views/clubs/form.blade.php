<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $club->exists ? "Edit {$club->name}" : 'New club' }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-card>
            <form method="POST" action="{{ $club->exists ? route('clubs.update', $club) : route('clubs.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if ($club->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $club->name)" required maxlength="100" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Short description" />
                    <textarea id="description" name="description" rows="4" maxlength="1000" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $club->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="logo" value="Logo (optional, JPG or PNG, up to 1 MB)" />
                    @if ($club->logo_path)
                        <x-club-logo :club="$club" class="mt-2 h-16 w-16" />
                    @endif
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" class="mt-2 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm">
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>
                @unless ($club->exists)
                    @include('clubs._admin-picker', ['optional' => true])
                @endunless

                <x-primary-button>Save</x-primary-button>
            </form>
        </x-card>

        @if ($club->exists)
            @can('assignAdmins', $club)
                <x-card title="Club admins">
                    <form method="POST" action="{{ route('clubs.admins', $club) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        @include('clubs._admin-picker')
                        <x-primary-button>Save admins</x-primary-button>
                    </form>
                </x-card>
            @endcan
        @endif
    </div>
</x-app-layout>
