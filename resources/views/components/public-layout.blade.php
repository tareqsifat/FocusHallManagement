<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                <a href="{{ route('notices.index') }}" class="flex items-center gap-2 min-w-0">
                    <x-application-logo class="h-8 w-auto shrink-0 fill-current text-gray-800" />
                    <span class="font-semibold truncate">{{ config('hall.name') }}</span>
                </a>
                <nav class="flex items-center gap-4 text-sm shrink-0">
                    <a href="{{ route('notices.index') }}" class="text-gray-700 hover:text-gray-900">Notices</a>
                    @if (Route::has('clubs.index'))
                        <a href="{{ route('clubs.index') }}" class="text-gray-700 hover:text-gray-900">Clubs</a>
                    @endif
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-indigo-700 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-indigo-700 font-medium">Log in</a>
                    @endauth
                </nav>
            </div>
        </header>
        <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
            @if (session('status'))
                <div role="status" class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (isset($errors) && $errors->any()) {{-- not shared on a 404 for an unmatched route --}}
                <div role="alert" class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            {{ $slot }}
        </main>
    </body>
</html>
