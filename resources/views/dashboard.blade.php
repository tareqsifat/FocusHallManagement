<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
            Welcome, {{ Auth::user()->name }}.
        </div>
    </div>
</x-app-layout>
