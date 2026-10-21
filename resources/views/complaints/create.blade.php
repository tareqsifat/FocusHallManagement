<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New complaint</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-card>
            <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="category" value="Category" />
                    <x-select-input id="category" name="category" class="mt-1 block w-full" required>
                        <option value="">Select category</option>
                        @foreach (App\Enums\ComplaintCategory::cases() as $category)
                            <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required maxlength="150" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="5" maxlength="5000" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="location_text" value="Location" />
                    @if ($room)
                        <p class="mt-1 text-sm text-gray-700">Your room, {{ $room->block->name }} · Room {{ $room->room_no }}, will be attached automatically.</p>
                    @else
                        <x-text-input id="location_text" name="location_text" class="mt-1 block w-full" :value="old('location_text')" required maxlength="255" placeholder="e.g. Ground floor common room" />
                        <p class="mt-1 text-xs text-gray-500">You do not have a seat, so tell us where the problem is.</p>
                    @endif
                    <x-input-error :messages="$errors->get('location_text')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="photo" value="Photo (optional, JPG or PNG, up to 2 MB)" />
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm">
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>

                <div class="rounded-md border border-gray-200 p-3">
                    <label class="flex items-start gap-2 text-sm">
                        <input type="hidden" name="is_anonymous" value="0">
                        <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous')) class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="font-medium text-gray-900">Submit anonymously</span>
                            <span class="block text-gray-600">Hall office staff and house tutors will not see your name. Only the Provost and the Super Admin can see who filed it. Your room or location is still shown so the problem can be fixed.</span>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('is_anonymous')" class="mt-2" />
                </div>

                <x-primary-button>Submit complaint</x-primary-button>
            </form>
        </x-card>
    </div>
</x-app-layout>
