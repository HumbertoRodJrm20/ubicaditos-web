@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1 block text-[11px] font-medium text-slate-500 dark:text-gray-400']) }}>
    {{ $value ?? $slot }}
</label>
