<x-app-layout :title="$tutor->exists ? 'Edit '.$tutor->user->name : 'New house tutor'">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $tutor->exists ? 'Edit '.$tutor->user->name : 'New house tutor' }}</h2>
    </x-slot>

    @php $selected = collect(old('block_ids', $tutor->exists ? $tutor->blocks->pluck('id')->all() : []))->map(fn ($id) => (int) $id); @endphp

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-card>
            <form method="POST" action="{{ $tutor->exists ? route('house-tutors.update', $tutor) : route('house-tutors.store') }}" class="space-y-4">
                @csrf
                @if ($tutor->exists) @method('PUT') @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $tutor->user?->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="designation" value="Designation" />
                        <x-text-input id="designation" name="designation" class="mt-1 block w-full" :value="old('designation', $tutor->designation)" required placeholder="Assistant Professor" />
                        <x-input-error :messages="$errors->get('designation')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="department" value="Department" />
                        <x-select-input id="department" name="department" class="mt-1 block w-full" required>
                            <option value="">Select department</option>
                            @foreach (config('hall.departments') as $department)
                                <option @selected(old('department', $tutor->department) === $department)>{{ $department }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('department')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Phone" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $tutor->phone)" required placeholder="01XXXXXXXXX" maxlength="11" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email (login)" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $tutor->user?->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="$tutor->exists ? 'New password (optional)' : 'Initial password'" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $tutor->exists" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                </div>

                <fieldset>
                    <legend class="block font-medium text-sm text-gray-700">Blocks</legend>
                    <div class="mt-2 flex flex-wrap gap-4">
                        @foreach ($blocks as $block)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="block_ids[]" value="{{ $block->id }}" @checked($selected->contains($block->id)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $block->name }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('block_ids')" class="mt-2" />
                </fieldset>

                <div class="flex items-center gap-4">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ route('house-tutors.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
