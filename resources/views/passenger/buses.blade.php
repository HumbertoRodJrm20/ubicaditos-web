@php
    // $buses, $routes y $busDetailsData vienen filtrados desde PassengerController (q, route_id, status, sort).

    $busFilterData = $buses->map(fn ($bus) => [
        'plate' => mb_strtolower($bus->license_plate ?? ''),
        'model' => mb_strtolower($bus->model ?? ''),
        'driver' => mb_strtolower($bus->driver->name ?? ''),
        'route' => mb_strtolower($bus->routes->first()->name ?? ''),
    ])->values();

    $statusStyles = [
        'active' => ['label' => 'En servicio', 'classes' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'dot' => 'bg-emerald-500'],
        'emergency' => ['label' => 'Emergencia', 'classes' => 'bg-rose-50 text-rose-600 ring-rose-100', 'dot' => 'bg-rose-500'],
        'maintenance' => ['label' => 'En mantenimiento', 'classes' => 'bg-amber-50 text-amber-600 ring-amber-100', 'dot' => 'bg-amber-500'],
        'inactive' => ['label' => 'Inactivo', 'classes' => 'bg-slate-100 text-slate-500 ring-slate-200', 'dot' => 'bg-slate-400'],
    ];

    $statusOptions = [
        'active' => 'En servicio',
        'maintenance' => 'Mantenimiento',
        'emergency' => 'Emergencia',
        'all' => 'Todos los estados',
    ];

    // Construye una URL conservando los filtros actuales y sobrescribiendo los indicados.
    $filterUrl = fn (array $overrides) => route('passenger.buses', array_filter(
        array_merge(
            [
                'q' => request('q'),
                'route_id' => request('route_id'),
                'status' => request('status'),
                'sort' => request('sort'),
            ],
            $overrides
        ),
        fn ($value) => filled($value)
    ));

    $currentStatus = request('status', 'active');
    $currentSort = request('sort', 'plate');
    $currentRouteId = request('route_id');

    $chipBase = 'inline-flex h-8 items-center justify-center rounded-full px-3.5 text-xs font-semibold transition focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25';
    $chipOn = 'bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/25';
    $chipOff = 'border border-white/70 bg-white/80 text-[#3d4a6b] hover:bg-white';
@endphp

<x-passenger-layout title="Autobuses · Ubicaditos">
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Encabezado y búsqueda --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-[28px] font-bold leading-tight tracking-tight text-[#1e2a4a] sm:text-3xl">
                    Autobuses en servicio
                </h1>
                <p class="mt-1 text-xs text-[#5b6788]">
                    Consulta la unidad, su conductor y califica tu viaje
                </p>
            </div>

            {{-- Búsqueda: filtra al instante (JS) y también en el servidor al enviar el formulario --}}
            <form method="GET" action="{{ route('passenger.buses') }}" class="w-full sm:w-72" role="search">
                @foreach (array_filter(['route_id' => $currentRouteId, 'status' => $currentStatus !== 'active' ? $currentStatus : null, 'sort' => $currentSort !== 'plate' ? $currentSort : null]) as $param => $value)
                    <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                @endforeach

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[#8a94b3]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </span>
                    <input
                        id="bus-search"
                        name="q"
                        type="search"
                        value="{{ request('q') }}"
                        oninput="filterBuses()"
                        aria-label="Buscar autobús"
                        placeholder="Buscar por placas o conductor"
                        class="h-11 w-full rounded-full border border-white/70 bg-white/85 pl-11 pr-4 text-sm text-[#1e2a4a] shadow-lg shadow-[#4a5a8a]/10 backdrop-blur-xl placeholder:text-[#8a94b3] focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/25"
                    />
                </div>
            </form>
        </header>

        {{-- Filtros por estado y ordenamiento (navegan al servidor) --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2" id="status-chips">
                @foreach ($statusOptions as $statusKey => $statusLabel)
                    <a href="{{ $filterUrl(['status' => $statusKey === 'active' ? null : $statusKey]) }}"
                       aria-current="{{ $currentStatus === $statusKey ? 'true' : 'false' }}"
                       class="{{ $chipBase }} {{ $currentStatus === $statusKey ? $chipOn : $chipOff }}">
                        {{ $statusLabel }}
                    </a>
                @endforeach
            </div>

            <label class="flex items-center gap-2 text-xs text-[#5b6788]">
                <span class="whitespace-nowrap">Ordenar</span>
                <select onchange="location = this.value"
                        aria-label="Ordenar autobuses"
                        class="h-9 rounded-full border border-white/70 bg-white/85 px-3 text-xs text-[#1e2a4a] shadow-sm focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/25">
                    <option value="{{ $filterUrl(['sort' => null]) }}" {{ $currentSort === 'plate' ? 'selected' : '' }}>
                        Por placa (A-Z)
                    </option>
                    <option value="{{ $filterUrl(['sort' => 'occupancy']) }}" {{ $currentSort === 'occupancy' ? 'selected' : '' }}>
                        Por ocupación (mayor primero)
                    </option>
                </select>
            </label>
        </div>

        {{-- Filtros por ruta --}}
        <div class="flex flex-wrap gap-2" id="route-chips">
            <a href="{{ $filterUrl(['route_id' => null]) }}"
               aria-current="{{ $currentRouteId ? 'false' : 'true' }}"
               class="{{ $chipBase }} {{ $currentRouteId ? $chipOff : $chipOn }}">
                Todas las rutas
            </a>
            @foreach ($routes as $route)
                <a href="{{ $filterUrl(['route_id' => $route->id]) }}"
                   aria-current="{{ $currentRouteId == $route->id ? 'true' : 'false' }}"
                   class="{{ $chipBase }} {{ $currentRouteId == $route->id ? $chipOn : $chipOff }}">
                    {{ $route->name }}
                </a>
            @endforeach
        </div>

        @if ($buses->isEmpty())
            <div class="mx-auto mt-8 max-w-md rounded-[28px] border border-white/70 bg-white/80 p-10 text-center shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/30">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="14" rx="3" />
                        <path d="M3 10h18M7 18l-1 3M17 18l1 3M7 14h.01M17 14h.01" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-[#1e2a4a]">No hay autobuses que coincidan</h3>
                <p class="mt-1 text-xs text-[#5b6788]">
                    Ajusta la búsqueda o los filtros para ver más unidades.
                </p>
                @if (request()->hasAny(['q', 'route_id', 'status']))
                    <a href="{{ route('passenger.buses') }}"
                       class="mt-4 inline-flex h-9 items-center justify-center rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-5 text-xs font-semibold text-white shadow-md shadow-[#4a5a8a]/25">
                        Limpiar filtros
                    </a>
                @endif
            </div>
        @else
            {{-- Tarjetas de autobuses --}}
            <div id="bus-grid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($buses as $bus)
                    @php
                        $busRoute = $bus->routes->first();
                        $driver = $bus->driver;
                        $routeColor = $busRoute->color ?? '#6b7bad';
                        $statusStyle = $statusStyles[$bus->status] ?? $statusStyles['inactive'];
                    @endphp

                    <article
                        class="bus-card flex min-h-[176px] flex-col justify-between gap-4 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-[#4a5a8a]/15"
                        data-plate="{{ mb_strtolower($bus->license_plate ?? '') }}"
                        data-model="{{ mb_strtolower($bus->model ?? '') }}"
                        data-driver="{{ mb_strtolower($driver->name ?? '') }}"
                        data-route="{{ mb_strtolower($busRoute->name ?? '') }}"
                    >
                        {{-- Unidad y estado --}}
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-md shadow-[#4a5a8a]/20"
                                 style="background-color: {{ $routeColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.28), rgba(0,0,0,.14));">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="14" rx="3" />
                                    <path d="M3 10h18M7 18l-1 3M17 18l1 3M7 14h.01M17 14h.01" />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-[15px] font-bold leading-tight text-[#1e2a4a]">
                                    Unidad {{ $bus->license_plate }}
                                </h2>
                                <p class="mt-0.5 truncate text-[11px] text-[#8a94b3]">
                                    {{ $bus->model ?: 'Modelo no especificado' }}
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1 {{ $statusStyle['classes'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                {{ $statusStyle['label'] }}
                            </span>
                        </div>

                        {{-- Ruta asignada --}}
                        <div class="flex min-w-0 items-center gap-2 text-xs text-[#5b6788]">
                            <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $routeColor }}"></span>
                            <span class="truncate">
                                {{ $busRoute ? $busRoute->name : 'Sin ruta asignada' }}
                            </span>
                        </div>

                        {{-- Ocupación y asientos libres --}}
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-[10px] font-semibold text-[#5b6788]">
                                <span>{{ $bus->current_passengers ?? 0 }}/{{ $bus->capacity }} ocupados</span>
                                <span class="{{ $bus->available_seats > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $bus->available_seats > 0 ? $bus->available_seats . ' asientos libres' : 'Sin asientos libres' }}
                                </span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $bus->occupancy_percentage >= 90 ? 'bg-rose-500' : ($bus->occupancy_percentage >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                     style="width: {{ $bus->occupancy_percentage }}%"
                                     role="progressbar"
                                     aria-valuenow="{{ $bus->occupancy_percentage }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     aria-label="Ocupación actual"></div>
                            </div>
                        </div>

                        {{-- Conductor --}}
                        <div class="flex items-center gap-2.5 rounded-2xl bg-white/75 px-2.5 py-2">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-[11px] font-bold text-white shadow-sm">
                                @if ($driver)
                                    {{ collect(explode(' ', trim($driver->name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
                                @else
                                    --
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-[#1e2a4a]">
                                    {{ $driver->name ?? 'Conductor sin asignar' }}
                                </p>
                                <p class="text-[10px] text-[#8a94b3]">Conductor</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <div class="text-sm leading-none tracking-wide text-[#efb45e]" aria-label="Calificación no disponible">★★★★★</div>
                                <p class="mt-1 text-[9px] text-[#5b6788]">Califica tu viaje</p>
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onclick="showBusDetails({{ $bus->id }})"
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-2 text-[11px] font-semibold text-white shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" /><path d="M12 8h.01M11 12h1v4h1" />
                                </svg>
                                Ver detalles
                            </button>

                            <a href="{{ route('passenger.dashboard', ['bus_id' => $bus->id]) }}"
                               class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 text-[11px] font-semibold text-[#3d4a6b] transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20V6.5Z" />
                                    <path d="M9 4v13.5M15 6.5V20" />
                                </svg>
                                Ver en mapa
                            </a>
                        </div>

                        @if ($driver)
                            <button
                                type="button"
                                onclick="showRatingModal({{ $driver->id }}, @js($driver->name))"
                                class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 text-[11px] font-semibold text-[#3d4a6b] transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" />
                                </svg>
                                Calificar conductor
                            </button>
                        @else
                            <span class="inline-flex h-9 w-full items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-2 text-[11px] text-slate-400">
                                Sin conductor
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>

            <div id="no-buses-message" class="hidden rounded-2xl border border-white/70 bg-white/70 px-4 py-8 text-center text-sm text-[#5b6788]">
                No se encontraron autobuses con los filtros seleccionados.
            </div>
        @endif
    </div>

    {{-- Modal de calificación --}}
    <div id="rating-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-[#1e2a4a]/35 p-4 backdrop-blur-sm">
        <div class="my-auto w-full max-w-md rounded-[28px] border border-white/70 bg-white p-6 shadow-2xl shadow-[#4a5a8a]/20">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-[#1e2a4a]">Calificar conductor</h3>
                    <p class="mt-1 text-xs text-[#5b6788]">Cuéntanos cómo fue tu experiencia.</p>
                </div>
                <button type="button" onclick="closeRatingModal()" aria-label="Cerrar modal"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="rating-form" method="POST" action="">
                @csrf
                <input type="hidden" id="driver-id" name="driver_id" value="">

                <div class="mb-5 rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-xs text-[#5b6788]">Conductor</p>
                    <p id="driver-name" class="mt-1 font-semibold text-[#1e2a4a]"></p>
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-[#3d4a6b]">Calificación</label>
                    <div class="flex gap-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" onclick="setRating({{ $i }})"
                                class="star-btn text-3xl text-slate-300 transition hover:scale-110 hover:text-[#efb45e] focus:outline-none"
                                data-rating="{{ $i }}" aria-label="{{ $i }} estrellas">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="rating-value" required>
                </div>

                <div class="mb-6">
                    <label for="rating-comment" class="mb-2 block text-sm font-semibold text-[#3d4a6b]">
                        Comentario <span class="font-normal text-[#8a94b3]">(opcional)</span>
                    </label>
                    <textarea id="rating-comment" name="comment" rows="3" maxlength="500"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#1e2a4a] placeholder:text-[#a0a8bd] focus:border-[#6b7bad] focus:ring-4 focus:ring-[#6b7bad]/20"
                        placeholder="Comparte tu experiencia..."></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#4a5a8a]/20 transition hover:brightness-105">
                        Enviar calificación
                    </button>
                    <button type="button" onclick="closeRatingModal()"
                        class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#5b6788] transition hover:bg-slate-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal de detalles del autobús (Requisito 4) --}}
    <div id="bus-details-modal" class="fixed inset-0 z-[60] hidden items-center justify-center overflow-y-auto bg-[#1e2a4a]/35 p-4 backdrop-blur-sm">
        <div class="my-auto w-full max-w-md rounded-[28px] border border-white/70 bg-white p-6 shadow-2xl shadow-[#4a5a8a]/20">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div id="bd-icon" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-md shadow-[#4a5a8a]/20" style="background-color: #6b7bad;">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="14" rx="3" />
                            <path d="M3 10h18M7 18l-1 3M17 18l1 3M7 14h.01M17 14h.01" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="bd-plate" class="truncate text-lg font-bold leading-tight text-[#1e2a4a]">—</h3>
                        <p id="bd-model" class="mt-0.5 truncate text-[11px] text-[#8a94b3]">—</p>
                    </div>
                </div>
                <button type="button" onclick="closeBusDetails()" aria-label="Cerrar modal"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="mb-4">
                <span id="bd-status" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-600 ring-1 ring-emerald-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> En servicio
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Ruta</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span id="bd-route-color" class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: #6b7bad;"></span>
                        <p id="bd-route" class="truncate font-semibold text-[#1e2a4a]">—</p>
                    </div>
                    <p id="bd-route-code" class="mt-0.5 text-[10px] text-[#8a94b3]"></p>
                </div>

                <div class="rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Capacidad</p>
                    <p id="bd-capacity" class="mt-1 font-semibold text-[#1e2a4a]">—</p>
                </div>

                <div class="rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Pasajeros actuales</p>
                    <p id="bd-passengers" class="mt-1 font-semibold text-[#1e2a4a]">—</p>
                </div>

                <div class="col-span-2 rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Asientos libres</p>
                    <p id="bd-seats" class="mt-1 font-semibold text-[#1e2a4a]">—</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                        <div id="bd-occupancy-bar" class="h-full w-0 rounded-full bg-emerald-500 transition-all"></div>
                    </div>
                </div>

                <div class="col-span-2 rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Conductor</p>
                    <p id="bd-driver" class="mt-1 truncate font-semibold text-[#1e2a4a]">—</p>
                </div>

                <div class="col-span-2 rounded-2xl bg-[#f3f5fb] p-3">
                    <p class="text-[11px] text-[#5b6788]">Última actualización de ubicación</p>
                    <p id="bd-update" class="mt-1 font-semibold text-[#1e2a4a]">—</p>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <a id="bd-map-link" href="#"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] px-4 text-sm font-semibold text-white shadow-md shadow-[#4a5a8a]/20 transition hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20V6.5Z" />
                        <path d="M9 4v13.5M15 6.5V20" />
                    </svg>
                    Ver en mapa
                </a>
                <button type="button" onclick="closeBusDetails()"
                    class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-4 text-sm font-semibold text-[#5b6788] transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/25">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Búsqueda instantánea entre las tarjetas renderizadas por el servidor.
        // (La búsqueda, el estado, la ruta y el orden también se aplican en el servidor vía query string.)
        function filterBuses() {
            const searchInput = document.getElementById('bus-search');
            const query = (searchInput?.value || '').trim().toLowerCase();
            const cards = document.querySelectorAll('.bus-card');
            const noResults = document.getElementById('no-buses-message');
            const grid = document.getElementById('bus-grid');
            let visibleCount = 0;

            cards.forEach(card => {
                const visible =
                    card.dataset.plate.includes(query) ||
                    card.dataset.model.includes(query) ||
                    card.dataset.driver.includes(query) ||
                    card.dataset.route.includes(query);

                card.classList.toggle('hidden', !visible);
                if (visible) visibleCount++;
            });

            if (noResults && grid) {
                noResults.classList.toggle('hidden', visibleCount !== 0);
                grid.classList.toggle('hidden', visibleCount === 0);
            }
        }

        function showRatingModal(driverId, driverName) {
            const modal = document.getElementById('rating-modal');
            document.getElementById('driver-id').value = driverId;
            document.getElementById('driver-name').textContent = driverName;
            document.getElementById('rating-form').action = `/passenger/rate/${driverId}`;
            document.getElementById('rating-value').value = '';

            document.querySelectorAll('.star-btn').forEach(button => {
                button.classList.remove('text-[#efb45e]');
                button.classList.add('text-slate-300');
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeRatingModal() {
            const modal = document.getElementById('rating-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function setRating(rating) {
            document.getElementById('rating-value').value = rating;

            document.querySelectorAll('.star-btn').forEach((button, index) => {
                button.classList.toggle('text-[#efb45e]', index < rating);
                button.classList.toggle('text-slate-300', index >= rating);
            });
        }

        document.getElementById('rating-modal').addEventListener('click', function (event) {
            if (event.target === this) closeRatingModal();
        });

        // ===== Detalles del autobús (Requisito 4) =====
        const busDetails = @json($busDetailsData);

        const BUS_STATUS_STYLES = {
            active: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
            inactive: 'bg-slate-100 text-slate-500 ring-slate-200',
            maintenance: 'bg-amber-50 text-amber-600 ring-amber-100',
            emergency: 'bg-rose-50 text-rose-600 ring-rose-100',
        };
        const BUS_STATUS_DOTS = {
            active: 'bg-emerald-500',
            inactive: 'bg-slate-400',
            maintenance: 'bg-amber-500',
            emergency: 'bg-rose-500',
        };
        const BUS_STATUS_LABELS = {
            active: 'En servicio',
            inactive: 'Inactivo',
            maintenance: 'En mantenimiento',
            emergency: 'Emergencia',
        };

        function showBusDetails(busId) {
            const bus = busDetails.find(b => b.id === busId);
            if (!bus) return;

            document.getElementById('bd-icon').style.backgroundColor = bus.route_color;
            document.getElementById('bd-plate').textContent = 'Unidad ' + bus.plate;
            document.getElementById('bd-model').textContent = bus.model;
            document.getElementById('bd-route').textContent = bus.route;
            document.getElementById('bd-route-color').style.backgroundColor = bus.route_color;
            document.getElementById('bd-route-code').textContent = bus.route_code ? 'Código ' + bus.route_code : '';
            document.getElementById('bd-capacity').textContent = bus.capacity + ' pasajeros';
            document.getElementById('bd-passengers').textContent =
                bus.passengers + (bus.passengers === 1 ? ' pasajero' : ' pasajeros');

            const seats = Number.isFinite(bus.available_seats)
                ? bus.available_seats
                : Math.max(0, bus.capacity - bus.passengers);
            document.getElementById('bd-seats').textContent =
                seats + (seats === 1 ? ' asiento libre' : ' asientos libres');

            const occupancy = bus.capacity > 0 ? Math.round((bus.passengers / bus.capacity) * 100) : 0;
            const occupancyBar = document.getElementById('bd-occupancy-bar');
            occupancyBar.style.width = Math.min(100, occupancy) + '%';
            occupancyBar.className = 'h-full rounded-full transition-all '
                + (occupancy >= 90 ? 'bg-rose-500' : (occupancy >= 60 ? 'bg-amber-500' : 'bg-emerald-500'));

            document.getElementById('bd-driver').textContent = bus.driver;
            document.getElementById('bd-update').textContent = bus.last_update;
            document.getElementById('bd-map-link').href = bus.map_url;

            const statusBadge = document.getElementById('bd-status');
            statusBadge.className = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 '
                + (BUS_STATUS_STYLES[bus.status] || BUS_STATUS_STYLES.inactive);
            statusBadge.innerHTML = '<span class="h-1.5 w-1.5 rounded-full '
                + (BUS_STATUS_DOTS[bus.status] || 'bg-slate-400') + '"></span> '
                + (BUS_STATUS_LABELS[bus.status] || 'Desconocido');

            const modal = document.getElementById('bus-details-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeBusDetails() {
            const modal = document.getElementById('bus-details-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('bus-details-modal').addEventListener('click', function (event) {
            if (event.target === this) closeBusDetails();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeBusDetails();
        });
    </script>
</x-passenger-layout>
