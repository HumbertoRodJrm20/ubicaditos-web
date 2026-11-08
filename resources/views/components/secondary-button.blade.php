<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-white/70 bg-white/80 px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#4b5a85] shadow-sm backdrop-blur-xl transition duration-150 ease-in-out hover:bg-white focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25 disabled:opacity-50 dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-200 dark:hover:bg-gray-800']) }}>
    {{ $slot }}
</button>
