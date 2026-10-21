<section {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg']) }}>
    @isset($title)
        <h3 class="px-4 sm:px-6 pt-4 font-semibold text-gray-900">{{ $title }}</h3>
    @endisset
    <div class="p-4 sm:p-6">{{ $slot }}</div>
</section>
