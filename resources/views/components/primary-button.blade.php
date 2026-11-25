<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-gradient-to-b from-[#7b8db8] to-[#4a5a8a] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white shadow-lg shadow-[#4a5a8a]/25 transition duration-150 ease-in-out hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30 disabled:opacity-50 dark:border-white/10']) }}>
    {{ $slot }}
</button>
