<x-public-layout :title="$notice->title">
    <a href="{{ route('notices.index') }}" class="text-sm text-indigo-700 hover:underline">← All notices</a>

    <article class="mt-4 rounded-lg bg-white p-6 shadow-sm">
        @unless ($notice->isPublished())
            <p class="mb-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">Draft preview. This notice is not public.</p>
        @endunless
        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
            <x-notice-category :category="$notice->category" />
            @if ($notice->published_at)
                <time datetime="{{ $notice->published_at->toIso8601String() }}">{{ $notice->published_at->format('d M Y, h:i A') }}</time>
            @endif
        </div>
        <h1 class="mt-2 text-2xl font-semibold">{{ $notice->title }}</h1>
        <div class="mt-4 whitespace-pre-line text-gray-800 leading-relaxed">{{ $notice->body }}</div>

        @if ($notice->attachment_path)
            <a href="{{ route('notices.attachment', $notice) }}" class="mt-6 inline-flex items-center gap-2 rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                Download attachment (PDF)
            </a>
        @endif
    </article>
</x-public-layout>
