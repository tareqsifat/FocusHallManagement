<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-600">
            Your student record comes from the university and cannot be changed here. Ask the hall office if something is wrong.
        </p>
    </header>

    @php($profile = $user->studentProfile)
    <dl class="mt-6 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-gray-500">Name</dt><dd>{{ $user->name }}</dd></div>
        <div><dt class="text-gray-500">Email</dt><dd class="break-all">{{ $user->email }}</dd></div>
        @if ($profile)
            <div><dt class="text-gray-500">Registration no.</dt><dd>{{ $profile->registration_no }}</dd></div>
            <div><dt class="text-gray-500">Department</dt><dd>{{ $profile->department }}</dd></div>
            <div><dt class="text-gray-500">Session</dt><dd>{{ $profile->session }}</dd></div>
            <div><dt class="text-gray-500">Phone</dt><dd>{{ $profile->phone }}</dd></div>
        @endif
    </dl>

    @if ($profile)
        <a href="{{ route('students.show', $profile) }}" class="mt-4 inline-block text-sm text-indigo-700 hover:underline">View my room and allocation history</a>
    @endif
</section>
