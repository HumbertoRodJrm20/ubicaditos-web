@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl bg-[#4b5a85] px-4 py-2.5 text-start text-[13px] font-semibold text-white shadow-sm focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full rounded-2xl px-4 py-2.5 text-start text-[13px] font-medium text-slate-700 hover:bg-white/70 focus:outline-none transition duration-150 ease-in-out dark:text-gray-200 dark:hover:bg-gray-700/60';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
