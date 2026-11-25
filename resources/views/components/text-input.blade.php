@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200/80 bg-white/80 px-3 py-2.5 text-sm text-slate-700 shadow-sm backdrop-blur placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/25 focus:outline-none disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700/80 dark:text-gray-100 dark:placeholder:text-gray-400']) }}>
