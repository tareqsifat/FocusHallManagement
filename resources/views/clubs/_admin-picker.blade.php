@php
    $selected = collect(old('admin_ids', $club->exists ? $club->admins->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
@endphp
<div>
    <x-input-label for="admin_ids" :value="'Club president and admins (approved students, up to '.config('hall.club_max_admins').')'" />
    <p class="mt-1 text-xs text-gray-500">
        Ctrl/Cmd-click to pick a second admin. Students who already lead another club are not listed.
        @if ($optional ?? false) You can leave this empty and assign admins later from Edit. @endif
    </p>
    <select id="admin_ids" name="admin_ids[]" multiple size="8" class="mt-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        @foreach ($students as $student)
            <option value="{{ $student->id }}" @selected($selected->contains($student->id))>{{ $student->name }} ({{ $student->studentProfile?->registration_no }})</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('admin_ids')" class="mt-2" />
    <x-input-error :messages="$errors->get('admin_ids.*')" class="mt-2" />
</div>
