<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-700 dark:text-gray-200 leading-tight">
            {{ __('Panel de Administración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Gestión de Usuarios -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Gestión de Usuarios</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Agregar Conductor -->
                        <a href="{{ route('admin.users.create') }}?role=driver" class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex flex-col items-center text-center">
                                <svg class="w-12 h-12 text-white mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <h4 class="text-xl font-bold text-white mb-1">Agregar Conductor</h4>
                                <p class="text-white/80 text-sm">Crear nuevo conductor</p>
                            </div>
                        </a>

                        <!-- Agregar Pasajero -->
                        <a href="{{ route('admin.users.create') }}?role=passenger" class="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex flex-col items-center text-center">
                                <svg class="w-12 h-12 text-white mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <h4 class="text-xl font-bold text-white mb-1">Agregar Pasajero</h4>
                                <p class="text-white/80 text-sm">Crear nuevo pasajero</p>
                            </div>
                        </a>

                        <!-- Agregar Administrador -->
                        <a href="{{ route('admin.users.create') }}?role=admin" class="bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex flex-col items-center text-center">
                                <svg class="w-12 h-12 text-white mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <h4 class="text-xl font-bold text-white mb-1">Agregar Administrador</h4>
                                <p class="text-white/80 text-sm">Crear nuevo administrador</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enlaces Rápidos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <a href="{{ route('admin.drivers') }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-accent-500">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-accent-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="font-semibold text-gray-700 dark:text-gray-100">Gestionar Conductores</span>
                    </div>
                </a>

                <a href="{{ route('admin.passengers') }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-accent-500">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-accent-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="font-semibold text-gray-700 dark:text-gray-100">Gestionar Pasajeros</span>
                    </div>
                </a>

                <a href="{{ route('admin.buses.index') }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-primary-500">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span class="font-semibold text-gray-700 dark:text-gray-100">Gestionar Autobuses</span>
                    </div>
                </a>

                <a href="{{ route('admin.routes.index') }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-success-500">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-success-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        <span class="font-semibold text-gray-700 dark:text-gray-100">Gestionar Rutas</span>
                    </div>
                </a>
            </div>

            <!-- Enlace a Reportes -->
            <div class="mb-6">
                <a href="{{ route('admin.reports') }}" class="block bg-gradient-to-r from-primary-500 to-accent-500 dark:from-primary-700 dark:to-accent-700 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-10 h-10 text-white mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <div>
                                <h3 class="text-xl font-bold text-white">Reportes y Estadísticas</h3>
                                <p class="text-white/80 text-sm">Ver gráficas y exportar datos</p>
                            </div>
                        </div>
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Autobuses Totales</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_buses'] }}</div>
                            </div>
                            <div class="ml-4">
                                <svg class="w-12 h-12 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-success-600 dark:text-success-400">
                            {{ $stats['active_buses'] }} activos
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Rutas</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_routes'] }}</div>
                            </div>
                            <div class="ml-4">
                                <svg class="w-12 h-12 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $stats['total_stops'] }} paradas
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Conductores</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_drivers'] }}</div>
                            </div>
                            <div class="ml-4">
                                <svg class="w-12 h-12 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $stats['total_passengers'] }} pasajeros
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Viajes Hoy</div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['trips_today'] }}</div>
                            </div>
                            <div class="ml-4">
                                <svg class="w-12 h-12 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Total de viajes registrados hoy
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autobuses Activos -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Autobuses Activos</h3>
                    @if($activeBuses->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($activeBuses as $bus)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <div class="font-semibold text-gray-700 dark:text-gray-100">{{ $bus->license_plate }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $bus->model }}</div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                            Activo
                                        </span>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Conductor:</span>
                                            <span class="text-gray-700 dark:text-gray-100">{{ $bus->driver->name ?? 'Sin asignar' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Ruta:</span>
                                            <span class="text-gray-700 dark:text-gray-100">{{ $bus->routes->first()->name ?? 'Sin ruta' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Pasajeros:</span>
                                            <span class="text-gray-700 dark:text-gray-100">{{ $bus->current_passengers }} / {{ $bus->capacity }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No hay autobuses activos en este momento.</p>
                    @endif
                </div>
            </div>

            <!-- Viajes Recientes -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Viajes Recientes</h3>
                    @if($recentTrips->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Autobús</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conductor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ruta</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inicio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentTrips as $trip)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $trip->bus->license_plate }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $trip->driver->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-100">
                                                {{ $trip->route->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $trip->started_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($trip->status === 'in_progress')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200">
                                                        En progreso
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
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
                        <p class="text-gray-500 dark:text-gray-400">No hay viajes registrados todavía.</p>
                    @endif
                </div>
            </div>

            <!-- Mapa en Tiempo Real -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100 mb-4">Mapa en Tiempo Real</h3>
                    <div id="admin-map" class="w-full h-96 rounded-lg relative">
                        <!-- Overlay de información GPS -->
                        <div id="gps-overlay-admin" class="absolute top-4 right-4 z-[1000] bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 max-w-xs">
                            <div class="text-sm space-y-1">
                                <div class="flex items-center">
                                    <span id="gps-indicator-admin" class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                    <span id="gps-status-text-admin" class="text-gray-700 dark:text-gray-300">GPS Inactivo</span>
                                </div>
                                <div id="gps-details-admin" class="hidden space-y-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Última actualización: <span id="last-update-admin">--</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Precisión: <span id="location-accuracy-admin">--</span>m
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Inicializar mapa
        const adminMap = L.map('admin-map').setView([17.0654, -96.7256], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(adminMap);

        // Variables globales
        let busMarkers = {};
        let stopMarkers = {};
        let routePolylines = {};
        let routePathsOSRM = {}; // Guardar las rutas OSRM reales
        let simulationIntervals = {};
        let routesLoaded = {}; // Track which routes have been loaded

        // Icono de autobús mejorado
        const busIcon = L.divIcon({
            html: `<div style="
                font-size: 32px;
                line-height: 1;
                text-shadow:
                    -2px -2px 0 #fff,
                    2px -2px 0 #fff,
                    -2px 2px 0 #fff,
                    2px 2px 0 #fff,
                    0 0 8px rgba(0,0,0,0.5);
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            ">🚌</div>`,
            className: 'bus-marker',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

        // Icono de parada
        const stopIcon = L.divIcon({
            html: '<div style="background-color: #dc2626; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4);"></div>',
            className: 'stop-marker',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            popupAnchor: [0, -10]
        });

        // Icono de ubicación del admin
        const userLocationIcon = L.divIcon({
            html: `<div style="
                font-size: 32px;
                line-height: 1;
                text-shadow:
                    -2px -2px 0 #fff,
                    2px -2px 0 #fff,
                    -2px 2px 0 #fff,
                    2px 2px 0 #fff,
                    0 0 8px rgba(0,0,0,0.5);
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            ">📍</div>`,
            className: 'user-location-marker',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        let userLocationMarker = null;
        let userLocationCircle = null;
        let userLocationWatchId = null;
        let simulatedLocationInterval = null;

        // Ubicación simulada (Oaxaca, México)
        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        // Datos
        const routes = @json($routes);
        const stops = @json($stops);
        const buses = @json($buses);

        console.log('Autobuses cargados:', buses.length);
        console.log('Rutas cargadas:', routes.length);
        console.log('Paradas cargadas:', stops.length);

        // Simulated bus data
        let simulatedBuses = [];

        // Dibujar una ruta específica usando OSRM
        async function drawRoute(route) {
            // Si ya está cargada, no volver a cargar
            if (routesLoaded[route.id]) {
                return;
            }

            // Obtener las paradas de esta ruta
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

            // Crear waypoints para OSRM
            const waypoints = routeStops.map(stop =>
                `${stop.longitude},${stop.latitude}`
            ).join(';');

            try {
                // Consultar OSRM para obtener ruta que sigue las calles
                const response = await fetch(
                    `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`
                );
                const data = await response.json();

                if (data.code === 'Ok' && data.routes && data.routes[0]) {
                    // Convertir coordenadas de OSRM a formato Leaflet [lat, lng]
                    const pathCoords = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                    // Guardar las coordenadas OSRM para que los autobuses las usen
                    routePathsOSRM[route.id] = pathCoords;

                    const polyline = L.polyline(pathCoords, {
                        color: route.color || '#8b5cf6',
                        weight: 4,
                        opacity: 0.7
                    }).addTo(adminMap);

                    polyline.bindPopup(`
                        <div class="font-semibold">${route.name}</div>
                        <div class="text-sm text-gray-600">${route.code}</div>
                        <div class="text-xs mt-1">${route.description}</div>
                    `);

                    routePolylines[route.id] = polyline;
                    routesLoaded[route.id] = true;
                    console.log('✓ Ruta cargada:', route.name);
                } else {
                    console.error('Error OSRM para ruta:', route.name, data);
                    drawRouteFallback(route, routeStops);
                }
            } catch (error) {
                console.error('Error al consultar OSRM para ruta:', route.name, error);
                drawRouteFallback(route, routeStops);
            }
        }

        // Cargar solo las rutas de los autobuses visibles
        async function loadVisibleRoutes() {
            const routesToLoad = new Set();

            // Obtener las rutas de los autobuses que están actualmente visibles
            buses.forEach(bus => {
                if (bus.routes && bus.routes[0]) {
                    routesToLoad.add(bus.routes[0].id);
                }
            });

            // Cargar solo esas rutas
            for (const routeId of routesToLoad) {
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            }
        }

        // Fallback: dibujar ruta con líneas rectas si OSRM falla
        function drawRouteFallback(route, routeStops) {
            const pathCoords = routeStops.map(stop => [stop.latitude, stop.longitude]);

            // Guardar coordenadas fallback para autobuses
            routePathsOSRM[route.id] = pathCoords;

            const polyline = L.polyline(pathCoords, {
                color: route.color || '#8b5cf6',
                weight: 4,
                opacity: 0.5,
                dashArray: '5, 10' // Línea punteada para indicar que no sigue calles
            }).addTo(adminMap);

            polyline.bindPopup(`
                <div class="font-semibold">${route.name}</div>
                <div class="text-sm text-gray-600">${route.code}</div>
                <div class="text-xs mt-1">${route.description}</div>
                <div class="text-xs text-red-600 mt-1">⚠ Ruta aproximada</div>
            `);

            routePolylines[route.id] = polyline;
            routesLoaded[route.id] = true;
        }

        // Inicializar autobuses simulados usando las rutas OSRM reales
        function initializeSimulatedBuses() {
            simulatedBuses = buses.map((bus, index) => {
                const route = bus.routes && bus.routes[0];
                if (!route) {
                    console.warn('Autobús sin ruta válida:', bus.license_plate);
                    return null;
                }

                // Buscar la ruta completa
                const fullRoute = routes.find(r => r.id === route.id);
                if (!fullRoute) {
                    console.warn('Ruta no encontrada:', bus.license_plate);
                    return null;
                }

                // Usar las coordenadas OSRM reales si están disponibles
                const routePath = routePathsOSRM[route.id];
                if (!routePath || routePath.length < 2) {
                    console.warn('Ruta OSRM no disponible aún para:', bus.license_plate);
                    return null;
                }

                // Iniciar cada autobús en una posición diferente de la ruta OSRM
                const startProgress = (index * 0.3) % 1; // Distribuir autobuses
                const pathIndex = Math.floor(routePath.length * startProgress);
                const startPoint = routePath[pathIndex];

                return {
                    id: bus.id,
                    license_plate: bus.license_plate,
                    model: bus.model,
                    capacity: bus.capacity,
                    driver: bus.driver,
                    route: fullRoute,
                    routePath: routePath, // Usar ruta OSRM real
                    currentPosition: {
                        lat: startPoint[0],
                        lng: startPoint[1]
                    },
                    pathIndex: pathIndex,
                    direction: 1 // 1 = forward, -1 = backward
                };
            }).filter(bus => bus !== null);

            console.log('Autobuses simulados inicializados sobre rutas OSRM:', simulatedBuses.length);
        }

        // Dibujar paradas
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

        // Dibujar autobuses
        function drawBuses() {
            simulatedBuses.forEach(bus => {
                if (busMarkers[bus.id]) {
                    busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
                } else {
                    const marker = L.marker([bus.currentPosition.lat, bus.currentPosition.lng], {
                        icon: busIcon,
                        zIndexOffset: 1000 // Asegurar que los autobuses estén encima
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

        // Mover autobús a lo largo de su ruta
        function moveBus(bus) {
            if (!bus.routePath || bus.routePath.length === 0) return;

            // Incrementar índice según dirección
            bus.pathIndex += bus.direction * 0.08; // Velocidad de movimiento

            // Cambiar dirección al llegar a los extremos
            if (bus.pathIndex >= bus.routePath.length - 1) {
                bus.pathIndex = bus.routePath.length - 1;
                bus.direction = -1; // Ir hacia atrás
            } else if (bus.pathIndex <= 0) {
                bus.pathIndex = 0;
                bus.direction = 1; // Ir hacia adelante
            }

            // Interpolación entre puntos para movimiento suave
            const currentIndex = Math.floor(bus.pathIndex);
            const nextIndex = Math.min(currentIndex + 1, bus.routePath.length - 1);
            const progress = bus.pathIndex - currentIndex;

            const currentPoint = bus.routePath[currentIndex];
            const nextPoint = bus.routePath[nextIndex];

            bus.currentPosition = {
                lat: currentPoint[0] + (nextPoint[0] - currentPoint[0]) * progress,
                lng: currentPoint[1] + (nextPoint[1] - currentPoint[1]) * progress
            };

            // Actualizar posición del marcador
            if (busMarkers[bus.id]) {
                busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
            }
        }

        // Iniciar simulación automáticamente
        function startSimulation() {
            simulatedBuses.forEach(bus => {
                simulationIntervals[bus.id] = setInterval(() => {
                    moveBus(bus);
                }, 200); // Actualizar cada 200ms
            });

            console.log('✅ Simulación iniciada con', simulatedBuses.length, 'autobuses');
        }

        // Función para actualizar el marcador de ubicación del admin
        function updateAdminLocationMarker(lat, lng, accuracy) {
            // Limitar la precisión a valores razonables (máximo 100m)
            const displayAccuracy = Math.min(Math.round(accuracy), 100);
            const circleRadius = Math.min(accuracy, 100);

            // Eliminar marcador anterior si existe
            if (userLocationMarker) {
                adminMap.removeLayer(userLocationMarker);
            }
            if (userLocationCircle) {
                adminMap.removeLayer(userLocationCircle);
            }

            // Agregar marcador de ubicación del admin
            userLocationMarker = L.marker([lat, lng], {
                icon: userLocationIcon,
                zIndexOffset: 2000
            }).addTo(adminMap).bindPopup(`
                <div class="font-semibold text-blue-600">📍 Mi ubicación</div>
                <div class="text-xs text-gray-600 mt-1">Lat: ${lat.toFixed(6)}</div>
                <div class="text-xs text-gray-600">Lng: ${lng.toFixed(6)}</div>
                <div class="text-xs text-gray-500 mt-1">Precisión: ${displayAccuracy}m</div>
            `);

            // Agregar círculo de precisión con radio limitado
            userLocationCircle = L.circle([lat, lng], {
                color: '#3b82f6',
                fillColor: '#60a5fa',
                fillOpacity: 0.1,
                radius: circleRadius
            }).addTo(adminMap);
        }

        // Función para usar ubicación simulada
        function useSimulatedLocationAdmin() {
            console.log('🎯 Usando ubicación GPS simulada para admin:', SIMULATED_LOCATION);

            // Actualizar indicadores GPS
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

            // Función para actualizar la ubicación simulada
            function updateSimulatedLocation() {
                const lastUpdate = document.getElementById('last-update-admin');
                const accuracyElement = document.getElementById('location-accuracy-admin');

                if (lastUpdate) {
                    lastUpdate.textContent = new Date().toLocaleTimeString();
                }
                if (accuracyElement) {
                    accuracyElement.textContent = SIMULATED_LOCATION.accuracy;
                }

                // Actualizar marcador
                updateAdminLocationMarker(SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng, SIMULATED_LOCATION.accuracy);
            }

            // Actualizar inmediatamente
            updateSimulatedLocation();

            // Actualizar cada 2 segundos
            simulatedLocationInterval = setInterval(updateSimulatedLocation, 2000);

            console.log('✅ Ubicación GPS simulada activa para admin');
        }

        // Función para obtener y actualizar continuamente la ubicación del admin
        function getAdminLocation() {
            if (!navigator.geolocation) {
                console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                useSimulatedLocationAdmin();
                return;
            }

            console.log('🔍 Iniciando rastreo de ubicación del administrador...');

            // Usar watchPosition para actualizaciones continuas
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

                    // Actualizar indicador GPS
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
                        // Mostrar precisión limitada a máximo 100m
                        const displayAccuracy = Math.min(Math.round(accuracy), 100);
                        accuracyElement.textContent = displayAccuracy;
                    }

                    // Actualizar marcador de ubicación
                    updateAdminLocationMarker(lat, lng, accuracy);
                },
                function(error) {
                    console.warn('⚠ Error al obtener ubicación GPS del admin:', {
                        code: error.code,
                        message: error.message
                    });

                    // Usar ubicación simulada cuando falla el GPS
                    console.log('🔄 Cambiando a ubicación GPS simulada para admin...');
                    useSimulatedLocationAdmin();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 2000  // Forzar actualización cada 2 segundos
                }
            );
        }

        // Limpiar watch al salir de la página
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

        // Inicializar todo
        console.log('🚀 Inicializando mapa de administrador...');

        // Inicialización optimizada
        (async function init() {
            drawStops();

            // Cargar solo las rutas necesarias (de los autobuses activos)
            await loadVisibleRoutes();

            initializeSimulatedBuses();
            drawBuses();
            startSimulation();

            // Iniciar rastreo de ubicación del admin
            getAdminLocation();

            console.log('✅ Mapa de administrador listo y en funcionamiento!');
        })();
    </script>
</x-app-layout>
