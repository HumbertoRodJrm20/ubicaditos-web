<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-rose-600 shadow-sm transition duration-150 ease-in-out hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-200/60 disabled:opacity-50 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200']) }}>
    {{ $slot }}
</button>
