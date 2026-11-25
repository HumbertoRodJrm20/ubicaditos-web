@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-3 py-2 text-xs font-medium text-emerald-700 backdrop-blur dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200']) }}>
        {{ $status }}
    </div>
@endif
