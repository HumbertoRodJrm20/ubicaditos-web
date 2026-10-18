<x-passenger-layout title="Ubicaditos · Reportes y Estadísticas">
    @php
        $card = 'bg-white/75 dark:bg-gray-800/75 backdrop-blur-xl border border-white/80 dark:border-white/10 shadow-[0_14px_35px_-20px_rgba(40,50,90,0.35)] rounded-[26px]';
        $muted = 'text-slate-500 dark:text-gray-400';
        $heading = 'text-[#273252] dark:text-gray-100';

        $totalTrips = isset($tripsByDay) ? (int) $tripsByDay->sum('count') : 0;
        $tripsThisMonth = isset($tripsByDay)
            ? (int) $tripsByDay->filter(fn ($day) => \Carbon\Carbon::parse($day->date)->isSameMonth(now()))->sum('count')
            : 0;

        $totalBuses = isset($busUsage) ? $busUsage->count() : 0;
        $activeBuses = isset($activeBuses) ? $activeBuses->count() : (isset($stats['active_buses']) ? $stats['active_buses'] : null);
        $totalUsers = isset($stats['total_users'])
            ? $stats['total_users']
            : ((isset($stats['total_drivers']) && isset($stats['total_passengers'])) ? $stats['total_drivers'] + $stats['total_passengers'] : null);
        $totalDrivers = $stats['total_drivers'] ?? null;
        $totalPassengers = $stats['total_passengers'] ?? null;
        $averageRating = isset($stats['average_rating']) ? (float) $stats['average_rating'] : null;

        if ($averageRating === null && isset($topDrivers) && $topDrivers->isNotEmpty()) {
            $ratings = $topDrivers->pluck('average_rating')->filter(fn ($rating) => is_numeric($rating));
            $averageRating = $ratings->isNotEmpty() ? (float) $ratings->avg() : null;
        }

        $maxTripsPerDay = isset($tripsByDay) && $tripsByDay->isNotEmpty()
            ? max((int) $tripsByDay->max('count'), 1)
            : 1;
        $maxBusTrips = isset($busUsage) && $busUsage->isNotEmpty()
            ? max((int) $busUsage->max('trips_count'), 1)
            : 1;
    @endphp

    <style>
        .reports-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .28), transparent 30%),
                radial-gradient(circle at 94% 8%, rgba(178, 207, 232, .45), transparent 28%);
        }
        .report-stat {
            min-height: 116px;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .report-stat:hover { transform: translateY(-2px); }
        .report-number { letter-spacing: -.04em; }
        .chart-bar { min-width: 8px; border-radius: 7px 7px 2px 2px; }
        @media (prefers-reduced-motion: reduce) {
            .report-stat { transition: none; }
            .report-stat:hover { transform: none; }
        }
    </style>

    <div class="reports-page -m-4 sm:-m-6 lg:-m-8 min-h-full px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Panel de administración</p>
                    <h1 class="text-center text-[30px] font-bold leading-tight text-[#252e4b] dark:text-gray-100 sm:text-left sm:text-[36px]">
                        Reportes y Estadísticas
                    </h1>
                    <p class="mt-2 text-center text-sm text-slate-500 dark:text-gray-400 sm:text-left">
                        Un resumen del uso y la actividad de Ubicaditos.
                    </p>
                </div>
                <span class="self-center rounded-full border border-white/80 bg-white/70 px-4 py-2 text-xs font-medium text-slate-500 shadow-sm dark:border-white/10 dark:bg-gray-800/70 dark:text-gray-300 sm:self-auto">
                    <span class="mr-1.5 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    Datos del sistema
                </span>
            </div>

            {{-- Tarjetas de resumen --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:gap-5">
                <article class="{{ $card }} report-stat p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-gray-400">Viajes totales</p>
                            <p class="report-number mt-1 text-[32px] font-bold leading-none text-[#3859df]">
                                {{ number_format($totalTrips) }}
                            </p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">
                                {{ number_format($tripsThisMonth) }} este mes
                            </p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#e8edff] text-[#405fe0] dark:bg-[#34416b] dark:text-[#c5ceff]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                        </span>
                    </div>
                </article>

                <article class="{{ $card }} report-stat p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-gray-400">Autobuses</p>
                            <p class="report-number mt-1 text-[32px] font-bold leading-none text-violet-600 dark:text-violet-400">
                                {{ number_format($totalBuses) }}
                            </p>
                            <p class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">
                                {{ $activeBuses !== null ? number_format($activeBuses) . ' activos' : 'Unidades registradas en el reporte' }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="15" rx="3"/><path d="M4 10h16M8 21v-3m8 3v-3"/><circle cx="8" cy="14" r=".7"/><circle cx="16" cy="14" r=".7"/></svg>
                        </span>
                    </div>
                </article>

                <article class="{{ $card }} report-stat p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-gray-400">Usuarios</p>
                            <p class="report-number mt-1 text-[32px] font-bold leading-none text-[#3859df]">
                                {{ $totalUsers !== null ? number_format($totalUsers) : '—' }}
                            </p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">
                                @if($totalDrivers !== null || $totalPassengers !== null)
                                    {{ number_format($totalDrivers ?? 0) }} conductores, {{ number_format($totalPassengers ?? 0) }} pasajeros
                                @else
                                    Total de usuarios no disponible
                                @endif
                            </p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                    </div>
                </article>

                <article class="{{ $card }} report-stat p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-gray-400">Calificación promedio</p>
                            <p class="report-number mt-1 text-[32px] font-bold leading-none text-[#3859df]">
                                {{ $averageRating !== null ? number_format($averageRating, 1) : '—' }}
                            </p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">
                                {{ $averageRating !== null ? 'de 5.0 estrellas' : 'Sin calificaciones disponibles' }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-500 dark:bg-amber-900/30 dark:text-amber-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2.8 2.8 5.68 6.27.91-4.54 4.42 1.07 6.24L12 17.1l-5.6 2.95 1.07-6.24L2.93 9.4l6.27-.91L12 2.8Z"/></svg>
                        </span>
                    </div>
                </article>
            </div>

            {{-- Panel gráfico decorativo inspirado en el diseño de referencia --}}
            <section class="{{ $card }} mt-6 overflow-hidden p-5 sm:p-7">
                <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold {{ $heading }}">Actividad del sistema</h2>
                        <p class="mt-1 text-xs {{ $muted }}">Vista general de viajes y uso de autobuses</p>
                    </div>
                    <span class="text-xs {{ $muted }}">Últimos 30 días</span>
                </div>

                <div class="grid items-end gap-7 md:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50/80 p-4 dark:bg-gray-700/40">
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-sm font-medium {{ $heading }}">Viajes por día</span>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">VIAJES</span>
                        </div>
                        @if(isset($tripsByDay) && $tripsByDay->isNotEmpty())
                            <div class="flex h-32 items-end gap-1.5 sm:gap-2">
                                @foreach($tripsByDay->take(-14) as $day)
                                    @php $barHeight = max(5, ((int) $day->count / $maxTripsPerDay) * 100); @endphp
                                    <div class="group relative flex h-full flex-1 items-end" title="{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}: {{ $day->count }} viajes">
                                        <div class="chart-bar w-full bg-gradient-to-t from-[#4b5f9a] to-[#b8d2ec] transition-all group-hover:brightness-110" style="height: {{ $barHeight }}%"></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex justify-between text-[10px] {{ $muted }}"><span>Más antiguo</span><span>Más reciente</span></div>
                        @else
                            <p class="py-10 text-center text-sm {{ $muted }}">No hay datos de viajes disponibles.</p>
                        @endif
                    </div>

                    <div class="rounded-2xl bg-slate-50/80 p-4 dark:bg-gray-700/40">
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-sm font-medium {{ $heading }}">Uso de autobuses</span>
                            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[10px] font-semibold text-violet-700 dark:bg-violet-900/50 dark:text-violet-200">UNIDADES</span>
                        </div>
                        @if(isset($busUsage) && $busUsage->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($busUsage->sortByDesc('trips_count')->take(4) as $bus)
                                    <div>
                                        <div class="mb-1 flex items-center justify-between gap-3 text-xs">
                                            <span class="truncate font-medium {{ $heading }}">{{ $bus->license_plate }}</span>
                                            <span class="{{ $muted }}">{{ (int) $bus->trips_count }} viajes</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-gray-600">
                                            <div class="h-full rounded-full bg-gradient-to-r from-[#7788bd] to-[#b5cde9]" style="width: {{ min(((int) $bus->trips_count / $maxBusTrips) * 100, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="py-10 text-center text-sm {{ $muted }}">No hay autobuses registrados.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-center gap-5 border-t border-slate-200/70 pt-5 dark:border-gray-700">
                    <div class="flex items-center gap-2 text-xs {{ $muted }}"><span class="h-2.5 w-2.5 rounded-full bg-[#6f80b3]"></span>Viajes</div>
                    <div class="flex items-center gap-2 text-xs {{ $muted }}"><span class="h-2.5 w-2.5 rounded-full bg-[#b8d2ec]"></span>Actividad</div>
                    <div class="flex items-center gap-2 text-xs {{ $muted }}"><span class="h-2.5 w-2.5 rounded-full bg-violet-400"></span>Autobuses</div>
                </div>
            </section>

            {{-- Viajes por día --}}
            <section class="{{ $card }} mt-6 p-5 sm:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold {{ $heading }}">Detalle de viajes por día</h2>
                    <p class="mt-1 text-xs {{ $muted }}">Actividad registrada durante los últimos 30 días.</p>
                </div>
                @if(isset($tripsByDay) && $tripsByDay->isNotEmpty())
                    <div class="overflow-x-auto rounded-2xl bg-white/60 dark:bg-gray-700/30">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <th class="px-4 py-3 font-semibold">Fecha</th>
                                    <th class="px-4 py-3 font-semibold">Cantidad de viajes</th>
                                    <th class="px-4 py-3 font-semibold">Actividad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                @foreach($tripsByDay as $day)
                                    <tr class="transition hover:bg-white/70 dark:hover:bg-gray-700/50">
                                        <td class="whitespace-nowrap px-4 py-3 {{ $heading }}">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 font-semibold {{ $heading }}">{{ number_format($day->count) }}</td>
                                        <td class="min-w-36 px-4 py-3">
                                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-600">
                                                <div class="h-full rounded-full bg-[#7385b8]" style="width: {{ min(((int) $day->count / $maxTripsPerDay) * 100, 100) }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="rounded-2xl bg-slate-50/80 px-4 py-8 text-center text-sm {{ $muted }} dark:bg-gray-700/30">No hay datos de viajes disponibles.</p>
                @endif
            </section>

            {{-- Uso de autobuses --}}
            <section class="{{ $card }} mt-6 p-5 sm:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold {{ $heading }}">Uso de autobuses</h2>
                    <p class="mt-1 text-xs {{ $muted }}">Unidades ordenadas por cantidad de viajes.</p>
                </div>
                @if(isset($busUsage) && $busUsage->isNotEmpty())
                    <div class="overflow-x-auto rounded-2xl bg-white/60 dark:bg-gray-700/30">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <th class="px-4 py-3 font-semibold">Placa</th>
                                    <th class="px-4 py-3 font-semibold">Modelo</th>
                                    <th class="px-4 py-3 font-semibold">Viajes</th>
                                    <th class="px-4 py-3 font-semibold">Uso relativo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                @foreach($busUsage->sortByDesc('trips_count') as $bus)
                                    <tr class="transition hover:bg-white/70 dark:hover:bg-gray-700/50">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold {{ $heading }}">{{ $bus->license_plate }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 {{ $muted }}">{{ $bus->model }}</td>
                                        <td class="px-4 py-3 {{ $heading }}">{{ number_format($bus->trips_count) }}</td>
                                        <td class="min-w-36 px-4 py-3">
                                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-600">
                                                <div class="h-full rounded-full bg-gradient-to-r from-violet-400 to-[#8c9bd0]" style="width: {{ min(((int) $bus->trips_count / $maxBusTrips) * 100, 100) }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="rounded-2xl bg-slate-50/80 px-4 py-8 text-center text-sm {{ $muted }} dark:bg-gray-700/30">No hay autobuses registrados.</p>
                @endif
            </section>

            {{-- Conductores mejor calificados / con más viajes --}}
            <section class="{{ $card }} mt-6 p-5 sm:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold {{ $heading }}">Top 10 conductores</h2>
                    <p class="mt-1 text-xs {{ $muted }}">Ordenados según los resultados entregados por el controlador.</p>
                </div>
                @if(isset($topDrivers) && $topDrivers->isNotEmpty())
                    <div class="overflow-x-auto rounded-2xl bg-white/60 dark:bg-gray-700/30">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <th class="px-4 py-3 font-semibold">#</th>
                                    <th class="px-4 py-3 font-semibold">Conductor</th>
                                    <th class="px-4 py-3 font-semibold">Correo</th>
                                    <th class="px-4 py-3 font-semibold">Viajes</th>
                                    <th class="px-4 py-3 font-semibold">Calificación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                @foreach($topDrivers as $index => $driver)
                                    <tr class="transition hover:bg-white/70 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 font-semibold {{ $heading }}">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium {{ $heading }}">{{ $driver->name }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 {{ $muted }}">{{ $driver->email }}</td>
                                        <td class="px-4 py-3 {{ $heading }}">{{ number_format($driver->trips_count) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 {{ $heading }}">
                                            @if($driver->average_rating !== null)
                                                <span class="mr-1 text-amber-500">★</span>{{ number_format($driver->average_rating, 1) }} <span class="text-xs {{ $muted }}">/ 5</span>
                                            @else
                                                <span class="text-xs {{ $muted }}">Sin calificaciones</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="rounded-2xl bg-slate-50/80 px-4 py-8 text-center text-sm {{ $muted }} dark:bg-gray-700/30">No hay conductores registrados.</p>
                @endif
            </section>

            <p class="py-5 text-center text-xs text-slate-400 dark:text-gray-500">
                Ubicaditos · Reportes y Estadísticas
            </p>
        </div>
    </div>
</x-passenger-layout>
