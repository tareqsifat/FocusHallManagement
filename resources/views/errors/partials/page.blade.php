<x-public-layout :title="$heading">
    <div class="max-w-lg mx-auto bg-white shadow-sm rounded-lg p-6 sm:p-8 text-center">
        <p class="font-mono text-sm text-gray-500">Error {{ $code }}</p>
        <h1 class="mt-2 text-2xl font-semibold">{{ $heading }}</h1>
        <p class="mt-3 text-gray-600">{{ $message }}</p>

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
            @auth
                <x-link-button :href="route('dashboard')">Back to dashboard</x-link-button>
            @else
                <x-link-button :href="route('notices.index')">Go to the notice board</x-link-button>
                <a href="{{ route('login') }}" class="text-sm text-indigo-700 hover:underline">Log in</a>
            @endauth
        </div>
    </div>
</x-public-layout>
