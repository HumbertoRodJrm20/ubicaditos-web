@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-white text-start text-base font-medium text-white bg-blue-500/30 focus:outline-none focus:text-white focus:bg-blue-500/50 focus:border-blue-200 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-blue-100 hover:text-white hover:bg-blue-500/20 hover:border-blue-300 focus:outline-none focus:text-white focus:bg-blue-500/20 focus:border-blue-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
