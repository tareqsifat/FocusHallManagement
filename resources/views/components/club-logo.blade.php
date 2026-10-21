@props(['club'])

@if ($club->logo_path)
    <img src="{{ route('clubs.logo', $club) }}" alt="{{ $club->name }} logo" {{ $attributes->merge(['class' => 'rounded-lg object-cover bg-white']) }}>
@else
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'rounded-lg bg-indigo-100 text-indigo-700 font-semibold flex items-center justify-center']) }}>
        {{ Str::of($club->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('') }}
    </div>
@endif
