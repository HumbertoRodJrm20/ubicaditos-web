@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-[#4b5a85] px-4 py-1.5 text-[13px] font-semibold text-white shadow-md shadow-[#4a5a8a]/25 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25 transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full px-4 py-1.5 text-[13px] font-medium text-slate-500 hover:bg-white/70 hover:text-[#3f4c75] focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/20 transition duration-150 ease-in-out dark:text-gray-300 dark:hover:bg-gray-700/60';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
