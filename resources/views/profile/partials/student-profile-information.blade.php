<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-600">
            You can update your name and phone. Your registration number, department, session and email come from the university record. Ask the hall office if one of them is wrong.
        </p>
    </header>

    @php($profile = $user->studentProfile)
    <dl class="mt-6 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-gray-500">Email</dt><dd class="break-all">{{ $user->email }}</dd></div>
        @if ($profile)
            <div><dt class="text-gray-500">Registration no.</dt><dd>{{ $profile->registration_no }}</dd></div>
            <div><dt class="text-gray-500">Department</dt><dd>{{ $profile->department }}</dd></div>
            <div><dt class="text-gray-500">Session</dt><dd>{{ $profile->session }}</dd></div>
        @endif
    </dl>

    @if ($profile)
        <form method="post" action="{{ route('profile.student.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('patch')

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $profile->phone)" required autocomplete="tel" placeholder="01XXXXXXXXX" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        <a href="{{ route('students.show', $profile) }}" class="mt-4 inline-block text-sm text-indigo-700 hover:underline">View my room and allocation history</a>
    @endif
</section>
