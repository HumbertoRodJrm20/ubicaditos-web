@props(['active' => null])

@php
    // Nombres de ruta del pasajero. Si una ruta no existe, el enlace queda en "#" y no truena.
    // ⚠️ Pendiente: 'Inicio' debe ser el nombre de la ruta que muestra menu.blade
    //    (búscalo con: php artisan route:list --name=passenger)

    
    $user = Auth::user();

    $isDriver = request()->routeIs('driver.*')
        || ($user->role ?? null) === 'driver';

    $isAdmin = request()->routeIs('admin.*')
        || ($user->role ?? null) === 'admin';

    if ($isDriver) {
        $items = [
            'Inicio'       => 'driver.dashboard',
            'Mapa'         => 'driver.map',
            'Estadísticas' => 'driver.statistics',
        ];
    } elseif ($isAdmin) {
        $items = [
            'Inicio'       => 'admin.dashboard',
            'Autobuses'    => 'admin.buses.index',
            'Rutas'        => 'admin.routes.index',
            'Conductores'  => 'admin.drivers',
            'Pasajeros'    => 'admin.passengers',
            'Emergencias'  => 'admin.emergencies',
            'Reportes'     => 'admin.reports',
            'Estadísticas' => 'admin.statistics',
        ];
    } else {
        $items = [
            'Inicio'    => 'passenger.menu',
            'Mapa'      => 'passenger.dashboard',
            'Rutas'     => 'passenger.routes',
            'Autobuses' => 'passenger.buses',
        ];
    }


    // La pestaña activa se detecta sola por la ruta actual.
    // Si pasas :active="'Rutas'" (o active="Rutas") se usa esa en su lugar.
    $links = collect($items)->map(fn ($name, $label) => [
        'label'  => $label,
        'href'   => Route::has($name) ? route($name) : '#',
        'active' => $active
            ? $active === $label
            : (Route::has($name) && request()->routeIs($name, $name . '.*')),
    ])->values();

    $user = Auth::user();
    $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Usuario');
    $initials = collect(explode(' ', trim($userName)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
@endphp

<nav class="pointer-events-auto relative grid grid-cols-[1fr_auto_1fr] items-center h-[52px] px-3 rounded-full bg-white/75 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg">
    <a href="{{ $links->firstWhere('label', 'Inicio')['href'] ?? '#' }}" class="flex items-center gap-3 justify-self-start">
        <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white flex items-center justify-center">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>
        </span>
        <span class="text-[15px] font-bold text-[#2f3a5f] dark:text-gray-100">Ubicaditos</span>
    </a>

    {{-- Enlaces (escritorio) --}}
    <div class="hidden md:flex items-center gap-1">
        @foreach($links as $link)
            <a href="{{ $link['href'] }}"
               class="px-4 py-1.5 rounded-full text-[13px] font-medium transition {{ $link['active'] ? 'bg-[#4b5a85] text-white shadow' : 'text-slate-500 dark:text-gray-300 hover:text-[#3f4c75]' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>

    <div class="flex items-center gap-2 justify-self-end col-start-3">
        <button type="button" class="w-8 h-8 rounded-full bg-white/80 dark:bg-gray-700 border border-slate-200/70 dark:border-gray-600 flex items-center justify-center text-slate-600 dark:text-gray-200" aria-label="Modo oscuro">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        </button>

        <div class="relative">
            <button type="button" data-toggle="user-menu" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white/80 dark:bg-gray-700 border border-slate-200/70 dark:border-gray-600">
                <span class="w-7 h-7 rounded-full bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white text-[10px] font-bold flex items-center justify-center">{{ $initials }}</span>
                <span class="hidden sm:block text-[12px] font-semibold text-[#2f3a5f] dark:text-gray-100">{{ $userName }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div id="user-menu" data-popover class="hidden absolute right-0 top-[calc(100%+8px)] w-48 py-1 rounded-2xl bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-xl">
                @if(Route::has('profile.edit'))
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-[13px] text-slate-700 dark:text-gray-200 hover:bg-slate-100/70 dark:hover:bg-gray-700">Perfil</a>
                @endif
                @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-[13px] text-slate-700 dark:text-gray-200 hover:bg-slate-100/70 dark:hover:bg-gray-700">Cerrar sesión</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Botón de menú (móvil) --}}
        <button type="button" data-toggle="mobile-menu" aria-label="Menú" class="md:hidden w-8 h-8 rounded-full bg-white/80 dark:bg-gray-700 border border-slate-200/70 dark:border-gray-600 flex items-center justify-center text-slate-600 dark:text-gray-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"></path></svg>
        </button>
    </div>

    {{-- Enlaces (móvil) --}}
    <div id="mobile-menu" data-popover class="hidden md:hidden absolute left-0 right-0 top-[calc(100%+8px)] p-2 rounded-3xl bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-xl">
        @foreach($links as $link)
            <a href="{{ $link['href'] }}"
               class="block px-4 py-2.5 rounded-2xl text-[13px] font-medium {{ $link['active'] ? 'bg-[#4b5a85] text-white' : 'text-slate-700 dark:text-gray-200 hover:bg-slate-100/70 dark:hover:bg-gray-700' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>

<script>
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-toggle]');
        document.querySelectorAll('[data-popover]').forEach(function (popover) {
            if (trigger && popover.id === trigger.dataset.toggle) {
                popover.classList.toggle('hidden');
            } else if (!popover.contains(e.target)) {
                popover.classList.add('hidden');
            }
        });
    });
</script>