<x-app-layout title="Manage notices">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage notices</h2>
            <x-link-button :href="route('admin.notices.create')">New notice</x-link-button>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <nav class="flex gap-2 text-sm" aria-label="Filter by status">
            @foreach (['' => 'All', 'published' => 'Published', 'draft' => 'Drafts'] as $value => $label)
                <a href="{{ route('admin.notices.index', array_filter(['status' => $value])) }}"
                   @class(['px-3 py-1.5 rounded-full border', 'bg-indigo-600 text-white border-indigo-600' => request('status', '') === $value, 'bg-white text-gray-700 border-gray-300' => request('status', '') !== $value])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
            @forelse ($notices as $notice)
                <div class="p-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span @class(['rounded-full px-2 py-0.5 font-medium', 'bg-green-100 text-green-800' => $notice->isPublished(), 'bg-gray-200 text-gray-700' => ! $notice->isPublished()])>{{ ucfirst($notice->status->value) }}</span>
                            @if ($notice->is_pinned)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">Pinned</span>
                            @endif
                            <x-notice-category :category="$notice->category" />
                        </div>
                        <p class="mt-1 font-medium text-gray-900">{{ $notice->title }}</p>
                        <p class="text-xs text-gray-500">By {{ $notice->author->name }} · updated {{ $notice->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-4 shrink-0 text-sm">
                        @if ($notice->isPublished())
                            <a href="{{ route('notices.show', $notice) }}" class="text-gray-700 hover:underline">View</a>
                        @endif
                        <a href="{{ route('admin.notices.edit', $notice) }}" class="text-indigo-700 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.notices.destroy', $notice) }}" onsubmit="return confirm('Delete this notice?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">No notices yet.</p>
            @endforelse
        </div>
        {{ $notices->links() }}
    </div>
</x-app-layout>
