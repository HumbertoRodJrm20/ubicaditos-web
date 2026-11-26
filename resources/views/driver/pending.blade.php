<x-app-layout>
    <div class="mx-auto max-w-2xl rounded-[24px] border border-white/80 bg-white/75 p-8 text-center shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75">
        <h1 class="mb-3 text-2xl font-semibold text-[#273252] dark:text-gray-100">Acceso a conductores</h1>

        @if (auth()->user()?->isRejectedApproval())
            <p class="text-sm text-rose-600 dark:text-rose-400">Tu solicitud fue rechazada. Contacta al administrador para revisar tu licencia.</p>
        @else
            <p class="text-sm text-amber-600 dark:text-amber-400">Tu cuenta está pendiente de aprobación. Recibirás acceso cuando un administrador revise tu licencia.</p>
        @endif

        <div class="mt-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-secondary-button type="submit">{{ __('Cerrar sesión') }}</x-secondary-button>
            </form>
        </div>
    </div>
</x-app-layout>
