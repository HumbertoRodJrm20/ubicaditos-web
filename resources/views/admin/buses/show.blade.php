<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#273252] dark:text-gray-100">
            {{ __('Detalle del Autobús') }}
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

        $statusStyle = $statusStyles[$bus->status] ?? $statusStyles['inactive'];
        $occupancy = $bus->occupancy_percentage;
    @endphp

    <style>
        .bus-show-page {
            background:
                radial-gradient(circle at 8% 12%, rgba(151, 169, 211, .30), transparent 31%),
                radial-gradient(circle at 96% 8%, rgba(178, 207, 232, .48), transparent 30%),
                linear-gradient(135deg, #f5f7fb 0%, #edf1f8 52%, #f9fafc 100%);
        }
        .bus-show-table th { font-size: 11px; }
        .bus-show-table td { font-size: 12px; }
        .bus-show-wrap { scrollbar-width: thin; scrollbar-color: #c5ccdc transparent; }
    </style>

    <div class="bus-show-page -m-4 min-h-[calc(100vh-5rem)] px-4 py-7 sm:-m-6 sm:px-6 lg:-m-8 lg:px-8">
        <div class="mx-auto max-w-[1400px]">
            @if(session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Encabezado --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-md shadow-[#4a5a8a]/25"
                         style="background-color: {{ $bus->routes->first()?->color ?? '#6b7bad' }}">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="14" rx="3" />
                            <path d="M3 10h18M7 18l-1 3M17 18l 1 3M7 14h.01M17 14h.01" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-[.18em] text-[#697aa8] dark:text-[#aab7df]">Administración de Ubicaditos</p>
                        <h1 class="truncate text-2xl font-bold tracking-tight text-[#252e4b] dark:text-gray-100 sm:text-3xl">
                            Unidad {{ $bus->license_plate }}
                        </h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusStyle['classes'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                {{ $statusStyle['label'] }}
                            </span>
                            <span class="text-xs {{ $muted }}">{{ $bus->model ?: 'Modelo no especificado' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($bus->current_latitude && $bus->current_longitude)
                        <a href="{{ route('admin.dashboard') }}#mapa"
                            class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            Ver en el mapa
                        </a>
                    @endif
                    <a href="{{ route('admin.buses.edit', $bus) }}"
                        class="inline-flex h-10 items-center justify-center rounded-full bg-gradient-to-b from-[#7b8db8] to-[#53658f] px-5 text-xs font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#8295c5] focus:ring-offset-2">
                        Editar autobús
                    </a>
                    <a href="{{ route('admin.buses.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        Volver al listado
                    </a>
                </div>
            </div>

            {{-- KPIs de ocupación --}}
            <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="{{ $panel }} p-4">
                    <p class="text-xs {{ $muted }}">Capacidad</p>
                    <p class="mt-1 text-xl font-bold {{ $heading }}">{{ number_format($bus->capacity) }} <span class="text-xs font-medium {{ $muted }}">pasajeros</span></p>
                </div>
                <div class="{{ $panel }} p-4">
                    <p class="text-xs {{ $muted }}">Ocupación actual</p>
                    <p class="mt-1 text-xl font-bold {{ $heading }}">{{ number_format($bus->current_passengers ?? 0) }} <span class="text-xs font-medium {{ $muted }}">pasajeros</span></p>
                </div>
                <div class="{{ $panel }} p-4">
                    <p class="text-xs {{ $muted }}">Asientos libres</p>
                    <p class="mt-1 text-xl font-bold {{ $heading }}">{{ number_format($bus->available_seats) }}</p>
                </div>
                <div class="{{ $panel }} p-4">
                    <p class="text-xs {{ $muted }}">Porcentaje de ocupación</p>
                    <p class="mt-1 text-xl font-bold {{ $heading }}">{{ $occupancy }}%</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-700">
                        <div class="h-full rounded-full {{ $occupancy >= 90 ? 'bg-rose-500' : ($occupancy >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min(100, $occupancy) }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Datos generales --}}
                <section class="{{ $panel }} p-4 sm:p-6">
                    <h2 class="mb-4 text-base font-semibold {{ $heading }}">Datos generales</h2>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-gray-700">
                            <dt class="{{ $muted }}">Placas</dt>
                            <dd class="font-semibold {{ $heading }}">{{ $bus->license_plate }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-gray-700">
                            <dt class="{{ $muted }}">Modelo</dt>
                            <dd class="font-semibold {{ $heading }}">{{ $bus->model ?: '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-gray-700">
                            <dt class="{{ $muted }}">Registro activo</dt>
                            <dd class="font-semibold {{ $heading }}">{{ $bus->is_active ? 'Sí' : 'No' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-gray-700">
                            <dt class="{{ $muted }}">Última ubicación</dt>
                            <dd class="font-semibold {{ $heading }}">{{ $bus->last_location_update?->diffForHumans() ?? 'Sin datos' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="{{ $muted }}">Coordenadas</dt>
                            <dd class="text-right font-mono text-xs text-slate-600 dark:text-gray-300">
                                @if ($bus->current_latitude && $bus->current_longitude)
                                    {{ $bus->current_latitude }}, {{ $bus->current_longitude }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- Conductor y rutas --}}
                <section class="{{ $panel }} p-4 sm:p-6">
                    <h2 class="mb-4 text-base font-semibold {{ $heading }}">Conductor y rutas</h2>

                    <div class="mb-4 rounded-2xl bg-slate-50 p-3 dark:bg-gray-700/40">
                        <p class="text-xs {{ $muted }}">Conductor asignado</p>
                        @if ($bus->driver)
                            <div class="mt-2 flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#7b8db8] to-[#53658f] text-xs font-bold text-white">
                                    {{ collect(explode(' ', trim($bus->driver->name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold {{ $heading }}">{{ $bus->driver->name }}</p>
                                    <p class="truncate text-xs {{ $muted }}">{{ $bus->driver->email }}</p>
                                </div>
                            </div>
                        @else
                            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-gray-300">Sin conductor asignado</p>
                        @endif
                    </div>

                    <p class="mb-2 text-xs {{ $muted }}">Rutas asignadas</p>
                    @if ($bus->routes->isEmpty())
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs {{ $muted }} dark:border-gray-700">
                            Este autobús no tiene rutas asignadas.
                        </p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($bus->routes as $route)
                                <li class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 dark:bg-gray-700/40">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $route->color ?? '#6b7bad' }}"></span>
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium {{ $heading }}">{{ $route->name }}</span>
                                    <span class="shrink-0 text-[11px] {{ $muted }}">{{ $route->code ?: '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Incidencias recientes --}}
                <section class="{{ $panel }} p-4 sm:p-6">
                    <h2 class="mb-4 text-base font-semibold {{ $heading }}">Incidencias recientes</h2>

                    @if ($emergencies->isEmpty())
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs {{ $muted }} dark:border-gray-700">
                            Sin incidencias registradas para esta unidad.
                        </p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($emergencies as $emergency)
                                @php
                                    $emergencyStatus = match ($emergency->status) {
                                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
                                        'in_progress' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
                                        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
                                    };
                                @endphp
                                <li class="rounded-2xl bg-slate-50 p-3 dark:bg-gray-700/40">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $emergencyStatus }}">{{ ucfirst($emergency->type) }}</span>
                                        <span class="text-[11px] {{ $muted }}">{{ $emergency->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-2 line-clamp-2 text-xs text-slate-600 dark:text-gray-300">{{ $emergency->description }}</p>
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('admin.emergencies') }}"
                            class="mt-4 inline-flex h-9 w-full items-center justify-center rounded-full border border-slate-200 bg-white/85 px-4 text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            Ver todas las incidencias
                        </a>
                    @endif
                </section>
            </div>

            {{-- Viajes recientes --}}
            <section class="{{ $panel }} mt-6 p-4 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold {{ $heading }}">Viajes recientes</h2>
                    <span class="rounded-full bg-[#53658f]/10 px-3 py-1 text-[11px] font-semibold text-[#53658f] dark:bg-[#8295c5]/20 dark:text-[#b6c4e6]">
                        {{ number_format($recentTrips->count()) }} viajes
                    </span>
                </div>

                @if ($recentTrips->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-10 text-center dark:border-gray-700">
                        <p class="text-sm font-semibold {{ $heading }}">Sin viajes registrados</p>
                        <p class="mt-1 text-xs {{ $muted }}">Los viajes de esta unidad aparecerán aquí.</p>
                    </div>
                @else
                    <div class="bus-show-wrap overflow-x-auto rounded-2xl border border-slate-200/80 bg-white/70 dark:border-gray-700 dark:bg-gray-800/50">
                        <table class="bus-show-table min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                            <thead class="bg-slate-100/90 dark:bg-gray-700/80">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Ruta</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Conductor</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Inicio</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Fin</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Pasajeros</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-gray-300">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white/50 dark:divide-gray-700 dark:bg-gray-800/30">
                                @foreach ($recentTrips as $trip)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-gray-100">{{ $trip->route->name ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">{{ $trip->driver->name ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">{{ $trip->started_at->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-gray-300">{{ $trip->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-gray-100">{{ number_format($trip->total_passengers ?? 0) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @php
                                                $tripStatus = match ($trip->status) {
                                                    'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
                                                    'in_progress' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
                                                    default => 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300',
                                                };
                                                $tripLabel = match ($trip->status) {
                                                    'completed' => 'Completado',
                                                    'in_progress' => 'En curso',
                                                    default => 'Cancelado',
                                                };
                                            @endphp
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium {{ $tripStatus }}">{{ $tripLabel }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
