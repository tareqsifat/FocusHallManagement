@props(['label', 'value', 'hint' => null, 'href' => null, 'alert' => false])

@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'block rounded-lg bg-white p-4 shadow-sm'.($href ? ' hover:shadow' : '')]) }}>
    <p class="text-sm text-gray-600">{{ $label }}</p>
    <p @class(['mt-1 text-3xl font-semibold tabular-nums', 'text-gray-900' => ! $alert, 'text-red-700' => $alert])>{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
