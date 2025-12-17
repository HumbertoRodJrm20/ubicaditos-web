<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mapa y Navegación') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Información de la Ruta y Control de Viaje -->
            @if($route)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $route->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $route->description }}</p>
                            </div>
                        </div>

                        <!-- Botones de Control de Viaje -->
                        <div class="flex gap-3">
                            @if($currentTrip)
                                <form method="POST" action="{{ route('driver.end-trip') }}" class="flex-1" onsubmit="return confirm('¿Estás seguro de finalizar este viaje?')">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-6 py-3 bg-danger-600 text-white text-sm font-medium rounded-md hover:bg-danger-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500 flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                        </svg>
                                        Finalizar Viaje
                                    </button>
                                </form>
                            @else
                                @if(auth()->user()->is_active && $bus->status === 'active')
                                    <form method="POST" action="{{ route('driver.start-trip') }}" class="flex-1">
                                        @csrf
                                        <button type="submit"
                                                class="w-full px-6 py-3 bg-success-600 text-white text-sm font-medium rounded-md hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-500 flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Iniciar Viaje
                                        </button>
                                    </form>
                                @else
                                    <div class="flex-1">
                                        <button type="button" disabled
                                                class="w-full px-6 py-3 bg-gray-400 text-white text-sm font-medium rounded-md cursor-not-allowed opacity-60 flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Iniciar Viaje
                                        </button>
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">
                                            @if(!auth()->user()->is_active)
                                                Debes estar activo para iniciar un viaje. Contacta al administrador.
                                            @elseif($bus->status !== 'active')
                                                El autobús debe estar en estado activo para iniciar un viaje.
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Mapa -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div id="map" class="w-full h-96 lg:h-[600px] relative">
                    <!-- Overlay de información -->
                    <div class="absolute top-4 right-4 z-[1000] bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 max-w-xs">
                        <div class="text-sm space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span id="gps-indicator" class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    <span id="gps-text" class="text-gray-700 dark:text-gray-300">GPS Activo</span>
                                </div>
                            </div>

                            <!-- Toggle de Ubicación Simulada -->
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
                                <label class="flex items-center cursor-pointer" id="simulated-location-label">
                                    <input type="checkbox" id="simulated-location-toggle" class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-xs font-medium text-gray-700 dark:text-gray-300">Ubicación Simulada</span>
                                </label>
                                <p id="simulated-hint" class="text-xs text-gray-500 dark:text-gray-400 mt-1 hidden">
                                    Haz clic en el mapa para establecer tu posición
                                </p>
                                <p id="trip-active-warning" class="text-xs text-amber-600 dark:text-amber-400 mt-1 hidden">
                                    ⚠️ No puedes cambiar la ubicación durante un viaje
                                </p>
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Última actualización: <span id="last-update">Iniciando...</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Velocidad: <span id="current-speed">0</span> km/h
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paradas de la Ruta -->
            @if($stops && $stops->count() > 0)
                <div class="mt-4 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Paradas de la Ruta</h3>
                        <div class="space-y-3">
                            @foreach($stops as $index => $stop)
                                <div id="stop-{{ $stop->id }}" class="flex items-center space-x-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg transition-all">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $stop->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stop->address }}</p>
                                    </div>
                                    @if($index < $stops->count() - 1)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            ~{{ $stop->pivot->estimated_time_from_previous ?? 5 }} min
                                        </div>
                                    @endif
                                    <div class="stop-status hidden">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Llegado
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
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

        // Inicializar mapa
        const map = L.map('map').setView([{{ $bus->current_latitude ?? 19.4326 }}, {{ $bus->current_longitude ?? -99.1332 }}], 14);

        // Agregar capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        // Datos de paradas
        const stops = @json($stops ?? []);
        const route = @json($route ?? null);
        const busData = {
            id: {{ $bus->id }},
            license_plate: '{{ $bus->license_plate }}',
            latitude: {{ $bus->current_latitude ?? 19.4326 }},
            longitude: {{ $bus->current_longitude ?? -99.1332 }}
        };

        // Icono del autobús (más grande y destacado)
        const busIcon = L.divIcon({
            html: '<div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); width: 40px; height: 40px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 20px; animation: pulse 2s infinite;">🚌</div>',
            className: 'bus-marker-driver',
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });

        // Icono de parada
        const stopIcon = L.divIcon({
            html: '<div style="background-color: #dc2626; width: 28px; height: 28px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
            className: 'stop-marker',
            iconSize: [28, 28],
            iconAnchor: [14, 14],
            popupAnchor: [0, -14]
        });

        // Variables para tracking
        let busMarker = null;
        let routePath = null;
        let routePolyline = null;
        let stopMarkers = [];
        let watchId = null;
        let lastPosition = null;
        let simulatedLocationInterval = null;
        let isSimulatedMode = false;
        let customSimulatedLocation = null;
        const hasTripActive = {{ isset($currentTrip) && $currentTrip ? 'true' : 'false' }};

        // Ubicación simulada por defecto (Oaxaca, México)
        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        // Agregar animación CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.1);
                }
            }
        `;
        document.head.appendChild(style);

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

        // Generar ruta que sigue las calles (con timeout)
        async function generateRoute() {
            if (!stops || stops.length < 2) {
                console.warn('No hay suficientes paradas para generar ruta');
                return;
            }

            const waypoints = stops.map(stop =>
                `${stop.longitude},${stop.latitude}`
            ).join(';');

            try {
                const response = await fetchWithTimeout(
                    `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`,
                    3000
                );
                const data = await response.json();

                if (data.code === 'Ok' && data.routes && data.routes[0]) {
                    // Convertir coordenadas
                    routePath = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                    // Dibujar la ruta
                    const color = route?.color || '#8b5cf6';
                    routePolyline = L.polyline(routePath, {
                        color: color,
                        weight: 5,
                        opacity: 0.7
                    }).addTo(map);

                    // Ajustar el mapa para mostrar toda la ruta
                    map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });

                    console.log('Ruta generada exitosamente');
                } else {
                    console.error('Error al generar ruta:', data);
                    fallbackStraightLine();
                }
            } catch (error) {
                console.error('Error al consultar OSRM:', error);
                fallbackStraightLine();
            }
        }

        // Fallback: línea recta entre paradas
        function fallbackStraightLine() {
            if (!stops || stops.length < 2) return;

            routePath = stops.map(stop => [parseFloat(stop.latitude), parseFloat(stop.longitude)]);
            const color = route?.color || '#8b5cf6';

            routePolyline = L.polyline(routePath, {
                color: color,
                weight: 5,
                opacity: 0.7,
                dashArray: '10, 10'
            }).addTo(map);

            map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
            console.log('Ruta generada con líneas rectas (fallback)');
        }

        // Dibujar paradas en el mapa
        function drawStops() {
            stops.forEach((stop, index) => {
                const marker = L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                    .addTo(map)
                    .bindPopup(`
                        <div class="font-semibold">Parada ${index + 1}</div>
                        <div class="text-sm">${stop.name}</div>
                        <div class="text-xs text-gray-500">${stop.address || ''}</div>
                    `);

                stopMarkers.push({
                    id: stop.id,
                    marker: marker,
                    position: [stop.latitude, stop.longitude]
                });
            });
        }

        // Crear marcador del autobús
        function createBusMarker(lat, lng) {
            busMarker = L.marker([lat, lng], { icon: busIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="font-semibold">${busData.license_plate}</div>
                    <div class="text-sm">Mi ubicación actual</div>
                `);
        }

        // Calcular velocidad
        function calculateSpeed(oldPos, newPos, timeElapsed) {
            if (!oldPos || !newPos) return 0;

            const R = 6371; // Radio de la Tierra en km
            const dLat = (newPos.lat - oldPos.lat) * Math.PI / 180;
            const dLon = (newPos.lng - oldPos.lng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(oldPos.lat * Math.PI / 180) * Math.cos(newPos.lat * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c; // en km

            const timeInHours = timeElapsed / 3600000; // ms a horas
            const speed = distance / timeInHours;

            return Math.round(speed);
        }

        // Función para usar ubicación simulada
        function useSimulatedLocationDriver() {
            const location = customSimulatedLocation || SIMULATED_LOCATION;
            console.log('🎯 Usando ubicación GPS simulada para conductor:', location);

            const lat = location.lat;
            const lng = location.lng;
            const timestamp = Date.now();

            // Actualizar marcador en el mapa
            if (busMarker) {
                busMarker.setLatLng([lat, lng]);
            } else {
                createBusMarker(lat, lng);
            }

            // Centrar mapa suavemente
            map.panTo([lat, lng]);

            // Actualizar UI
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            document.getElementById('current-speed').textContent = '0';

            // Guardar última posición
            lastPosition = { lat, lng, timestamp };

            // Enviar ubicación al servidor una sola vez
            fetch('{{ route("driver.update-location") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ latitude: lat, longitude: lng })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Ubicación simulada actualizada en servidor');
            })
            .catch(error => {
                console.error('Error al actualizar ubicación:', error);
            });

            // Verificar proximidad a paradas
            checkProximityToStops(lat, lng);

            console.log('✅ Ubicación GPS simulada establecida (sin actualización automática)');
        }

        // Actualizar ubicación en tiempo real
        function updateLocation() {
            if (!navigator.geolocation) {
                console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                useSimulatedLocationDriver();
                return;
            }

            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        // Si hay viaje activo y está en modo simulado, ignorar actualizaciones de GPS
                        if (hasTripActive && isSimulatedMode) {
                            console.log('🔒 Viaje activo: ignorando actualizaciones de GPS en modo simulado');
                            return;
                        }

                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const timestamp = Date.now();

                        // Calcular velocidad si hay posición previa
                        let speed = 0;
                        if (lastPosition) {
                            const timeElapsed = timestamp - lastPosition.timestamp;
                            speed = calculateSpeed(
                                { lat: lastPosition.lat, lng: lastPosition.lng },
                                { lat, lng },
                                timeElapsed
                            );
                        }

                        // Actualizar marcador en el mapa
                        if (busMarker) {
                            busMarker.setLatLng([lat, lng]);
                        } else {
                            createBusMarker(lat, lng);
                        }

                        // Centrar mapa suavemente
                        map.panTo([lat, lng]);

                        // Actualizar UI
                        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                        document.getElementById('current-speed').textContent = speed;
                        document.getElementById('gps-status').className = 'w-4 h-4 rounded-full bg-green-500 animate-pulse';

                        // Guardar última posición
                        lastPosition = { lat, lng, timestamp };

                        // Enviar ubicación al servidor
                        fetch('{{ route("driver.update-location") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ latitude: lat, longitude: lng })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Ubicación actualizada en servidor');
                        })
                        .catch(error => {
                            console.error('Error al actualizar ubicación:', error);
                        });

                        // Verificar proximidad a paradas
                        checkProximityToStops(lat, lng);
                    },
                    function(error) {
                        console.warn('⚠ Error al obtener ubicación GPS del conductor:', {
                            code: error.code,
                            message: error.message
                        });

                        // Usar ubicación simulada cuando falla el GPS
                        console.log('🔄 Cambiando a ubicación GPS simulada para conductor...');
                        useSimulatedLocationDriver();
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 2000,  // Forzar actualización cada 2 segundos
                        timeout: 5000
                    }
                );
            } else {
                alert('Tu navegador no soporta geolocalización');
            }
        }

        // Verificar proximidad a paradas (radio de 100m)
        function checkProximityToStops(lat, lng) {
            const threshold = 0.001; // ~100 metros

            stopMarkers.forEach((stopData, index) => {
                const stopLat = stopData.position[0];
                const stopLng = stopData.position[1];

                const distance = Math.sqrt(
                    Math.pow(stopLat - lat, 2) + Math.pow(stopLng - lng, 2)
                );

                const stopElement = document.getElementById(`stop-${stopData.id}`);
                if (distance < threshold) {
                    // Cerca de la parada
                    if (stopElement) {
                        stopElement.classList.add('bg-green-100', 'dark:bg-green-900');
                        stopElement.querySelector('.stop-status').classList.remove('hidden');
                    }
                } else {
                    // Lejos de la parada
                    if (stopElement) {
                        stopElement.classList.remove('bg-green-100', 'dark:bg-green-900');
                        stopElement.querySelector('.stop-status').classList.add('hidden');
                    }
                }
            });
        }

        // Inicialización optimizada - cargas en paralelo
        console.log('Inicializando mapa del conductor...');

        // Ejecutar en paralelo operaciones independientes
        Promise.all([
            generateRoute(),  // Cargar ruta
            Promise.resolve(drawStops()),  // Dibujar paradas
            Promise.resolve(createBusMarker(busData.latitude, busData.longitude))  // Crear marcador
        ]).then(() => {
            console.log('✅ Mapa inicializado y listo');
        });

        // Toggle de ubicación simulada
        const simulatedToggle = document.getElementById('simulated-location-toggle');
        const simulatedHint = document.getElementById('simulated-hint');
        const gpsIndicator = document.getElementById('gps-indicator');
        const gpsText = document.getElementById('gps-text');
        const tripActiveWarning = document.getElementById('trip-active-warning');
        const simulatedLabel = document.getElementById('simulated-location-label');

        // Bloquear toggle si hay viaje activo
        if (hasTripActive) {
            simulatedToggle.disabled = true;
            simulatedLabel.classList.add('opacity-50', 'cursor-not-allowed');
            simulatedLabel.classList.remove('cursor-pointer');
            tripActiveWarning.classList.remove('hidden');
            simulatedHint.classList.add('hidden');
        }

        simulatedToggle.addEventListener('change', function() {
            // Bloquear cambios si hay viaje activo
            if (hasTripActive) {
                this.checked = isSimulatedMode; // Revertir el cambio
                alert('No puedes cambiar el modo de ubicación mientras hay un viaje activo');
                return;
            }

            isSimulatedMode = this.checked;

            if (isSimulatedMode) {
                // Activar modo simulado
                simulatedHint.classList.remove('hidden');
                gpsIndicator.className = 'w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse';
                gpsText.textContent = 'Modo Simulado';

                // Detener geolocalización real si está activa
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }

                // Si ya hay una ubicación personalizada, usarla
                if (customSimulatedLocation) {
                    useSimulatedLocationDriver();
                } else {
                    alert('Haz clic en el mapa para establecer tu ubicación simulada');
                }
            } else {
                // Desactivar modo simulado
                simulatedHint.classList.add('hidden');
                gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse';
                gpsText.textContent = 'GPS Activo';
                customSimulatedLocation = null;

                // Detener intervalo de simulación
                if (simulatedLocationInterval) {
                    clearInterval(simulatedLocationInterval);
                    simulatedLocationInterval = null;
                }

                // Reiniciar geolocalización real
                updateLocation();
            }
        });

        // Event listener para clics en el mapa (ubicación simulada)
        map.on('click', function(e) {
            if (isSimulatedMode) {
                // Bloquear cambios de ubicación si hay viaje activo
                if (hasTripActive) {
                    alert('No puedes cambiar la ubicación mientras hay un viaje activo');
                    return;
                }

                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                // Guardar ubicación personalizada
                customSimulatedLocation = { lat, lng, accuracy: 20 };

                console.log('📍 Nueva ubicación simulada establecida:', customSimulatedLocation);

                // Detener intervalo anterior si existe
                if (simulatedLocationInterval) {
                    clearInterval(simulatedLocationInterval);
                    simulatedLocationInterval = null;
                }

                // Iniciar con la nueva ubicación
                useSimulatedLocationDriver();

                // Agregar un marcador temporal para mostrar dónde se hizo clic
                L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#3b82f6',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map).bindPopup('Ubicación simulada establecida').openPopup();
            }
        });

        // Iniciar geolocalización en paralelo (no bloquea)
        updateLocation();

        // Limpiar watch al salir de la página
        window.addEventListener('beforeunload', function() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                console.log('✓ Rastreo de ubicación detenido');
            }
            if (simulatedLocationInterval) {
                clearInterval(simulatedLocationInterval);
                console.log('✓ Ubicación simulada detenida');
            }
        });

        } // Cierre de initMap()

        // Iniciar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }
    </script>
</x-app-layout>
