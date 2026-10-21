<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $block->exists ? "Edit {$block->name}" : 'New block' }}</h2>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-card>
            <form method="POST" action="{{ $block->exists ? route('blocks.update', $block) : route('blocks.store') }}" class="space-y-4">
                @csrf
                @if ($block->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $block->name)" required maxlength="50" placeholder="North" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="code" value="Code" />
                    <x-text-input id="code" name="code" class="mt-1 block w-full uppercase" :value="old('code', $block->code)" required maxlength="10" placeholder="N" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ route('blocks.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
