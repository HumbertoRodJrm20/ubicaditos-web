<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Panel') }}
        </h2>
    </x-slot>

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-7 text-center shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/80">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-lg shadow-[#4a5a8a]/30">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="3" width="16" height="14" rx="3" /><path d="M4 11h16M8 21v-4M16 21v-4" /><circle cx="8.5" cy="14" r=".6" /><circle cx="15.5" cy="14" r=".6" />
                </svg>
            </span>

            <h1 class="mt-4 text-2xl font-bold tracking-tight text-[#273252] dark:text-gray-100">Sesión iniciada</h1>
            <p class="mt-2 text-sm text-[#5b6788] dark:text-gray-400">
                Tu cuenta está activa. Elige a dónde quieres ir; el panel cambia según tu rol en Ubicaditos.
            </p>

            <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex h-11 items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-6 text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110">
                    Ir a mi panel
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="inline-flex h-11 items-center justify-center rounded-full border border-white/70 bg-white/80 px-6 text-sm font-semibold text-[#4b5a85] shadow-sm backdrop-blur transition hover:bg-white dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-200">
                    Ver mi perfil
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
