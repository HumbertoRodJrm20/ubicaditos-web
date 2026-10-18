<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Gestión de Autobuses') }}
        </h2>
    </x-slot>

    @php
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';
        $panel = 'rounded-[24px] border border-white/80 bg-white/75 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/75';

        $statusStyles = [
            'active' => ['label' => 'En servicio', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800', 'dot' => 'bg-emerald-500'],
            'maintenance' => ['label' => 'En mantenimiento', 'classes' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800', 'dot' => 'bg-amber-500'],
            'emergency' => ['label' => 'Emergencia', 'classes' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-800', 'dot' => 'bg-rose-500'],
            'inactive' => ['label' => 'Inactivo', 'classes' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600', 'dot' => 'bg-slate-400'],
        ];
    @endphp

    <style>
        .buses-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        .buses-table th { font-size: 11px; letter-spacing: .01em; }
        .buses-table td { font-size: 12px; }
        .buses-table tbody tr { transition: background-color .15s ease; }
        .buses-table tbody tr:hover { background-color: rgba(226, 232, 240, .38); }
        .buses-table-wrap { scrollbar-width: thin; scrollbar-color: #c5ccdc transparent; }
        @media (prefers-reduced-motion: reduce) {
            .buses-table tbody tr { transition: none; }
        }
    </style>

    <div class="buses-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
        <div class="mx-auto max-w-[1400px]">
            @if(session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-sm text-rose-800 shadow-sm dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="mb-7 text-center">
                <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                <h1 class="text-3xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-4xl">
                    Gestión de Autobuses
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Administra la flota, sus rutas y conductores asignados.</p>
            </div>

            <section class="{{ $panel }} p-4 sm:p-6">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold {{ $heading }}">Lista de Autobuses</h2>
                        <p class="mt-1 text-xs {{ $muted }}">
                            {{ method_exists($buses, 'total') ? number_format($buses->total()) : number_format($buses->count()) }} autobuses encontrados
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <form method="GET" action="{{ route('admin.buses.index') }}" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                            <label for="bus-search" class="sr-only">Buscar autobús</label>
                            <div class="relative min-w-0 flex-1">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4 4"/>
                                </svg>
                                <input id="bus-search" type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Buscar por placa, modelo o conductor..."
                                    class="w-full rounded-full border border-slate-200/90 bg-white/90 py-2.5 pl-9 pr-4 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder:text-gray-400">
                            </div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                                Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.buses.index') }}" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">Limpiar</a>
                            @endif
                        </form>

                        <form method="GET" action="{{ route('admin.buses.index') }}" class="flex items-center gap-2">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <label for="bus-per-page" class="whitespace-nowrap text-xs {{ $muted }}">Mostrar:</label>
                            <select id="bus-per-page" name="per_page" onchange="this.form.submit()"
                                class="rounded-full border border-slate-200/90 bg-white/90 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#8295c5] focus:ring-2 focus:ring-[#8295c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if($buses->count() > 0)
                    <div class="buses-table-wrap overflow-x-auto rounded-2xl border border-slate-200/80 bg-white/70 dark:border-gray-700 dark:bg-gray-800/50">
                        <table class="buses-table min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                            <thead class="bg-slate-100/90 dark:bg-gray-700/80">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Placa</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Modelo</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Capacidad</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Conductor</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Rutas</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Estado</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white/50 dark:divide-gray-700 dark:bg-gray-800/30">
                                @foreach($buses as $bus)
                                    @php
                                        $statusStyle = $statusStyles[$bus->status] ?? $statusStyles['inactive'];
                                    @endphp
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-gray-100">
                                            {{ $bus->license_plate }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-gray-400">
                                            {{ $bus->model }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-gray-100">
                                            {{ $bus->capacity }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">
                                            @if($bus->driver)
                                                {{ $bus->driver->name }}
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500">Sin conductor</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">
                                            @if($bus->routes->count() > 0)
                                                {{ $bus->routes->pluck('name')->join(', ') }}
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500">Sin rutas</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusStyle['classes'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                                {{ $statusStyle['label'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="{{ route('admin.buses.show', $bus) }}" class="font-medium text-[#53699e] transition hover:text-[#354775] dark:text-blue-300">
                                                    Detalles
                                                </a>
                                                <a href="{{ route('admin.buses.edit', $bus) }}" class="font-medium text-slate-600 transition hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">
                                                    Editar
                                                </a>
                                                <form method="POST" action="{{ route('admin.buses.destroy', $bus) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="font-medium text-rose-600 transition hover:text-rose-800 dark:text-rose-400" onclick="return confirm('¿Estás seguro de eliminar este autobús?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $buses->links() }}
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center dark:border-gray-700">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-gray-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="3"/><path d="M3 10h18M7 18l-1 3M17 18l 1 3M7 14h.01M17 14h.01"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold {{ $heading }}">No hay autobuses registrados</p>
                        <p class="mt-1 text-xs {{ $muted }}">Registra la primera unidad para comenzar a dar seguimiento a la flota.</p>
                        <a href="{{ route('admin.buses.create') }}" class="mt-4 inline-flex h-10 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                            Agregar Autobús
                        </a>
                    </div>
                @endif
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">Ubicaditos · Gestión de Autobuses</p>
        </div>
    </div>
</x-app-layout>
