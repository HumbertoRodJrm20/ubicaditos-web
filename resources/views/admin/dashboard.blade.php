@php
    $card = 'bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-white/80 dark:border-white/10 shadow-[0_20px_50px_-20px_rgba(60,70,110,0.35)] rounded-[34px]';
    $tile = 'flex items-center gap-3 rounded-[22px] bg-white/85 dark:bg-gray-700/60 px-3 py-[10px] hover:bg-white transition';
    $tileIcon = 'w-11 h-11 rounded-[14px] text-white flex items-center justify-center shrink-0 shadow-md';
    $statRow = 'flex items-center justify-between rounded-[18px] bg-white/85 dark:bg-gray-700/60 px-3 py-3';
    $userPlus = '<svg class="w-11 h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>';
    $chevron = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"></path></svg>';
    $busSvg = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>';
    $mapSvg = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>';
    $usersSvg = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
    $peopleSvg = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>';
    $idSvg = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"></rect><circle cx="9" cy="11" r="2"></circle><path d="M6 16c.5-1.5 1.8-2 3-2s2.5.5 3 2M14 10h4M14 13h3"></path></svg>';
    $clockSvg = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
@endphp

<x-passenger-layout title="Ubicaditos · Inicio">
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin="">
    </x-slot>

    <style>
        #admin-map { background: #e8edf8; }
        #admin-map .leaflet-tile-pane { filter: saturate(.3) brightness(1.08) contrast(.88); opacity: .8; }
        .leaflet-popup-content-wrapper { border-radius: 16px; box-shadow: 0 12px 30px rgba(60, 70, 110, .25); }
    </style>

    <div>
        <h1 class="text-center text-[36px] font-bold text-[#1e2a4a] dark:text-gray-100 leading-tight mb-5">Gestión de Usuarios</h1>

        <div class="grid gap-5 md:grid-cols-3">
            <a href="{{ route('admin.users.create') }}?role=driver" class="relative flex flex-col justify-between h-[206px] rounded-[34px] p-7 bg-gradient-to-br from-[#8f9bcf] via-[#6f7db3] to-[#4a578a] shadow-xl text-white hover:brightness-105 transition">
                <span class="w-[84px] h-[84px] rounded-[22px] bg-white/20 flex items-center justify-center">{!! $userPlus !!}</span>
                <div>
                    <h2 class="text-[23px] font-bold leading-tight">Agregar conductor</h2>
                    <p class="text-[12px] text-white/70">Crear nuevo conductor</p>
                </div>
                <span class="absolute right-7 bottom-7 w-9 h-9 rounded-full bg-white text-[#3f4c75] flex items-center justify-center">{!! $chevron !!}</span>
            </a>

            <a href="{{ route('admin.users.create') }}?role=passenger" class="relative flex flex-col justify-between h-[206px] rounded-[34px] p-7 bg-gradient-to-br from-[#b9cde6] via-[#93a7d2] to-[#6f82b6] shadow-xl text-white hover:brightness-105 transition">
                <span class="w-[84px] h-[84px] rounded-[22px] bg-white/20 flex items-center justify-center">{!! $userPlus !!}</span>
                <div>
                    <h2 class="text-[23px] font-bold leading-tight">Agregar pasajero</h2>
                    <p class="text-[12px] text-white/70">Crear nuevo pasajero</p>
                </div>
                <span class="absolute right-7 bottom-7 w-9 h-9 rounded-full bg-white text-[#3f4c75] flex items-center justify-center">{!! $chevron !!}</span>
            </a>

            <a href="{{ route('admin.users.create') }}?role=admin" class="relative flex flex-col justify-between h-[206px] rounded-[34px] p-7 bg-gradient-to-br from-[#7d8ab9] via-[#4a5686] to-[#1f2646] shadow-xl text-white hover:brightness-105 transition">
                <span class="w-[84px] h-[84px] rounded-[22px] bg-white/20 flex items-center justify-center">{!! $userPlus !!}</span>
                <div>
                    <h2 class="text-[23px] font-bold leading-tight">Agregar administrador</h2>
                    <p class="text-[12px] text-white/70">Crear nuevo administrador</p>
                </div>
                <span class="absolute right-7 bottom-7 w-9 h-9 rounded-full bg-white text-[#3f4c75] flex items-center justify-center">{!! $chevron !!}</span>
            </a>
        </div>

        <div class="{{ $card }} p-6 mt-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('admin.drivers') }}" class="{{ $tile }}">
                    <span class="{{ $tileIcon }} bg-gradient-to-br from-[#8f9bcf] to-[#4f5c8f]">{!! $idSvg !!}</span>
                    <span class="text-[14px] font-semibold text-[#1e2a4a] dark:text-gray-100">Gestionar conductores</span>
                </a>
                <a href="{{ route('admin.passengers') }}" class="{{ $tile }}">
                    <span class="{{ $tileIcon }} bg-gradient-to-br from-[#9bd6d8] to-[#5fa9b4]">{!! $peopleSvg !!}</span>
                    <span class="text-[14px] font-semibold text-[#1e2a4a] dark:text-gray-100">Gestionar pasajeros</span>
                </a>
                <a href="{{ route('admin.buses.index') }}" class="{{ $tile }}">
                    <span class="{{ $tileIcon }} bg-gradient-to-br from-[#aab9e6] to-[#6d7fb8]">{!! $busSvg !!}</span>
                    <span class="text-[14px] font-semibold text-[#1e2a4a] dark:text-gray-100">Gestionar autobuses</span>
                </a>
                <a href="{{ route('admin.routes.index') }}" class="{{ $tile }}">
                    <span class="{{ $tileIcon }} bg-gradient-to-br from-[#a8d8d6] to-[#6fb3b8]">{!! $mapSvg !!}</span>
                    <span class="text-[14px] font-semibold text-[#1e2a4a] dark:text-gray-100">Gestionar rutas</span>
                </a>
            </div>
        </div>

        <div class="relative z-10 -mt-3 flex justify-center">
            <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-2 h-[45px] px-8 rounded-full bg-gradient-to-r from-[#6c7aa8] to-[#3f4c75] text-white text-[14px] font-medium shadow-lg shadow-[#3f4c75]/40 hover:brightness-110 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Reportes y Estadísticas
            </a>
        </div>

        <div class="{{ $card }} p-6 mt-5 space-y-3">
            <div class="{{ $statRow }}">
                <div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">Autobuses Totales</div>
                    <div class="text-[17px] font-bold text-[#1e2a4a] dark:text-gray-100 leading-tight">{{ $stats['total_buses'] }}</div>
                    <div class="text-[12px] text-emerald-600">{{ $stats['active_buses'] }} activos</div>
                </div>
                <span class="text-[#4b5a85] mr-2 [&>svg]:w-9 [&>svg]:h-9">{!! $busSvg !!}</span>
            </div>

            <div class="{{ $statRow }}">
                <div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">Rutas</div>
                    <div class="text-[17px] font-bold text-[#1e2a4a] dark:text-gray-100 leading-tight">{{ $stats['total_routes'] }}</div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">{{ $stats['total_stops'] }} paradas</div>
                </div>
                <span class="text-[#8fcfcf] mr-2 [&>svg]:w-9 [&>svg]:h-9">{!! $mapSvg !!}</span>
            </div>

            <div class="{{ $statRow }}">
                <div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">Conductores</div>
                    <div class="text-[17px] font-bold text-[#1e2a4a] dark:text-gray-100 leading-tight">{{ $stats['total_drivers'] }}</div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">{{ $stats['total_passengers'] }} pasajeros</div>
                </div>
                <span class="text-[#8fa3d6] mr-2 [&>svg]:w-9 [&>svg]:h-9">{!! $usersSvg !!}</span>
            </div>

            <div class="{{ $statRow }}">
                <div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">Viajes Hoy</div>
                    <div class="text-[17px] font-bold text-[#1e2a4a] dark:text-gray-100 leading-tight">{{ $stats['trips_today'] }}</div>
                    <div class="text-[12px] text-slate-500 dark:text-gray-400">Total de viajes registrados hoy</div>
                </div>
                <span class="text-[#8fcfcf] mr-2 [&>svg]:w-9 [&>svg]:h-9">{!! $clockSvg !!}</span>
            </div>
        </div>

        <div class="{{ $card }} p-6 mt-5">
            <h3 class="text-[16px] font-semibold text-[#2f3a5f] dark:text-gray-100 mb-4">Autobuses Activos</h3>
            @if($activeBuses->count() > 0)
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($activeBuses as $bus)
                        <div class="rounded-[22px] bg-white/85 dark:bg-gray-700/60 p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="text-[14px] font-semibold text-[#1e2a4a] dark:text-gray-100">{{ $bus->license_plate }}</div>
                                    <div class="text-[12px] text-slate-500 dark:text-gray-400">{{ $bus->model }}</div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 rounded-full text-[10px] leading-5 bg-[#dcf3e5] text-emerald-700">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                    Activo
                                </span>
                            </div>
                            <div class="space-y-1 text-[13px]">
                                <div class="flex justify-between"><span class="text-slate-400">Conductor:</span><span class="text-slate-600 dark:text-gray-200">{{ $bus->driver->name ?? 'Sin asignar' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Ruta:</span><span class="text-slate-600 dark:text-gray-200">{{ $bus->routes->first()->name ?? 'Sin ruta' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Pasajeros:</span><span class="text-slate-600 dark:text-gray-200">{{ $bus->current_passengers }} / {{ $bus->capacity }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[13px] text-slate-500 dark:text-gray-400">No hay autobuses activos en este momento.</p>
            @endif
        </div>

        <div class="{{ $card }} p-6 mt-5">
            <h3 class="text-[16px] font-semibold text-[#2f3a5f] dark:text-gray-100 mb-4">Viajes Recientes</h3>
            @if($recentTrips->count() > 0)
                <div class="rounded-[18px] bg-white/85 dark:bg-gray-700/60 px-4 py-2 overflow-x-auto">
                    <table class="min-w-full text-[13px] leading-5">
                        <thead>
                            <tr class="text-left uppercase text-[12px] text-slate-400 border-b border-slate-300/70">
                                <th class="py-2 pr-4 font-normal">Autobús</th>
                                <th class="py-2 pr-4 font-normal">Conductor</th>
                                <th class="py-2 pr-4 font-normal">Ruta</th>
                                <th class="py-2 pr-4 font-normal">Inicio</th>
                                <th class="py-2 font-normal">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-500 dark:text-gray-300">
                            @foreach($recentTrips as $trip)
                                <tr>
                                    <td class="py-1.5 pr-4 whitespace-nowrap">{{ $trip->bus->license_plate }}</td>
                                    <td class="py-1.5 pr-4 whitespace-nowrap">{{ $trip->driver->name }}</td>
                                    <td class="py-1.5 pr-4 whitespace-nowrap">{{ $trip->route->name }}</td>
                                    <td class="py-1.5 pr-4 whitespace-nowrap">{{ $trip->started_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-1.5 whitespace-nowrap">
                                        @if($trip->status === 'in_progress')
                                            <span class="inline-flex items-center gap-1 px-2 rounded-full text-[10px] leading-4 bg-[#fdeeb2] text-amber-800">
                                                <span class="w-1 h-1 rounded-full bg-amber-600"></span>
                                                En progreso
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 rounded-full text-[10px] leading-4 bg-[#dcf3e5] text-emerald-700">
                                                <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                Completado
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-[13px] text-slate-500 dark:text-gray-400">No hay viajes registrados todavía.</p>
            @endif
        </div>

        <div class="{{ $card }} p-6 mt-5">
            <h3 class="text-[16px] font-semibold text-[#2f3a5f] dark:text-gray-100 mb-4">Mapa en Tiempo Real</h3>
            <div class="relative">
                <div id="admin-map" class="w-full h-96 rounded-[24px] overflow-hidden"></div>

                <div id="gps-overlay-admin" class="absolute top-4 right-4 z-[1000] bg-white/85 dark:bg-gray-800/80 backdrop-blur-xl border border-white/70 dark:border-white/10 rounded-2xl shadow-lg px-4 py-2 max-w-xs">
                    <div class="text-sm space-y-1">
                        <div class="flex items-center">
                            <span id="gps-indicator-admin" class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                            <span id="gps-status-text-admin" class="text-[12px] font-medium text-slate-600 dark:text-gray-300">GPS Inactivo</span>
                        </div>
                        <div id="gps-details-admin" class="hidden space-y-0.5">
                            <div class="text-[11px] text-slate-500 dark:text-gray-400">
                                Última actualización: <span id="last-update-admin">--</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-gray-400">
                                Precisión: <span id="location-accuracy-admin">--</span>m
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <script>
        const adminMap = L.map('admin-map').setView([17.0654, -96.7256], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(adminMap);

        let busMarkers = {};
        let stopMarkers = {};
        let routePolylines = {};
        let routePathsOSRM = {};
        let simulationIntervals = {};
        let routesLoaded = {};

        const BUS_SVG = (size = 18) => `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>`;

        const busIcon = L.divIcon({
            html: `<div style="
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6b7aa8, #3f4c75);
                border: 3px solid #fff;
                box-shadow: 0 4px 12px rgba(40,50,90,0.35);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
            ">${BUS_SVG(18)}</div>`,
            className: 'bus-marker',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });

        const stopIcon = L.divIcon({
            html: '<div style="background-color: #fff; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #4b5a85; box-shadow: 0 2px 6px rgba(40,50,90,0.35);"></div>',
            className: 'stop-marker',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
            popupAnchor: [0, -8]
        });

        const userLocationIcon = L.divIcon({
            html: `<div style="
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: rgba(100,110,140,0.28);
                display: flex;
                align-items: center;
                justify-content: center;
            "><div style="
                width: 14px;
                height: 14px;
                border-radius: 50%;
                background: #3f4c75;
                border: 3px solid #fff;
                box-shadow: 0 2px 6px rgba(40,50,90,0.4);
            "></div></div>`,
            className: 'user-location-marker',
            iconSize: [44, 44],
            iconAnchor: [22, 22],
            popupAnchor: [0, -22]
        });

        let userLocationMarker = null;
        let userLocationCircle = null;
        let userLocationWatchId = null;
        let simulatedLocationInterval = null;

        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        const routes = @json($routes);
        const stops = @json($stops);
        const buses = @json($buses);

        console.log('Autobuses cargados:', buses.length);
        console.log('Rutas cargadas:', routes.length);
        console.log('Paradas cargadas:', stops.length);

        let simulatedBuses = [];

        async function drawRoute(route) {
            if (routesLoaded[route.id]) {
                return;
            }

            const routeStops = stops.filter(stop =>
                route.stops && route.stops.some(s => s.id === stop.id)
            ).sort((a, b) => {
                const aOrder = route.stops.find(s => s.id === a.id)?.pivot?.order || 0;
                const bOrder = route.stops.find(s => s.id === b.id)?.pivot?.order || 0;
                return aOrder - bOrder;
            });

            if (routeStops.length < 2) {
                console.warn('Ruta sin suficientes paradas:', route.name);
                return;
            }

            const waypoints = routeStops.map(stop =>
                `${stop.longitude},${stop.latitude}`
            ).join(';');

            for (let attempt = 1; attempt <= 3; attempt++) {
                try {
                    console.log(`[Admin - ${route.name}] Intento ${attempt}/3 de cargar ruta sobre calles...`);

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 15000);

                    const response = await fetch(
                        `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`,
                        { signal: controller.signal }
                    );
                    clearTimeout(timeoutId);
                    const data = await response.json();

                    if (data.code === 'Ok' && data.routes && data.routes[0]) {
                        const pathCoords = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                        routePathsOSRM[route.id] = pathCoords;

                        const polyline = L.polyline(pathCoords, {
                            color: route.color || '#4b5a85',
                            weight: 5,
                            opacity: 0.9,
                            lineCap: 'round',
                            lineJoin: 'round'
                        }).addTo(adminMap);

                        polyline.bindPopup(`
                            <div class="font-semibold">${route.name}</div>
                            <div class="text-sm text-gray-600">${route.code}</div>
                            <div class="text-xs mt-1">${route.description}</div>
                        `);

                        routePolylines[route.id] = polyline;
                        routesLoaded[route.id] = true;
                        console.log(`✅ [Admin - ${route.name}] Ruta cargada sobre calles correctamente`);
                        return;
                    } else {
                        console.warn(`⚠ [Admin - ${route.name}] OSRM retornó: ${data.code}`);
                    }
                } catch (error) {
                    console.warn(`⚠ [Admin - ${route.name}] Error en intento ${attempt}:`, error.message);
                }

                if (attempt < 3) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }

            console.error(`❌ [Admin - ${route.name}] No se pudo cargar sobre calles después de 3 intentos - LA RUTA NO SE MOSTRARÁ`);
        }

        async function loadVisibleRoutes() {
            const routesToLoad = new Set();

            buses.forEach(bus => {
                if (bus.routes && bus.routes[0]) {
                    routesToLoad.add(bus.routes[0].id);
                }
            });

            for (const routeId of routesToLoad) {
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            }
        }

        function initializeSimulatedBuses() {
            simulatedBuses = buses.map((bus, index) => {
                const route = bus.routes && bus.routes[0];
                if (!route) {
                    console.warn('Autobús sin ruta válida:', bus.license_plate);
                    return null;
                }

                const fullRoute = routes.find(r => r.id === route.id);
                if (!fullRoute) {
                    console.warn('Ruta no encontrada:', bus.license_plate);
                    return null;
                }

                const routePath = routePathsOSRM[route.id];
                if (!routePath || routePath.length < 2) {
                    console.warn('Ruta OSRM no disponible aún para:', bus.license_plate);
                    return null;
                }

                const startProgress = (index * 0.3) % 1;
                const pathIndex = Math.floor(routePath.length * startProgress);
                const startPoint = routePath[pathIndex];

                return {
                    id: bus.id,
                    license_plate: bus.license_plate,
                    model: bus.model,
                    capacity: bus.capacity,
                    driver: bus.driver,
                    route: fullRoute,
                    routePath: routePath,
                    currentPosition: {
                        lat: startPoint[0],
                        lng: startPoint[1]
                    },
                    pathIndex: pathIndex,
                    direction: 1
                };
            }).filter(bus => bus !== null);

            console.log('Autobuses simulados inicializados sobre rutas OSRM:', simulatedBuses.length);
        }

        function drawStops() {
            stops.forEach(stop => {
                if (!stopMarkers[stop.id]) {
                    const marker = L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                        .addTo(adminMap)
                        .bindPopup(`
                            <div class="font-semibold">${stop.name}</div>
                            <div class="text-sm text-gray-600">${stop.address || ''}</div>
                        `);
                    stopMarkers[stop.id] = marker;
                }
            });
        }

        function drawBuses() {
            simulatedBuses.forEach(bus => {
                if (busMarkers[bus.id]) {
                    busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
                } else {
                    const marker = L.marker([bus.currentPosition.lat, bus.currentPosition.lng], {
                        icon: busIcon,
                        zIndexOffset: 1000
                    })
                        .addTo(adminMap)
                        .bindPopup(`
                            <div class="font-semibold text-lg">🚌 ${bus.license_plate}</div>
                            <div class="text-sm text-gray-600">${bus.model}</div>
                            <div class="mt-2 text-sm">
                                <div><strong>Conductor:</strong> ${bus.driver?.name || 'N/A'}</div>
                                <div><strong>Ruta:</strong> ${bus.route.name}</div>
                                <div><strong>Capacidad:</strong> ${bus.capacity} pasajeros</div>
                            </div>
                        `);
                    busMarkers[bus.id] = marker;
                    console.log('Autobús dibujado:', bus.license_plate, 'en', bus.currentPosition);
                }
            });
        }

        function moveBus(bus) {
            if (!bus.routePath || bus.routePath.length === 0) return;

            bus.pathIndex += bus.direction * 0.08;

            if (bus.pathIndex >= bus.routePath.length - 1) {
                bus.pathIndex = bus.routePath.length - 1;
                bus.direction = -1;
            } else if (bus.pathIndex <= 0) {
                bus.pathIndex = 0;
                bus.direction = 1;
            }

            const currentIndex = Math.floor(bus.pathIndex);
            const nextIndex = Math.min(currentIndex + 1, bus.routePath.length - 1);
            const progress = bus.pathIndex - currentIndex;

            const currentPoint = bus.routePath[currentIndex];
            const nextPoint = bus.routePath[nextIndex];

            bus.currentPosition = {
                lat: currentPoint[0] + (nextPoint[0] - currentPoint[0]) * progress,
                lng: currentPoint[1] + (nextPoint[1] - currentPoint[1]) * progress
            };

            if (busMarkers[bus.id]) {
                busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
            }
        }

        function startSimulation() {
            simulatedBuses.forEach(bus => {
                simulationIntervals[bus.id] = setInterval(() => {
                    moveBus(bus);
                }, 200);
            });

            console.log('✅ Simulación iniciada con', simulatedBuses.length, 'autobuses');
        }

        function updateAdminLocationMarker(lat, lng, accuracy) {
            const displayAccuracy = Math.min(Math.round(accuracy), 100);
            const circleRadius = Math.min(accuracy, 100);

            if (userLocationMarker) {
                adminMap.removeLayer(userLocationMarker);
            }
            if (userLocationCircle) {
                adminMap.removeLayer(userLocationCircle);
            }

            userLocationMarker = L.marker([lat, lng], {
                icon: userLocationIcon,
                zIndexOffset: 2000
            }).addTo(adminMap).bindPopup(`
                <div class="font-semibold text-blue-600">📍 Mi ubicación</div>
                <div class="text-xs text-gray-600 mt-1">Lat: ${lat.toFixed(6)}</div>
                <div class="text-xs text-gray-600">Lng: ${lng.toFixed(6)}</div>
                <div class="text-xs text-gray-500 mt-1">Precisión: ${displayAccuracy}m</div>
            `);

            userLocationCircle = L.circle([lat, lng], {
                color: '#4b5a85',
                fillColor: '#6b7aa8',
                fillOpacity: 0.1,
                weight: 1,
                radius: circleRadius
            }).addTo(adminMap);
        }

        function useSimulatedLocationAdmin() {
            console.log('🎯 Usando ubicación GPS simulada para admin:', SIMULATED_LOCATION);

            const gpsIndicator = document.getElementById('gps-indicator-admin');
            const gpsStatusText = document.getElementById('gps-status-text-admin');
            const gpsDetails = document.getElementById('gps-details-admin');

            if (gpsIndicator) {
                gpsIndicator.className = 'w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse';
            }
            if (gpsStatusText) {
                gpsStatusText.textContent = 'GPS Simulado';
            }
            if (gpsDetails) {
                gpsDetails.classList.remove('hidden');
            }

            function updateSimulatedLocation() {
                const lastUpdate = document.getElementById('last-update-admin');
                const accuracyElement = document.getElementById('location-accuracy-admin');

                if (lastUpdate) {
                    lastUpdate.textContent = new Date().toLocaleTimeString();
                }
                if (accuracyElement) {
                    accuracyElement.textContent = SIMULATED_LOCATION.accuracy;
                }

                updateAdminLocationMarker(SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng, SIMULATED_LOCATION.accuracy);
            }

            updateSimulatedLocation();

            simulatedLocationInterval = setInterval(updateSimulatedLocation, 2000);

            console.log('✅ Ubicación GPS simulada activa para admin');
        }

        function getAdminLocation() {
            if (!navigator.geolocation) {
                console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                useSimulatedLocationAdmin();
                return;
            }

            console.log('🔍 Iniciando rastreo de ubicación del administrador...');

            userLocationWatchId = navigator.geolocation.watchPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    console.log('✓ Ubicación del admin actualizada:', {
                        lat: lat,
                        lng: lng,
                        accuracy: accuracy
                    });

                    const gpsIndicator = document.getElementById('gps-indicator-admin');
                    const gpsStatusText = document.getElementById('gps-status-text-admin');
                    const gpsDetails = document.getElementById('gps-details-admin');
                    const lastUpdate = document.getElementById('last-update-admin');
                    const accuracyElement = document.getElementById('location-accuracy-admin');

                    if (gpsIndicator) {
                        gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse';
                    }
                    if (gpsStatusText) {
                        gpsStatusText.textContent = 'GPS Activo';
                    }
                    if (gpsDetails) {
                        gpsDetails.classList.remove('hidden');
                    }
                    if (lastUpdate) {
                        lastUpdate.textContent = new Date().toLocaleTimeString();
                    }
                    if (accuracyElement) {
                        const displayAccuracy = Math.min(Math.round(accuracy), 100);
                        accuracyElement.textContent = displayAccuracy;
                    }

                    updateAdminLocationMarker(lat, lng, accuracy);
                },
                function(error) {
                    console.warn('⚠ Error al obtener ubicación GPS del admin:', {
                        code: error.code,
                        message: error.message
                    });

                    console.log('🔄 Cambiando a ubicación GPS simulada para admin...');
                    useSimulatedLocationAdmin();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 2000
                }
            );
        }

        window.addEventListener('beforeunload', function() {
            if (userLocationWatchId) {
                navigator.geolocation.clearWatch(userLocationWatchId);
                console.log('✓ Rastreo de ubicación detenido');
            }
            if (simulatedLocationInterval) {
                clearInterval(simulatedLocationInterval);
                console.log('✓ Ubicación simulada detenida');
            }
        });

        console.log('🚀 Inicializando mapa de administrador...');

        (async function init() {
            drawStops();

            await loadVisibleRoutes();

            initializeSimulatedBuses();
            drawBuses();
            startSimulation();

            getAdminLocation();

            console.log('✅ Mapa de administrador listo y en funcionamiento!');
        })();
    </script>
</x-passenger-layout>