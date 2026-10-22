<x-guest-layout title="Register">
    <h1 class="text-lg font-semibold text-gray-900">Student registration</h1>
    <p class="mt-1 text-sm text-gray-600">Your account stays pending until the hall office approves it.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-4 space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Full name" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="registration_no" value="Registration number (10 digits)" />
            <x-text-input id="registration_no" class="block mt-1 w-full" type="text" name="registration_no" :value="old('registration_no')" required inputmode="numeric" pattern="\d{10}" maxlength="10" />
            <x-input-error :messages="$errors->get('registration_no')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="department" value="Department" />
            <x-select-input id="department" name="department" class="block mt-1 w-full" required>
                <option value="">Select department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                @endforeach
            </x-select-input>
            <x-input-error :messages="$errors->get('department')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="session" value="Session" />
                <x-text-input id="session" class="block mt-1 w-full" type="text" name="session" :value="old('session')" required placeholder="2023-24" />
                <x-input-error :messages="$errors->get('session')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required placeholder="01XXXXXXXXX" inputmode="numeric" maxlength="11" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="University email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="you@student.du.test" autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">Already registered?</a>
            <x-primary-button class="ms-4">Register</x-primary-button>
        </div>
    </form>
</x-guest-layout>
