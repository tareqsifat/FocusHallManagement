<x-app-layout :title="$notice->exists ? 'Edit notice' : 'New notice'">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $notice->exists ? 'Edit notice' : 'New notice' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-card>
            <form method="POST" action="{{ $notice->exists ? route('admin.notices.update', $notice) : route('admin.notices.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if ($notice->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $notice->title)" required maxlength="200" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="body" value="Body" />
                    <textarea id="body" name="body" rows="10" maxlength="20000" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body', $notice->body) }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="category" value="Category" />
                        <x-select-input id="category" name="category" class="mt-1 block w-full" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" @selected(old('category', $notice->category?->value) === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full" required>
                            @foreach (App\Enums\NoticeStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $notice->status->value) === $status->value)>{{ ucfirst($status->value) }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_pinned" value="0">
                    <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $notice->is_pinned)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Pin to the top of the board
                </label>

                <div>
                    <x-input-label for="attachment" value="PDF attachment (optional, up to 5 MB)" />
                    @if ($notice->attachment_path)
                        <p class="mt-1 text-sm">
                            @if ($notice->isPublished())
                                <a href="{{ route('notices.attachment', $notice) }}" class="text-indigo-700 hover:underline">Current attachment</a>
                            @else
                                <span class="text-gray-700">A PDF is attached.</span>
                            @endif
                            <label class="ms-3 inline-flex items-center gap-1 text-gray-600">
                                <input type="hidden" name="remove_attachment" value="0">
                                <input type="checkbox" name="remove_attachment" value="1" class="rounded border-gray-300"> Remove
                            </label>
                        </p>
                    @endif
                    <input id="attachment" name="attachment" type="file" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm">
                    <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ route('admin.notices.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
