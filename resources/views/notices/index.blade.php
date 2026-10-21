<x-public-layout title="Notices">
    <h1 class="text-2xl font-semibold">Notice board</h1>

    <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="flex-1">
            <x-input-label for="search" value="Search by title" />
            <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" />
        </div>
        <div>
            <x-input-label for="category" value="Category" />
            <x-select-input id="category" name="category" class="mt-1 block w-full">
                <option value="">All categories</option>
                @foreach (App\Enums\NoticeCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
        <x-primary-button class="justify-center">Search</x-primary-button>
    </form>

    <ul class="mt-6 space-y-3">
        @forelse ($notices as $notice)
            <li>
                <a href="{{ route('notices.show', $notice) }}" @class(['block rounded-lg bg-white p-4 shadow-sm hover:shadow', 'ring-2 ring-amber-300' => $notice->is_pinned])>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        @if ($notice->is_pinned)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">Pinned</span>
                        @endif
                        <x-notice-category :category="$notice->category" />
                        <time datetime="{{ $notice->published_at->toIso8601String() }}">{{ $notice->published_at->format('d M Y') }}</time>
                        @if ($notice->attachment_path)
                            <span>· PDF attached</span>
                        @endif
                    </div>
                    <p class="mt-1 font-medium text-gray-900">{{ $notice->title }}</p>
                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $notice->body }}</p>
                </a>
            </li>
        @empty
            <li class="rounded-lg bg-white p-6 text-sm text-gray-500">No notices found.</li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $notices->links() }}</div>
</x-public-layout>
