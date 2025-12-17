<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mapa de Autobuses en Tiempo Real') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Filtrar por ruta:</label>
                            <select id="route-filter" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm">
                                <option value="">Todas las rutas</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="show-stops" checked class="mr-2">
                            <label for="show-stops" class="text-sm text-gray-700 dark:text-gray-300">Mostrar paradas</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="show-nearest-routes" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="show-nearest-routes" class="ml-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Mostrar solo rutas cercanas
                            </label>
                        </div>

                        <div class="ml-auto flex items-center">
                            <div class="flex items-center space-x-2 px-3 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">{{ $buses->count() }} autobuses en servicio</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div id="map" class="w-full h-96 lg:h-[600px] relative">
                    <!-- Overlay de información GPS -->
                    <div id="gps-overlay" class="absolute top-4 right-4 z-[1000] bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 max-w-xs">
                        <div class="text-sm space-y-1">
                            <div class="flex items-center">
                                <span id="gps-indicator" class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                <span id="gps-status-text" class="text-gray-700 dark:text-gray-300">GPS Inactivo</span>
                            </div>
                            <div id="gps-details" class="hidden space-y-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Última actualización: <span id="last-update-passenger">--</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Precisión: <span id="location-accuracy">--</span>m
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Rutas Cercanas -->
            <div id="nearest-routes-container" class="mt-4 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hidden">
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Rutas más cercanas a tu ubicación
                    </h3>
                    <div id="nearest-routes-list" class="space-y-2">
                        <!-- Las rutas cercanas se mostrarán aquí dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Leyenda y Tiempos de Llegada -->
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Leyenda -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Leyenda</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center">
                                <div class="text-2xl mr-2">🚌</div>
                                <span class="text-gray-700 dark:text-gray-300">Autobús</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-600 rounded-full mr-2"></div>
                                <span class="text-gray-700 dark:text-gray-300">Parada</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-purple-600 rounded-full mr-2"></div>
                                <span class="text-gray-700 dark:text-gray-300">Ruta del autobús</span>
                            </div>
                            <div class="flex items-center">
                                <div class="text-2xl mr-2">📍</div>
                                <span class="text-gray-700 dark:text-gray-300">Tu ubicación</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tiempos de Llegada -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Tiempos de Llegada Estimados</h3>
                        <div id="arrival-times" class="space-y-2 max-h-40 overflow-y-auto">
                            <p class="text-xs text-gray-500 dark:text-gray-400 italic">Haz clic en una parada para ver los tiempos de llegada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS (defer para carga no bloqueante) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
            defer></script>

    <script defer>
        // Esperar a que Leaflet esté cargado
        function initMap() {
            if (typeof L === 'undefined') {
                setTimeout(initMap, 50);
                return;
            }

        // Inicializar mapa (Zócalo de Oaxaca como centro)
        const map = L.map('map').setView([17.0654, -96.7256], 13);

        // Agregar capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        // Variables globales
        let busMarkers = {};
        let stopMarkers = {};
        let routePolylines = {};
        let routePathsOSRM = {}; // Guardar las rutas OSRM reales
        let simulationIntervals = {};
        const simulationSpeed = 2;
        let routesLoaded = {}; // Track which routes have been loaded
        let currentFilteredRoute = null; // Current filtered route ID

        // Icono de autobús mejorado con emoji más grande y visible
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

        const stopIcon = L.divIcon({
            html: '<div style="background-color: #dc2626; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4);"></div>',
            className: 'stop-marker',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            popupAnchor: [0, -10]
        });

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

        // Datos de rutas y paradas
        const routes = @json($routes);
        const stops = @json($stops);
        const buses = @json($buses);

        console.log('Autobuses cargados:', buses.length);
        console.log('Rutas cargadas:', routes.length);

        // Variables para tracking de ubicación
        let userLocationWatchId = null;
        let simulatedLocationInterval = null;

        // Ubicación simulada (Oaxaca, México)
        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        // Función para actualizar el marcador de ubicación del usuario
        function updateUserLocationMarker(userLat, userLng, accuracy) {
            // Limitar la precisión a valores razonables (máximo 100m)
            const displayAccuracy = Math.min(Math.round(accuracy), 100);
            const circleRadius = Math.min(accuracy, 100);

            // Eliminar marcador anterior si existe
            if (userLocationMarker) {
                map.removeLayer(userLocationMarker);
            }
            if (userLocationCircle) {
                map.removeLayer(userLocationCircle);
            }

            // Agregar marcador de ubicación del usuario
            userLocationMarker = L.marker([userLat, userLng], {
                icon: userLocationIcon,
                zIndexOffset: 2000
            }).addTo(map).bindPopup(`
                <div class="font-semibold text-blue-600">📍 Tu ubicación</div>
                <div class="text-xs text-gray-600 mt-1">Lat: ${userLat.toFixed(6)}</div>
                <div class="text-xs text-gray-600">Lng: ${userLng.toFixed(6)}</div>
                <div class="text-xs text-gray-500 mt-1">Precisión: ${displayAccuracy}m</div>
            `);

            // Agregar círculo de precisión con radio limitado
            userLocationCircle = L.circle([userLat, userLng], {
                color: '#3b82f6',
                fillColor: '#60a5fa',
                fillOpacity: 0.1,
                radius: circleRadius
            }).addTo(map);
        }

        // Función para usar ubicación simulada
        function useSimulatedLocation() {
            console.log('🎯 Usando ubicación GPS simulada:', SIMULATED_LOCATION);

            // Actualizar indicadores GPS
            const gpsIndicator = document.getElementById('gps-indicator');
            const gpsStatusText = document.getElementById('gps-status-text');
            const gpsDetails = document.getElementById('gps-details');

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
                const lastUpdate = document.getElementById('last-update-passenger');
                const accuracyElement = document.getElementById('location-accuracy');

                if (lastUpdate) {
                    lastUpdate.textContent = new Date().toLocaleTimeString();
                }
                if (accuracyElement) {
                    accuracyElement.textContent = SIMULATED_LOCATION.accuracy;
                }

                // Actualizar marcador
                updateUserLocationMarker(SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng, SIMULATED_LOCATION.accuracy);
            }

            // Actualizar inmediatamente
            updateSimulatedLocation();

            // Centrar mapa en ubicación simulada
            map.setView([SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng], 15);

            // Actualizar cada 2 segundos
            simulatedLocationInterval = setInterval(updateSimulatedLocation, 2000);

            console.log('✅ Ubicación GPS simulada activa');
        }

        // Función para obtener y actualizar continuamente la ubicación del usuario
        function getUserLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                    useSimulatedLocation();
                    resolve({ lat: SIMULATED_LOCATION.lat, lng: SIMULATED_LOCATION.lng });
                    return;
                }

                console.log('🔍 Iniciando rastreo de ubicación del usuario...');

                // Usar watchPosition para actualizaciones continuas
                userLocationWatchId = navigator.geolocation.watchPosition(
                    function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        console.log('✓ Ubicación actualizada:', {
                            lat: userLat,
                            lng: userLng,
                            accuracy: accuracy
                        });

                        // Actualizar indicador GPS
                        const gpsIndicator = document.getElementById('gps-indicator');
                        const gpsStatusText = document.getElementById('gps-status-text');
                        const gpsDetails = document.getElementById('gps-details');
                        const lastUpdate = document.getElementById('last-update-passenger');
                        const accuracyElement = document.getElementById('location-accuracy');

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
                        updateUserLocationMarker(userLat, userLng, accuracy);

                        // Centrar mapa en la ubicación del usuario solo la primera vez
                        if (!userLocationMarker || resolve) {
                            map.setView([userLat, userLng], 15);
                            console.log('✓ Mapa centrado en ubicación del usuario');

                            // Resolver la promesa solo la primera vez
                            if (resolve) {
                                const tempResolve = resolve;
                                resolve = null; // Evitar múltiples resoluciones
                                tempResolve({ lat: userLat, lng: userLng });
                            }
                        }
                    },
                    function(error) {
                        console.warn('⚠ Error al obtener ubicación GPS:', {
                            code: error.code,
                            message: error.message
                        });

                        // Usar ubicación simulada cuando falla el GPS
                        console.log('🔄 Cambiando a ubicación GPS simulada...');
                        useSimulatedLocation();
                        resolve({ lat: SIMULATED_LOCATION.lat, lng: SIMULATED_LOCATION.lng });
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 2000  // Forzar actualización cada 2 segundos
                    }
                );
            });
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

        // Función para calcular distancia entre dos puntos (fórmula de Haversine)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distancia en km
        }

        // Calcular distancia real a lo largo de la ruta entre dos puntos
        function calculateDistanceAlongRoute(routePath, fromIndex, toPosition) {
            let distance = 0;
            let minDistToStop = Infinity;
            let closestIndex = fromIndex;

            // Encontrar el punto de la ruta más cercano a la parada
            for (let i = 0; i < routePath.length; i++) {
                const dist = calculateDistance(
                    routePath[i][0],
                    routePath[i][1],
                    toPosition.lat,
                    toPosition.lng
                );
                if (dist < minDistToStop) {
                    minDistToStop = dist;
                    closestIndex = i;
                }
            }

            // Si el autobús ya pasó la parada, calcular la vuelta completa
            const currentIdx = Math.floor(fromIndex);
            if (closestIndex < currentIdx) {
                // El autobús tiene que completar la ruta y volver
                for (let i = currentIdx; i < routePath.length - 1; i++) {
                    distance += calculateDistance(
                        routePath[i][0],
                        routePath[i][1],
                        routePath[i + 1][0],
                        routePath[i + 1][1]
                    );
                }
                for (let i = 0; i < closestIndex; i++) {
                    distance += calculateDistance(
                        routePath[i][0],
                        routePath[i][1],
                        routePath[i + 1][0],
                        routePath[i + 1][1]
                    );
                }
            } else {
                // Calcular distancia desde posición actual hasta la parada
                for (let i = currentIdx; i < closestIndex; i++) {
                    distance += calculateDistance(
                        routePath[i][0],
                        routePath[i][1],
                        routePath[i + 1][0],
                        routePath[i + 1][1]
                    );
                }
            }

            return distance;
        }

        // Función para calcular tiempo estimado de llegada REALISTA
        function calculateArrivalTime(stop) {
            const arrivalTimes = [];

            simulatedBuses.forEach(bus => {
                // Verificar si el autobús pasa por esta parada
                const busRoute = bus.route;
                const stopInRoute = busRoute.stops && busRoute.stops.find(s => s.id === stop.id);

                if (stopInRoute && bus.routePath) {
                    // Calcular distancia REAL a lo largo de la ruta
                    const distanceAlongRoute = calculateDistanceAlongRoute(
                        bus.routePath,
                        bus.pathIndex,
                        { lat: stop.latitude, lng: stop.longitude }
                    );

                    // Usar la velocidad real del autobús
                    const avgSpeed = bus.avgSpeedKmh || 20;

                    // Calcular tiempo en minutos de forma realista
                    const timeInMinutes = (distanceAlongRoute / avgSpeed) * 60;

                    // Agregar tiempo de paradas (asumimos 30 segundos por parada)
                    const stopsUntilDestination = Math.floor(distanceAlongRoute * 2); // Aproximación
                    const stopTimeMinutes = (stopsUntilDestination * 0.5); // 30 segundos por parada

                    const totalTime = timeInMinutes + stopTimeMinutes;

                    arrivalTimes.push({
                        bus: bus,
                        distance: distanceAlongRoute,
                        time: Math.ceil(totalTime),
                        route: busRoute
                    });
                }
            });

            // Ordenar por tiempo de llegada
            arrivalTimes.sort((a, b) => a.time - b.time);

            return arrivalTimes;
        }

        // Función para mostrar tiempos de llegada en el panel
        function showArrivalTimes(stop) {
            const arrivalTimes = calculateArrivalTime(stop);
            const arrivalTimesDiv = document.getElementById('arrival-times');

            if (arrivalTimes.length === 0) {
                arrivalTimesDiv.innerHTML = `
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                        No hay autobuses en ruta hacia esta parada
                    </p>
                `;
                return;
            }

            let html = `
                <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Parada: ${stop.name}
                </div>
            `;

            arrivalTimes.forEach(arrival => {
                const timeText = arrival.time < 1 ? 'Menos de 1 min' :
                                arrival.time === 1 ? '1 minuto' :
                                `${arrival.time} minutos`;

                html += `
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 rounded-full" style="background-color: ${arrival.route.color || '#8b5cf6'}"></div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">🚌 ${arrival.bus.license_plate}</span>
                            <span class="text-gray-500 dark:text-gray-400">(${arrival.route.name})</span>
                        </div>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">${timeText}</span>
                    </div>
                `;
            });

            arrivalTimesDiv.innerHTML = html;
        }

        // Función para mostrar información de una ruta completa
        function showRouteInfo(route) {
            const arrivalTimesDiv = document.getElementById('arrival-times');

            // Encontrar todos los autobuses en esta ruta
            const routeBuses = simulatedBuses.filter(bus => bus.route.id === route.id);

            if (routeBuses.length === 0) {
                arrivalTimesDiv.innerHTML = `
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Ruta: ${route.name}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                        No hay autobuses activos en esta ruta
                    </p>
                `;
                return;
            }

            // Obtener todas las paradas de esta ruta ordenadas
            const routeStops = route.stops ? route.stops.sort((a, b) => {
                const aOrder = a.pivot?.order || 0;
                const bOrder = b.pivot?.order || 0;
                return aOrder - bOrder;
            }) : [];

            let html = `
                <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background-color: ${route.color || '#8b5cf6'}"></div>
                        <span>Ruta: ${route.name}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${route.code}</div>
                </div>
            `;

            // Para cada autobús en la ruta, mostrar su próxima parada
            routeBuses.forEach((bus, index) => {
                // Encontrar la próxima parada del autobús
                let nextStop = null;
                let minDistance = Infinity;

                routeStops.forEach(stop => {
                    if (bus.routePath) {
                        const distanceAlongRoute = calculateDistanceAlongRoute(
                            bus.routePath,
                            bus.pathIndex,
                            { lat: stop.latitude, lng: stop.longitude }
                        );

                        if (distanceAlongRoute < minDistance && distanceAlongRoute > 0.05) {
                            minDistance = distanceAlongRoute;
                            nextStop = stop;
                        }
                    }
                });

                if (nextStop) {
                    const avgSpeed = bus.avgSpeedKmh || 20;
                    const timeInMinutes = (minDistance / avgSpeed) * 60;
                    const stopsUntilDestination = Math.floor(minDistance * 2);
                    const stopTimeMinutes = (stopsUntilDestination * 0.5);
                    const totalTime = Math.ceil(timeInMinutes + stopTimeMinutes);

                    const timeText = totalTime < 1 ? 'Menos de 1 min' :
                                    totalTime === 1 ? '1 minuto' :
                                    `${totalTime} minutos`;

                    html += `
                        <div class="mb-3 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900 dark:text-gray-100 text-xs">🚌 ${bus.license_plate}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${bus.model}</span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                Próxima parada: <span class="font-semibold">${nextStop.name}</span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold">
                                Llegada estimada: ${timeText}
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="mb-3 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100 text-xs">🚌 ${bus.license_plate}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">${bus.model}</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                Calculando próxima parada...
                            </div>
                        </div>
                    `;
                }
            });

            arrivalTimesDiv.innerHTML = html;
        }

        // Simulated bus data
        let simulatedBuses = [];

        // Función helper para fetch con timeout
        async function fetchWithTimeout(url, timeout = 3000) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(url, { signal: controller.signal });
                clearTimeout(timeoutId);
                return response;
            } catch (error) {
                clearTimeout(timeoutId);
                throw error;
            }
        }

        // Dibujar una ruta específica usando OSRM (lazy loading con timeout)
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

            // SOLO usar OSRM - sin fallback de líneas rectas
            for (let attempt = 1; attempt <= 3; attempt++) {
                try {
                    console.log(`[${route.name}] Intento ${attempt}/3 de cargar ruta sobre calles...`);

                    const response = await fetchWithTimeout(
                        `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`,
                        15000  // 15 segundos de timeout
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
                        }).addTo(map);

                        polyline.bindPopup(`
                            <div class="font-semibold">${route.name}</div>
                            <div class="text-sm text-gray-600">${route.code}</div>
                            <div class="text-xs mt-1">${route.description}</div>
                        `);

                        // Agregar evento click para mostrar información de la ruta
                        polyline.on('click', function() {
                            showRouteInfo(route);
                        });

                        routePolylines[route.id] = polyline;
                        routesLoaded[route.id] = true;
                        console.log(`✅ [${route.name}] Ruta cargada sobre calles correctamente`);
                        return; // Éxito - salir de la función
                    } else {
                        console.warn(`⚠ [${route.name}] OSRM retornó: ${data.code}`);
                    }
                } catch (error) {
                    console.warn(`⚠ [${route.name}] Error en intento ${attempt}:`, error.message);
                }

                // Esperar antes del siguiente intento (excepto en el último)
                if (attempt < 3) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }

            // Si llegamos aquí, todos los intentos fallaron
            console.error(`❌ [${route.name}] No se pudo cargar sobre calles después de 3 intentos - LA RUTA NO SE MOSTRARÁ`);
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

        // Función de fallback ELIMINADA - Solo rutas sobre calles con OSRM

        // Calcular distancia total de una ruta en kilómetros
        function calculateRouteDistance(routePath) {
            let totalDistance = 0;
            for (let i = 0; i < routePath.length - 1; i++) {
                const lat1 = routePath[i][0];
                const lng1 = routePath[i][1];
                const lat2 = routePath[i + 1][0];
                const lng2 = routePath[i + 1][1];
                totalDistance += calculateDistance(lat1, lng1, lat2, lng2);
            }
            return totalDistance;
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

                // Calcular distancia total de la ruta
                const routeDistanceKm = calculateRouteDistance(routePath);

                // Velocidad promedio de autobús urbano: 20 km/h
                const avgSpeedKmh = 20;

                // Tiempo de actualización: 200ms = 0.2 segundos
                const updateIntervalSeconds = 0.2;

                // Distancia que avanza en cada actualización (en km)
                const distancePerUpdate = (avgSpeedKmh * updateIntervalSeconds) / 3600; // km

                // Calcular incremento del índice basado en la distancia de la ruta
                const speedIncrement = (distancePerUpdate / routeDistanceKm) * routePath.length;

                // Iniciar cada autobús en una posición diferente de la ruta OSRM
                const startProgress = (index * 0.3) % 1; // Distribuir autobuses
                const pathIndex = Math.floor(routePath.length * startProgress);
                const startPoint = routePath[pathIndex];

                console.log(`Autobús ${bus.license_plate}: Ruta ${routeDistanceKm.toFixed(2)}km, Velocidad: ${avgSpeedKmh}km/h, Incremento: ${speedIncrement.toFixed(4)}`);

                return {
                    id: bus.id,
                    license_plate: bus.license_plate,
                    model: bus.model,
                    capacity: bus.capacity,
                    route: fullRoute,
                    routePath: routePath,
                    routeDistanceKm: routeDistanceKm,
                    speedIncrement: speedIncrement, // Velocidad calculada de forma realista
                    avgSpeedKmh: avgSpeedKmh,
                    currentPosition: {
                        lat: startPoint[0],
                        lng: startPoint[1]
                    },
                    pathIndex: pathIndex,
                    direction: 1 // 1 = forward, -1 = backward
                };
            }).filter(bus => bus !== null);

            console.log('✅ Autobuses simulados inicializados con velocidades realistas:', simulatedBuses.length);
        }

        // Dibujar paradas
        function drawStops() {
            stops.forEach(stop => {
                if (!stopMarkers[stop.id]) {
                    const marker = L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                        .addTo(map)
                        .bindPopup(`
                            <div class="font-semibold">${stop.name}</div>
                            <div class="text-sm text-gray-600">${stop.address || ''}</div>
                            <button onclick="showArrivalTimesForStop(${stop.id})"
                                    class="mt-2 mr-1 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                                🕐 Ver tiempos de llegada
                            </button>
                            <button onclick="addToFavorites(${stop.id}, '${stop.name.replace(/'/g, "\\'")}')"
                                    class="mt-2 px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600">
                                ⭐ Agregar a favoritos
                            </button>
                        `);
                    stopMarkers[stop.id] = marker;
                }
            });
        }

        // Función global para mostrar tiempos de llegada
        window.showArrivalTimesForStop = function(stopId) {
            const stop = stops.find(s => s.id === stopId);
            if (stop) {
                showArrivalTimes(stop);
            }
        };

        // Dibujar autobuses
        function drawBuses() {
            simulatedBuses.forEach(bus => {
                const available = bus.capacity;

                if (busMarkers[bus.id]) {
                    busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
                } else {
                    const marker = L.marker([bus.currentPosition.lat, bus.currentPosition.lng], {
                        icon: busIcon,
                        zIndexOffset: 1000 // Asegurar que los autobuses estén encima
                    })
                        .addTo(map)
                        .bindPopup(`
                            <div class="font-semibold text-lg">🚌 ${bus.license_plate}</div>
                            <div class="text-sm text-gray-600">${bus.model}</div>
                            <div class="mt-2 text-sm">
                                <div><strong>Ruta:</strong> ${bus.route.name}</div>
                                <div><strong>Capacidad:</strong> ${bus.capacity} pasajeros</div>
                            </div>
                        `);
                    busMarkers[bus.id] = marker;
                    console.log('Autobús dibujado:', bus.license_plate, 'en', bus.currentPosition);
                }
            });
        }

        // Mover autobús a lo largo de su ruta con velocidad realista
        function moveBus(bus) {
            if (!bus.routePath || bus.routePath.length === 0) return;

            // Usar velocidad calculada de forma realista (basada en 20 km/h)
            const speed = bus.speedIncrement || 0.02; // Fallback a valor pequeño
            bus.pathIndex += bus.direction * speed;

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
                }, 200); // Actualizar cada 200ms para movimiento más rápido
            });

            console.log('✅ Simulación iniciada con', simulatedBuses.length, 'autobuses');
        }

        // Función para mostrar rutas más cercanas
        window.showNearestRoutes = async function() {
            console.log('🔍 Buscando rutas más cercanas...');

            // Verificar si tenemos la ubicación del usuario
            if (!userLocationMarker) {
                alert('⚠ Primero necesitamos obtener tu ubicación. Por favor, permite el acceso a tu ubicación en el navegador.');
                try {
                    await getUserLocation();
                } catch (error) {
                    console.error('Error al obtener ubicación:', error);
                    return;
                }
            }

            // Obtener la posición actual del usuario
            const userPos = userLocationMarker.getLatLng();
            const userLat = userPos.lat;
            const userLng = userPos.lng;

            console.log('📍 Ubicación del usuario:', userLat, userLng);

            // Calcular distancia a cada ruta (usando sus paradas)
            const routeDistances = [];

            routes.forEach(route => {
                if (!route.stops || route.stops.length === 0) return;

                // Calcular distancia mínima a cualquier parada de esta ruta
                let minDistance = Infinity;
                let closestStop = null;

                route.stops.forEach(stop => {
                    const distance = calculateDistance(
                        userLat,
                        userLng,
                        stop.latitude,
                        stop.longitude
                    );

                    if (distance < minDistance) {
                        minDistance = distance;
                        closestStop = stop;
                    }
                });

                routeDistances.push({
                    route: route,
                    distance: minDistance,
                    closestStop: closestStop
                });
            });

            // Ordenar por distancia
            routeDistances.sort((a, b) => a.distance - b.distance);

            // Tomar las 3 rutas más cercanas (dentro de 5km)
            const nearestRoutes = routeDistances.filter(r => r.distance <= 5).slice(0, 3);

            if (nearestRoutes.length === 0) {
                alert('No se encontraron rutas cercanas (dentro de 5km).');
                return;
            }

            // Crear mensaje con las rutas más cercanas
            let message = '📍 Rutas más cercanas a tu ubicación:\n\n';
            nearestRoutes.forEach((item, index) => {
                const distanceKm = item.distance.toFixed(2);
                const distanceM = Math.round(item.distance * 1000);
                const distanceText = item.distance < 1 ? `${distanceM}m` : `${distanceKm}km`;

                message += `${index + 1}. ${item.route.name} (${item.route.code})\n`;
                message += `   📏 Distancia: ${distanceText}\n`;
                message += `   🚏 Parada más cercana: ${item.closestStop.name}\n\n`;
            });

            message += '¿Deseas filtrar el mapa para mostrar solo estas rutas?';

            if (confirm(message)) {
                // Mostrar solo las rutas más cercanas
                const nearestRouteIds = nearestRoutes.map(r => r.route.id);

                // Cargar las rutas seleccionadas
                for (const routeId of nearestRouteIds) {
                    const route = routes.find(r => r.id === routeId);
                    if (route && !routesLoaded[routeId]) {
                        await drawRoute(route);
                    }
                }

                // Ocultar todas las rutas y autobuses
                Object.keys(routePolylines).forEach(key => {
                    const rId = parseInt(key);
                    if (nearestRouteIds.includes(rId)) {
                        routePolylines[key].addTo(map);
                    } else {
                        map.removeLayer(routePolylines[key]);
                    }
                });

                // Filtrar autobuses
                simulatedBuses.forEach(bus => {
                    const shouldShow = nearestRouteIds.includes(bus.route.id);
                    if (shouldShow) {
                        if (busMarkers[bus.id]) {
                            busMarkers[bus.id].addTo(map);
                        }
                    } else {
                        if (busMarkers[bus.id]) {
                            map.removeLayer(busMarkers[bus.id]);
                        }
                    }
                });

                // Resaltar las paradas más cercanas
                nearestRoutes.forEach(item => {
                    const stopMarker = stopMarkers[item.closestStop.id];
                    if (stopMarker) {
                        stopMarker.openPopup();
                        setTimeout(() => {
                            map.setView([item.closestStop.latitude, item.closestStop.longitude], 15);
                        }, 500);
                    }
                });

                console.log('✅ Mostrando rutas más cercanas:', nearestRouteIds);
            }
        };

        // Agregar a favoritos
        function addToFavorites(stopId, stopName) {
            const nickname = prompt('Dale un nombre a esta parada favorita:', stopName);
            if (nickname) {
                fetch(`/passenger/favorites/${stopId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ nickname: nickname })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('✓ Parada agregada a favoritos correctamente');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('✗ Error: ' + (error.error || error.message || 'No se pudo agregar a favoritos'));
                });
            }
        }

        // Toggle paradas
        document.getElementById('show-stops').addEventListener('change', function(e) {
            Object.values(stopMarkers).forEach(marker => {
                if (e.target.checked) {
                    marker.addTo(map);
                } else {
                    map.removeLayer(marker);
                }
            });
        });

        // Variable global para rutas cercanas
        let currentNearestRoutes = [];

        // Calcular rutas más cercanas
        function calculateNearestRoutes() {
            if (!userLocationMarker) {
                console.warn('⚠ Ubicación del usuario no disponible');
                return [];
            }

            const userPos = userLocationMarker.getLatLng();
            const userLat = userPos.lat;
            const userLng = userPos.lng;

            const routeDistances = [];

            routes.forEach(route => {
                if (!route.stops || route.stops.length === 0) return;

                let minDistance = Infinity;
                let closestStop = null;

                route.stops.forEach(stop => {
                    const distance = calculateDistance(
                        userLat,
                        userLng,
                        stop.latitude,
                        stop.longitude
                    );

                    if (distance < minDistance) {
                        minDistance = distance;
                        closestStop = stop;
                    }
                });

                routeDistances.push({
                    route: route,
                    distance: minDistance,
                    closestStop: closestStop
                });
            });

            routeDistances.sort((a, b) => a.distance - b.distance);
            return routeDistances.filter(r => r.distance <= 5).slice(0, 3);
        }

        // Mostrar rutas cercanas en el contenedor
        function showNearestRoutesInContainer(nearestRoutes) {
            const container = document.getElementById('nearest-routes-container');
            const listDiv = document.getElementById('nearest-routes-list');

            if (nearestRoutes.length === 0) {
                listDiv.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron rutas cercanas (dentro de 5km).</p>';
                container.classList.remove('hidden');
                return;
            }

            let html = '';
            nearestRoutes.forEach((item, index) => {
                const distanceKm = item.distance.toFixed(2);
                const distanceM = Math.round(item.distance * 1000);
                const distanceText = item.distance < 1 ? `${distanceM}m` : `${distanceKm}km`;
                const routeColor = item.route.color || '#8b5cf6';

                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-12 rounded-full" style="background-color: ${routeColor}"></div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">${item.route.name}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${item.route.code}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                    🚏 ${item.closestStop.name}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">${distanceText}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">distancia</div>
                        </div>
                    </div>
                `;
            });

            listDiv.innerHTML = html;
            container.classList.remove('hidden');
        }

        // Filtrar mapa para mostrar solo rutas cercanas
        async function filterNearestRoutes(show) {
            if (!show) {
                // Restaurar todas las rutas y autobuses
                Object.values(routePolylines).forEach(polyline => polyline.addTo(map));
                simulatedBuses.forEach(bus => {
                    if (busMarkers[bus.id]) {
                        busMarkers[bus.id].addTo(map);
                    }
                });
                document.getElementById('nearest-routes-container').classList.add('hidden');
                currentNearestRoutes = [];
                return;
            }

            const nearestRoutes = calculateNearestRoutes();
            currentNearestRoutes = nearestRoutes;

            if (nearestRoutes.length === 0) {
                showNearestRoutesInContainer([]);
                return;
            }

            showNearestRoutesInContainer(nearestRoutes);

            const nearestRouteIds = nearestRoutes.map(r => r.route.id);

            // Cargar rutas cercanas en paralelo
            const routePromises = nearestRouteIds.map(async routeId => {
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            });
            await Promise.all(routePromises);

            // Filtrar rutas en el mapa
            Object.keys(routePolylines).forEach(key => {
                const rId = parseInt(key);
                if (nearestRouteIds.includes(rId)) {
                    routePolylines[key].addTo(map);
                } else {
                    map.removeLayer(routePolylines[key]);
                }
            });

            // Filtrar autobuses
            simulatedBuses.forEach(bus => {
                const shouldShow = nearestRouteIds.includes(bus.route.id);
                if (shouldShow) {
                    if (busMarkers[bus.id]) {
                        busMarkers[bus.id].addTo(map);
                    }
                } else {
                    if (busMarkers[bus.id]) {
                        map.removeLayer(busMarkers[bus.id]);
                    }
                }
            });

            console.log('✅ Mostrando solo rutas cercanas:', nearestRouteIds);
        }

        // Toggle para mostrar solo rutas cercanas
        document.getElementById('show-nearest-routes').addEventListener('change', function(e) {
            filterNearestRoutes(e.target.checked);
        });

        // Filtro de ruta (con lazy loading)
        document.getElementById('route-filter').addEventListener('change', async function(e) {
            const routeId = parseInt(e.target.value) || null;
            currentFilteredRoute = routeId;

            if (routeId) {
                // Cargar solo la ruta seleccionada si no está cargada
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            }

            // Mostrar/ocultar rutas
            Object.keys(routePolylines).forEach(key => {
                const rId = parseInt(key);
                if (!routeId || rId === routeId) {
                    routePolylines[key].addTo(map);
                } else {
                    map.removeLayer(routePolylines[key]);
                }
            });

            // Mostrar/ocultar autobuses
            // Verificar si hay un bus_id específico en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const busIdFromUrl = urlParams.get('bus_id');
            const specificBusId = busIdFromUrl ? parseInt(busIdFromUrl) : null;

            simulatedBuses.forEach(bus => {
                let shouldShow = false;

                // Si hay un bus específico seleccionado, solo mostrar ese
                if (specificBusId) {
                    shouldShow = bus.id === specificBusId;
                } else {
                    // Si no, mostrar por ruta
                    shouldShow = !routeId || bus.route.id === routeId;
                }

                if (shouldShow) {
                    if (busMarkers[bus.id]) {
                        busMarkers[bus.id].addTo(map);
                    }
                } else {
                    if (busMarkers[bus.id]) {
                        map.removeLayer(busMarkers[bus.id]);
                    }
                }
            });
        });

        // Inicializar todo
        console.log('🚀 Inicializando sistema de autobuses...');
        console.log('Rutas disponibles:', routes.length);
        console.log('Autobuses disponibles:', buses.length);
        console.log('Paradas disponibles:', stops.length);

        // Inicialización optimizada
        (async function init() {
            console.log('🚀 Iniciando sistema de mapas...');

            // Iniciar operaciones en paralelo
            const stopsPromise = Promise.resolve(drawStops());
            const routesPromise = loadVisibleRoutes();
            const locationPromise = getUserLocation().catch(err => {
                console.warn('⚠ Continuando sin ubicación del usuario:', err.message);
                return null;
            });

            // Esperar por paradas, rutas y ubicación en paralelo
            await Promise.all([stopsPromise, routesPromise, locationPromise]);

            console.log('✅ Mapa base cargado');

            initializeSimulatedBuses();
            drawBuses();
            startSimulation();

            console.log('✅ Sistema listo y en funcionamiento!');
            console.log('💡 Las rutas se cargan automáticamente al seleccionarlas en el filtro');

            // Si viene un route_id en la URL, activar el filtro automáticamente
            const urlParams = new URLSearchParams(window.location.search);
            const routeIdFromUrl = urlParams.get('route_id');
            if (routeIdFromUrl) {
                console.log('📍 Filtrando ruta seleccionada:', routeIdFromUrl);
                document.getElementById('route-filter').dispatchEvent(new Event('change'));
            }
        })();

        } // Cierre de initMap()

        // Iniciar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }
    </script>
</x-app-layout>
