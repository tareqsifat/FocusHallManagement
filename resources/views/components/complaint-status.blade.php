@props(['status'])

<span {{ $attributes->merge(['class' => 'inline-block rounded-full px-2 py-0.5 text-xs font-medium '.$status->badgeClasses()]) }}>{{ $status->label() }}</span>
