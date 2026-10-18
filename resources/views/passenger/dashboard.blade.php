<x-passenger-layout :fullscreen="true" title="Ubicaditos · Mapa" active="Mapa">
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin="">
        <style>
            html, body { height: 100%; overflow: hidden; }
            #map { background: #e8edf8; }
            #map .leaflet-tile-pane { filter: saturate(.3) brightness(1.08) contrast(.88); opacity: .8; }
            .leaflet-popup-content-wrapper { border-radius: 16px; box-shadow: 0 12px 30px rgba(60, 70, 110, .25); }
            .leaflet-bottom.leaflet-left { margin-bottom: 4px; }
            .panel-scroll::-webkit-scrollbar { width: 6px; }
            .panel-scroll::-webkit-scrollbar-thumb { background: rgba(75, 90, 133, .25); border-radius: 9999px; }
        </style>
    </x-slot>

    <div class="relative h-full w-full overflow-hidden">

        <div id="map" class="absolute inset-0 isolate"></div>


        <div class="absolute inset-x-4 sm:inset-x-6 top-[6.25rem] bottom-6 z-[1000] pointer-events-none flex flex-col lg:flex-row lg:justify-between gap-3 max-lg:overflow-y-auto">

            <div class="flex flex-col gap-3 lg:w-[300px] lg:min-h-0">

                <div class="pointer-events-auto relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                        <path stroke-linecap="round" stroke-width="2" d="M20 20l-3.5-3.5"></path>
                    </svg>
                    <input type="text" placeholder="Buscar parada o ruta"
                           class="w-full pl-10 pr-12 py-3 rounded-full bg-white/80 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg text-sm text-slate-700 dark:text-gray-100 placeholder-slate-400 focus:ring-2 focus:ring-[#6b7aa8]/40 focus:border-transparent">

                    <button type="button" data-toggle="filters-panel" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100/80" aria-label="Filtros">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M6 12h12M10 18h4"></path>
                        </svg>
                    </button>

                    <div id="filters-panel" data-popover class="hidden absolute left-0 right-0 top-[calc(100%+8px)] z-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-xl rounded-2xl p-4 space-y-3">
                        <div>
                            <label for="route-filter" class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">Filtrar por ruta</label>
                            <select id="route-filter" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-xl text-sm text-slate-700 dark:text-gray-100 focus:ring-2 focus:ring-[#6b7aa8]/40 focus:border-transparent">
                                <option value="">Todas las rutas</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="show-stops" checked class="w-4 h-4 mr-2 rounded border-slate-300 text-[#4b5a85] focus:ring-[#6b7aa8]">
                            <label for="show-stops" class="text-sm text-slate-700 dark:text-gray-300 cursor-pointer">Mostrar paradas</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="show-nearest-routes" class="w-4 h-4 mr-2 rounded border-slate-300 text-[#4b5a85] focus:ring-[#6b7aa8] dark:bg-gray-700 dark:border-gray-600">
                            <label for="show-nearest-routes" class="text-sm text-slate-700 dark:text-gray-300 cursor-pointer">Mostrar solo rutas cercanas</label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 px-3 py-2 bg-green-50/80 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span id="fleet-counter" class="text-xs font-medium text-green-700 dark:text-green-300" data-base="{{ $buses->count() }} autobuses en servicio">{{ $buses->count() }} autobuses en servicio</span>
                            <span id="realtime-status" class="text-[11px] font-medium text-green-600/70 dark:text-green-400/70"></span>
                        </div>

                        <div id="gps-overlay" class="text-xs space-y-0.5">
                            <div class="flex items-center">
                                <span id="gps-indicator" class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                <span id="gps-status-text" class="font-medium text-slate-600 dark:text-gray-300">GPS Inactivo</span>
                            </div>
                            <div id="gps-details" class="hidden space-y-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                <div>Última actualización: <span id="last-update-passenger">--</span></div>
                                <div>Precisión: <span id="location-accuracy">--</span>m</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="nearest-routes-container" class="pointer-events-auto bg-white/75 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg rounded-2xl p-4 lg:min-h-0 lg:overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#4b5a85]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="3" stroke-width="2"></circle>
                                <circle cx="12" cy="12" r="8" stroke-width="2"></circle>
                                <path stroke-linecap="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3"></path>
                            </svg>
                            Rutas más cercanas
                            <span class="text-[11px] font-normal text-slate-400">5 km</span>
                        </h3>
                        <button type="button" data-collapse-target="nearest-routes-body" aria-expanded="true" aria-label="Minimizar panel" class="w-7 h-7 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100/80 dark:hover:bg-gray-700/60 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="nearest-routes-body">
                        <div id="nearest-routes-list" class="space-y-2 overflow-y-auto panel-scroll lg:max-h-[46vh]">
                            <p class="text-xs text-slate-500 dark:text-gray-400">Activa «Mostrar solo rutas cercanas» en los filtros para ver las rutas más cercanas a ti.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:w-[300px] lg:self-start flex flex-col gap-3">
                <div class="pointer-events-auto bg-white/75 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div id="arrival-title" class="text-sm font-semibold text-slate-800 dark:text-gray-100 truncate">Tiempos de llegada</div>
                            <div id="arrival-subtitle" class="text-[11px] text-slate-500 dark:text-gray-400 truncate">Selecciona una parada</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-full bg-white/80 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 flex items-center justify-center text-slate-500 shrink-0" aria-label="Favorito">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </button>
                        <button type="button" data-collapse-target="arrival-body" aria-expanded="true" aria-label="Minimizar panel" class="w-9 h-9 rounded-full bg-white/80 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 flex items-center justify-center text-slate-500 shrink-0 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                    </div>

                    <div id="arrival-body">
                        <div class="border-t border-slate-200/70 dark:border-white/10 my-3"></div>

                        <div class="text-[10px] font-semibold tracking-[.12em] uppercase text-slate-400 mb-2">Próximos autobuses</div>

                        <div id="arrival-times" class="space-y-2 max-h-[40vh] overflow-y-auto panel-scroll">
                            <p class="text-xs text-slate-500 dark:text-gray-400 italic">Haz clic en una parada para ver los tiempos de llegada</p>
                        </div>

                        <button type="button" class="mt-3 w-full flex items-center justify-center gap-2 py-2.5 rounded-full border border-slate-300 dark:border-gray-600 bg-white/60 dark:bg-gray-700/60 text-[13px] font-medium text-[#4b5a85] dark:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            Agregar a favoritos
                        </button>
                    </div>
                </div>

                <div class="pointer-events-auto flex items-center gap-2">
                    <button type="button" id="zoom-in-btn" class="w-10 h-10 rounded-full bg-white/90 dark:bg-gray-700 shadow-lg flex items-center justify-center text-[#4b5a85] dark:text-gray-200 text-xl leading-none" aria-label="Acercar">+</button>
                    <button type="button" id="zoom-out-btn" class="w-10 h-10 rounded-full bg-white/90 dark:bg-gray-700 shadow-lg flex items-center justify-center text-[#4b5a85] dark:text-gray-200 text-xl leading-none" aria-label="Alejar">−</button>
                    <button type="button" id="locate-btn" class="w-11 h-11 rounded-full bg-[#4b5a85] text-white shadow-lg flex items-center justify-center" aria-label="Mi ubicación">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3" stroke-width="2"></circle>
                            <circle cx="12" cy="12" r="8" stroke-width="2"></circle>
                            <path stroke-linecap="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Capa de carga: difumina el mapa mientras se traza el elemento consultado -->
        <div id="map-loading" class="hidden absolute inset-0 z-[2000] pointer-events-auto flex items-center justify-center">
            <div class="absolute inset-0 bg-white/40 dark:bg-gray-900/50 backdrop-blur-md"></div>
            <div class="relative bg-white/85 dark:bg-gray-800/85 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-xl rounded-2xl px-5 py-4 flex items-center gap-3" role="status" aria-live="polite">
                <svg class="w-5 h-5 text-[#4b5a85] animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <div>
                    <div id="map-loading-title" class="text-[13px] font-semibold text-slate-800 dark:text-gray-100">Cargando mapa…</div>
                    <div id="map-loading-sub" class="text-[11px] text-slate-500 dark:text-gray-400">Preparando rutas y autobuses</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
            defer></script>

    <script defer>
        function initMap() {
            if (typeof L === 'undefined') {
                setTimeout(initMap, 50);
                return;
            }

        const map = L.map('map', { zoomControl: false }).setView([17.0654, -96.7256], 13);

        map.attributionControl.setPosition('bottomleft');

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        // ===== Controles manuales del mapa (antes eran botones muertos) =====
        document.getElementById('zoom-in-btn').addEventListener('click', () => map.zoomIn());
        document.getElementById('zoom-out-btn').addEventListener('click', () => map.zoomOut());

        document.getElementById('locate-btn').addEventListener('click', function() {
            // Volver a la ubicación del usuario "desancla" el centro del mapa,
            // de modo que puede volver a seguir al GPS.
            centerLocked = false;

            if (userLocationMarker) {
                map.flyTo(userLocationMarker.getLatLng(), 16);
            } else {
                // Sin posición conocida: pide una fix al navegador y centra con ella.
                map.locate({ setView: true, maxZoom: 16 });
            }
        });

        // Leaflet emite estos eventos cuando se llama a map.locate().
        map.on('locationfound', function(e) {
            updateUserLocationMarker(e.latlng.lat, e.latlng.lng, e.accuracy);
        });

        map.on('locationerror', function(e) {
            console.warn('⚠ No se pudo obtener tu ubicación:', e.message);
        });

        // ===== Paneles flotantes colapsables (data-collapse-target) =====
        document.querySelectorAll('[data-collapse-target]').forEach(function(button) {
            const body = document.getElementById(button.dataset.collapseTarget);
            if (!body) return;

            button.addEventListener('click', function() {
                const collapsed = body.classList.toggle('hidden');
                button.classList.toggle('rotate-180', collapsed);
                button.setAttribute('aria-expanded', String(!collapsed));
            });
        });

        // ===== Capa de carga del mapa =====
        // Mientras se trazan los recorridos (puede tardar varios segundos si OSRM
        // tarda), el mapa queda difuminado tras una ventana emergente de carga.
        const mapLoading = document.getElementById('map-loading');

        function showMapLoading(title, sub) {
            document.getElementById('map-loading-title').textContent = title || 'Cargando mapa…';
            document.getElementById('map-loading-sub').textContent = sub || 'Preparando rutas y autobuses';
            mapLoading.classList.remove('hidden');
        }

        function hideMapLoading() {
            mapLoading.classList.add('hidden');
        }

        let busMarkers = {};
        let stopMarkers = {};
        let routePolylines = {};
        let routePathsOSRM = {};
        let simulationIntervals = {};
        const simulationSpeed = 2;
        let routesLoaded = {};
        let currentFilteredRoute = null;
        // Cuando es true el mapa NO sigue al GPS: queda "anclado" al elemento
        // consultado (ruta, parada o autobús llegado por URL ?route_id/?stop_id/?bus_id).
        let centerLocked = false;

        const NEAREST_EMPTY = '<p class="text-xs text-slate-500 dark:text-gray-400">Activa «Mostrar solo rutas cercanas» en los filtros para ver las rutas más cercanas a ti.</p>';

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

        const routes = @json($routes);
        const stops = @json($stops);
        const buses = @json($buses);
        const favoriteStopIds = @json(Auth::user()->favoriteStops->pluck('id'));

        console.log('Autobuses cargados:', buses.length);
        console.log('Rutas cargadas:', routes.length);

        let userLocationWatchId = null;
        let simulatedLocationInterval = null;

        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        function updateUserLocationMarker(userLat, userLng, accuracy) {
            const displayAccuracy = Math.min(Math.round(accuracy), 100);
            const circleRadius = Math.min(accuracy, 100);

            if (userLocationMarker) {
                map.removeLayer(userLocationMarker);
            }
            if (userLocationCircle) {
                map.removeLayer(userLocationCircle);
            }

            userLocationMarker = L.marker([userLat, userLng], {
                icon: userLocationIcon,
                zIndexOffset: 2000
            }).addTo(map).bindPopup(`
                <div class="font-semibold text-blue-600">📍 Tu ubicación</div>
                <div class="text-xs text-gray-600 mt-1">Lat: ${userLat.toFixed(6)}</div>
                <div class="text-xs text-gray-600">Lng: ${userLng.toFixed(6)}</div>
                <div class="text-xs text-gray-500 mt-1">Precisión: ${displayAccuracy}m</div>
            `);

            userLocationCircle = L.circle([userLat, userLng], {
                color: '#4b5a85',
                fillColor: '#6b7aa8',
                fillOpacity: 0.1,
                weight: 1,
                radius: circleRadius
            }).addTo(map);
        }

        function useSimulatedLocation() {
            console.log('🎯 Usando ubicación GPS simulada:', SIMULATED_LOCATION);

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

            function updateSimulatedLocation() {
                const lastUpdate = document.getElementById('last-update-passenger');
                const accuracyElement = document.getElementById('location-accuracy');

                if (lastUpdate) {
                    lastUpdate.textContent = new Date().toLocaleTimeString();
                }
                if (accuracyElement) {
                    accuracyElement.textContent = SIMULATED_LOCATION.accuracy;
                }

                updateUserLocationMarker(SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng, SIMULATED_LOCATION.accuracy);
            }

            updateSimulatedLocation();

            if (!centerLocked) {
                map.flyTo([SIMULATED_LOCATION.lat, SIMULATED_LOCATION.lng], 15);
            }

            simulatedLocationInterval = setInterval(updateSimulatedLocation, 2000);

            console.log('✅ Ubicación GPS simulada activa');
        }

        function getUserLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                    useSimulatedLocation();
                    resolve({ lat: SIMULATED_LOCATION.lat, lng: SIMULATED_LOCATION.lng });
                    return;
                }

                console.log('🔍 Iniciando rastreo de ubicación del usuario...');

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
                            const displayAccuracy = Math.min(Math.round(accuracy), 100);
                            accuracyElement.textContent = displayAccuracy;
                        }

                        updateUserLocationMarker(userLat, userLng, accuracy);

                        // El mapa se centra en el GPS solo la primera vez, y nunca
                        // si hay un elemento objetivo anclado (centerLocked).
                        if (resolve) {
                            if (!centerLocked) {
                                map.flyTo([userLat, userLng], 15);
                                console.log('✓ Mapa centrado en ubicación del usuario');
                            }

                            const tempResolve = resolve;
                            resolve = null;
                            tempResolve({ lat: userLat, lng: userLng });
                        }
                    },
                    function(error) {
                        console.warn('⚠ Error al obtener ubicación GPS:', {
                            code: error.code,
                            message: error.message
                        });

                        console.log('🔄 Cambiando a ubicación GPS simulada...');
                        useSimulatedLocation();
                        resolve({ lat: SIMULATED_LOCATION.lat, lng: SIMULATED_LOCATION.lng });
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 2000
                    }
                );
            });
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

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function calculateDistanceAlongRoute(routePath, fromIndex, toPosition) {
            let distance = 0;
            let minDistToStop = Infinity;
            let closestIndex = fromIndex;

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

            const currentIdx = Math.floor(fromIndex);
            if (closestIndex < currentIdx) {
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

        function calculateArrivalTime(stop) {
            const arrivalTimes = [];

            simulatedBuses.forEach(bus => {
                const busRoute = bus.route;
                const stopInRoute = busRoute.stops && busRoute.stops.find(s => s.id === stop.id);

                if (stopInRoute && bus.routePath) {
                    const distanceAlongRoute = calculateDistanceAlongRoute(
                        bus.routePath,
                        bus.pathIndex,
                        { lat: stop.latitude, lng: stop.longitude }
                    );

                    const avgSpeed = bus.avgSpeedKmh || 20;

                    const timeInMinutes = (distanceAlongRoute / avgSpeed) * 60;

                    const stopsUntilDestination = Math.floor(distanceAlongRoute * 2);
                    const stopTimeMinutes = (stopsUntilDestination * 0.5);

                    const totalTime = timeInMinutes + stopTimeMinutes;

                    arrivalTimes.push({
                        bus: bus,
                        distance: distanceAlongRoute,
                        time: Math.ceil(totalTime),
                        route: busRoute
                    });
                }
            });

            arrivalTimes.sort((a, b) => a.time - b.time);

            return arrivalTimes;
        }

        function buildBusRow(color, title, subtitle, timeText) {
            return `
                <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-white/60 dark:bg-gray-700/60">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white shrink-0" style="background-color: ${color}">
                        ${BUS_SVG(16)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 truncate">${title}</div>
                        <div class="text-[11px] text-slate-500 dark:text-gray-400 truncate">${subtitle}</div>
                    </div>
                    <div class="text-[15px] font-bold text-[#3f4c75] dark:text-blue-300 whitespace-nowrap">${timeText}</div>
                </div>
            `;
        }

        function showArrivalTimes(stop) {
            const arrivalTimes = calculateArrivalTime(stop);
            const arrivalTimesDiv = document.getElementById('arrival-times');

            document.getElementById('arrival-title').textContent = stop.name;
            document.getElementById('arrival-subtitle').textContent = stop.address || 'Parada';

            if (arrivalTimes.length === 0) {
                arrivalTimesDiv.innerHTML = `
                    <p class="text-xs text-slate-500 dark:text-gray-400 italic">
                        No hay autobuses en ruta hacia esta parada
                    </p>
                `;
                return;
            }

            let html = '';

            arrivalTimes.forEach(arrival => {
                const timeText = arrival.time < 1 ? '< 1 min' : `${arrival.time} min`;

                html += buildBusRow(
                    arrival.route.color || '#4b5a85',
                    arrival.bus.license_plate,
                    `A ${arrival.distance.toFixed(1)} km · ${arrival.route.name}`,
                    timeText
                );
            });

            arrivalTimesDiv.innerHTML = html;
        }

        function showRouteInfo(route) {
            const arrivalTimesDiv = document.getElementById('arrival-times');

            document.getElementById('arrival-title').textContent = route.name;
            document.getElementById('arrival-subtitle').textContent = route.code || 'Ruta';

            const routeBuses = simulatedBuses.filter(bus => bus.route.id === route.id);

            if (routeBuses.length === 0) {
                arrivalTimesDiv.innerHTML = `
                    <p class="text-xs text-slate-500 dark:text-gray-400 italic">
                        No hay autobuses activos en esta ruta
                    </p>
                `;
                return;
            }

            const routeStops = route.stops ? route.stops.sort((a, b) => {
                const aOrder = a.pivot?.order || 0;
                const bOrder = b.pivot?.order || 0;
                return aOrder - bOrder;
            }) : [];

            let html = '';

            routeBuses.forEach((bus, index) => {
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

                    const timeText = totalTime < 1 ? '< 1 min' : `${totalTime} min`;

                    html += buildBusRow(
                        route.color || '#4b5a85',
                        `${bus.license_plate} · ${bus.model}`,
                        `Próxima parada: ${nextStop.name}`,
                        timeText
                    );
                } else {
                    html += buildBusRow(
                        route.color || '#4b5a85',
                        `${bus.license_plate} · ${bus.model}`,
                        'Calculando próxima parada...',
                        '--'
                    );
                }
            });

            arrivalTimesDiv.innerHTML = html;
        }

        let simulatedBuses = [];

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
                    console.log(`[${route.name}] Intento ${attempt}/3 de cargar ruta sobre calles...`);

                    const response = await fetchWithTimeout(
                        `https://router.project-osrm.org/route/v1/driving/${waypoints}?overview=full&geometries=geojson`,
                        15000
                    );
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
                        }).addTo(map);

                        polyline.bindPopup(`
                            <div class="font-semibold">${route.name}</div>
                            <div class="text-sm text-gray-600">${route.code}</div>
                            <div class="text-xs mt-1">${route.description}</div>
                        `);

                        polyline.on('click', function() {
                            showRouteInfo(route);
                        });

                        routePolylines[route.id] = polyline;
                        routesLoaded[route.id] = true;
                        console.log(`✅ [${route.name}] Ruta cargada sobre calles correctamente`);
                        return;
                    } else {
                        console.warn(`⚠ [${route.name}] OSRM retornó: ${data.code}`);
                    }
                } catch (error) {
                    console.warn(`⚠ [${route.name}] Error en intento ${attempt}:`, error.message);
                }

                if (attempt < 3) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }

            console.error(`❌ [${route.name}] No se pudo cargar sobre calles después de 3 intentos - LA RUTA NO SE MOSTRARÁ`);
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

                const routeDistanceKm = calculateRouteDistance(routePath);

                const avgSpeedKmh = 20;

                const updateIntervalSeconds = 0.2;

                const distancePerUpdate = (avgSpeedKmh * updateIntervalSeconds) / 3600;

                const speedIncrement = (distancePerUpdate / routeDistanceKm) * routePath.length;

                const startProgress = (index * 0.3) % 1;
                const pathIndex = Math.floor(routePath.length * startProgress);

                // Si el servidor reporta una posición real del autobús, se usa esa
                // coordenada y el marcador deja de moverse por simulación (recibe
                // actualizaciones en tiempo real en su lugar).
                const hasRealPosition = bus.current_latitude != null && bus.current_longitude != null;
                const startPoint = hasRealPosition
                    ? [Number(bus.current_latitude), Number(bus.current_longitude)]
                    : routePath[pathIndex];

                console.log(`Autobús ${bus.license_plate}: Ruta ${routeDistanceKm.toFixed(2)}km, ${hasRealPosition ? 'posición real' : 'simulada'}`);

                return {
                    id: bus.id,
                    license_plate: bus.license_plate,
                    model: bus.model,
                    capacity: bus.capacity,
                    current_passengers: bus.current_passengers ?? 0,
                    route: fullRoute,
                    routePath: routePath,
                    routeDistanceKm: routeDistanceKm,
                    speedIncrement: speedIncrement,
                    avgSpeedKmh: avgSpeedKmh,
                    isReal: hasRealPosition,
                    currentPosition: {
                        lat: startPoint[0],
                        lng: startPoint[1]
                    },
                    pathIndex: pathIndex,
                    direction: 1
                };
            }).filter(bus => bus !== null);

            console.log('✅ Autobuses simulados inicializados con velocidades realistas:', simulatedBuses.length);
        }

        function drawStops() {
            stops.forEach(stop => {
                if (!stopMarkers[stop.id]) {
                    const isFavorite = favoriteStopIds.includes(stop.id);
                    const favoriteButton = isFavorite
                        ? `<button onclick="removeFromFavorites(${stop.id})"
                                class="mt-2 px-3 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500"
                                id="favorite-btn-${stop.id}">
                                ✓ En favoritos
                            </button>`
                        : `<button onclick="addToFavorites(${stop.id}, '${stop.name.replace(/'/g, "\\'")}')"
                                class="mt-2 px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600"
                                id="favorite-btn-${stop.id}">
                                ⭐ Agregar a favoritos
                            </button>`;

                    const marker = L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                        .addTo(map)
                        .bindPopup(`
                            <div class="font-semibold">${stop.name}</div>
                            <div class="text-sm text-gray-600">${stop.address || ''}</div>
                            <button onclick="showArrivalTimesForStop(${stop.id})"
                                    class="mt-2 mr-1 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                                🕐 Ver tiempos de llegada
                            </button>
                            ${favoriteButton}
                        `);
                    stopMarkers[stop.id] = marker;
                }
            });
        }

        window.showArrivalTimesForStop = function(stopId) {
            const stop = stops.find(s => s.id === stopId);
            if (stop) {
                showArrivalTimes(stop);
            }
        };

        function drawBuses() {
            simulatedBuses.forEach(bus => {
                const available = bus.capacity;

                if (busMarkers[bus.id]) {
                    busMarkers[bus.id].setLatLng([bus.currentPosition.lat, bus.currentPosition.lng]);
                    busMarkers[bus.id].setPopupContent(`
                        <div class="font-semibold text-lg">🚌 ${bus.license_plate}</div>
                        <div class="text-sm text-gray-600">${bus.model}</div>
                        <div class="mt-2 text-sm">
                            <div><strong>Ruta:</strong> ${bus.route.name}</div>
                            <div><strong>Capacidad:</strong> ${bus.capacity} pasajeros</div>
                            <div><strong>Ocupación:</strong> ${bus.current_passengers ?? 0}/${bus.capacity}</div>
                        </div>
                    `);
                } else {
                    const marker = L.marker([bus.currentPosition.lat, bus.currentPosition.lng], {
                        icon: busIcon,
                        zIndexOffset: 1000
                    })
                        .addTo(map)
                        .bindPopup(`
                            <div class="font-semibold text-lg">🚌 ${bus.license_plate}</div>
                            <div class="text-sm text-gray-600">${bus.model}</div>
                            <div class="mt-2 text-sm">
                                <div><strong>Ruta:</strong> ${bus.route.name}</div>
                                <div><strong>Capacidad:</strong> ${bus.capacity} pasajeros</div>
                                <div><strong>Ocupación:</strong> ${bus.current_passengers ?? 0}/${bus.capacity}</div>
                            </div>
                        `);
                    busMarkers[bus.id] = marker;
                    console.log('Autobús dibujado:', bus.license_plate, 'en', bus.currentPosition);
                }
            });
        }

        function moveBus(bus) {
            // Los buses con posición real no se simulan: los mueve el tiempo real.
            if (bus.isReal || !bus.routePath || bus.routePath.length === 0) return;

            const speed = bus.speedIncrement || 0.02;
            bus.pathIndex += bus.direction * speed;

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

        // ===== Tiempo real (WebSocket con fallback a polling) =====
        // Mueve los marcadores con la posición reportada por los conductores.
        function startRealtimeUpdates() {
            if (!window.UbicaditosRealtime) {
                console.warn('⚠ Módulo de tiempo real no disponible; solo simulación local.');
                return;
            }

            const realtime = window.UbicaditosRealtime.start({ pollInterval: 5000 });

            realtime.onBusLocation(payload => {
                const bus = simulatedBuses.find(b => b.id === payload.id);
                if (!bus) return;

                bus.isReal = true;

                if (simulationIntervals[bus.id]) {
                    clearInterval(simulationIntervals[bus.id]);
                    delete simulationIntervals[bus.id];
                }

                bus.currentPosition = { lat: payload.latitude, lng: payload.longitude };

                if (payload.current_passengers != null) {
                    bus.current_passengers = payload.current_passengers;
                }

                if (busMarkers[bus.id]) {
                    busMarkers[bus.id].setLatLng([payload.latitude, payload.longitude]);
                    drawBuses(); // refresca el popup con la ocupación más reciente
                } else {
                    drawBuses();
                }
            });

            realtime.onStatus(status => {
                const statusEl = document.getElementById('realtime-status');
                if (!statusEl) return;
                statusEl.textContent = status === 'websocket'
                    ? '· en vivo'
                    : (status === 'polling' ? '· actualizando…' : '· sin conexión en vivo');
            });

            window.addEventListener('beforeunload', () => realtime.stop());
        }

        // ===== Búsqueda en el mapa (paradas y autobuses) =====
        function setMarkerVisible(marker, visible) {
            if (!marker) return;
            if (visible && !map.hasLayer(marker)) marker.addTo(map);
            if (!visible && map.hasLayer(marker)) map.removeLayer(marker);
        }

        function wireMapSearch() {
            const searchInput = document.querySelector('input[placeholder="Buscar parada o ruta"]');
            if (!searchInput) return;

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim().toLowerCase();

                let busesShown = 0;
                simulatedBuses.forEach(bus => {
                    const matches = !query
                        || bus.license_plate.toLowerCase().includes(query)
                        || bus.route.name.toLowerCase().includes(query);
                    setMarkerVisible(busMarkers[bus.id], matches);
                    if (matches) busesShown++;
                });

                stops.forEach(stop => {
                    const matches = !query
                        || stop.name.toLowerCase().includes(query)
                        || (stop.address || '').toLowerCase().includes(query);
                    setMarkerVisible(stopMarkers[stop.id], matches);
                });

                const counter = document.getElementById('fleet-counter');
                if (counter) {
                    counter.textContent = query
                        ? `${busesShown} autobuses coinciden`
                        : (counter.dataset.base || counter.textContent);
                }
            });
        }

        window.showNearestRoutes = async function() {
            console.log('🔍 Buscando rutas más cercanas...');

            if (!userLocationMarker) {
                alert('⚠ Primero necesitamos obtener tu ubicación. Por favor, permite el acceso a tu ubicación en el navegador.');
                try {
                    await getUserLocation();
                } catch (error) {
                    console.error('Error al obtener ubicación:', error);
                    return;
                }
            }

            const userPos = userLocationMarker.getLatLng();
            const userLat = userPos.lat;
            const userLng = userPos.lng;

            console.log('📍 Ubicación del usuario:', userLat, userLng);

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

            const nearestRoutes = routeDistances.filter(r => r.distance <= 5).slice(0, 3);

            if (nearestRoutes.length === 0) {
                alert('No se encontraron rutas cercanas (dentro de 5km).');
                return;
            }

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
                const nearestRouteIds = nearestRoutes.map(r => r.route.id);

                for (const routeId of nearestRouteIds) {
                    const route = routes.find(r => r.id === routeId);
                    if (route && !routesLoaded[routeId]) {
                        await drawRoute(route);
                    }
                }

                Object.keys(routePolylines).forEach(key => {
                    const rId = parseInt(key);
                    if (nearestRouteIds.includes(rId)) {
                        routePolylines[key].addTo(map);
                    } else {
                        map.removeLayer(routePolylines[key]);
                    }
                });

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

        window.addToFavorites = function(stopId, stopName) {
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
                        if (!favoriteStopIds.includes(stopId)) {
                            favoriteStopIds.push(stopId);
                        }
                        updateStopPopup(stopId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('✗ Error: ' + (error.error || error.message || 'No se pudo agregar a favoritos'));
                });
            }
        };

        window.removeFromFavorites = function(stopId) {
            if (confirm('¿Quitar esta parada de favoritos?')) {
                fetch(`/passenger/favorites/${stopId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const index = favoriteStopIds.indexOf(stopId);
                        if (index > -1) {
                            favoriteStopIds.splice(index, 1);
                        }
                        updateStopPopup(stopId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('✗ Error: No se pudo eliminar de favoritos');
                });
            }
        };

        function updateStopPopup(stopId) {
            const stop = stops.find(s => s.id === stopId);
            if (stop && stopMarkers[stopId]) {
                const isFavorite = favoriteStopIds.includes(stopId);
                const favoriteButton = isFavorite
                    ? `<button onclick="removeFromFavorites(${stopId})"
                            class="mt-2 px-3 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500"
                            id="favorite-btn-${stopId}">
                            ✓ En favoritos
                        </button>`
                    : `<button onclick="addToFavorites(${stopId}, '${stop.name.replace(/'/g, "\\'")}')"
                            class="mt-2 px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600"
                            id="favorite-btn-${stopId}">
                            ⭐ Agregar a favoritos
                        </button>`;

                stopMarkers[stopId].setPopupContent(`
                    <div class="font-semibold">${stop.name}</div>
                    <div class="text-sm text-gray-600">${stop.address || ''}</div>
                    <button onclick="showArrivalTimesForStop(${stopId})"
                            class="mt-2 mr-1 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                        🕐 Ver tiempos de llegada
                    </button>
                    ${favoriteButton}
                `);
            }
        }

        document.getElementById('show-stops').addEventListener('change', function(e) {
            Object.values(stopMarkers).forEach(marker => {
                if (e.target.checked) {
                    marker.addTo(map);
                } else {
                    map.removeLayer(marker);
                }
            });
        });

        let currentNearestRoutes = [];

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

        function showNearestRoutesInContainer(nearestRoutes) {
            const listDiv = document.getElementById('nearest-routes-list');

            if (nearestRoutes.length === 0) {
                listDiv.innerHTML = '<p class="text-xs text-slate-500 dark:text-gray-400">No se encontraron rutas cercanas (dentro de 5km).</p>';
                return;
            }

            let html = '';
            nearestRoutes.forEach((item, index) => {
                const distanceKm = item.distance.toFixed(2);
                const distanceM = Math.round(item.distance * 1000);
                const distanceText = item.distance < 1 ? `${distanceM} m` : `${distanceKm} km`;
                const routeColor = item.route.color || '#4b5a85';

                html += `
                    <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-white/60 dark:bg-gray-700/60">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: ${routeColor}">
                            ${BUS_SVG(18)}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 truncate">${item.route.name}</div>
                            <div class="text-[11px] text-slate-500 dark:text-gray-400 truncate">${item.route.code} · ${item.closestStop.name}</div>
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-gray-600 text-[11px] font-semibold text-slate-600 dark:text-gray-100 whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: ${routeColor}"></span>
                            ${distanceText}
                        </div>
                    </div>
                `;
            });

            listDiv.innerHTML = html;
        }

        async function filterNearestRoutes(show) {
            if (!show) {
                Object.values(routePolylines).forEach(polyline => polyline.addTo(map));
                simulatedBuses.forEach(bus => {
                    if (busMarkers[bus.id]) {
                        busMarkers[bus.id].addTo(map);
                    }
                });
                document.getElementById('nearest-routes-list').innerHTML = NEAREST_EMPTY;
                currentNearestRoutes = [];
                return;
            }

            const nearestRoutes = calculateNearestRoutes();
            currentNearestRoutes = nearestRoutes;

            if (nearestRoutes.length === 0) {
                showNearestRoutesInContainer([]);
                hideMapLoading();
                return;
            }

            showNearestRoutesInContainer(nearestRoutes);
            showMapLoading('Buscando rutas cercanas…', 'Calculando distancias y trazados');

            const nearestRouteIds = nearestRoutes.map(r => r.route.id);

            const routePromises = nearestRouteIds.map(async routeId => {
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            });
            await Promise.all(routePromises);

            Object.keys(routePolylines).forEach(key => {
                const rId = parseInt(key);
                if (nearestRouteIds.includes(rId)) {
                    routePolylines[key].addTo(map);
                } else {
                    map.removeLayer(routePolylines[key]);
                }
            });

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
            hideMapLoading();
        }

        document.getElementById('show-nearest-routes').addEventListener('change', function(e) {
            filterNearestRoutes(e.target.checked);
        });

        // Aplica el filtro de ruta: carga su trazado si hace falta, la aísla de
        // las demás rutas y encuadra el mapa en ella con fitBounds.
        async function applyRouteFilter(routeId, { focus = true, loading = true } = {}) {
            currentFilteredRoute = routeId;

            if (routeId && loading) {
                showMapLoading('Trazando la ruta…', 'Consultando el recorrido sobre las calles');
            }

            if (routeId) {
                const route = routes.find(r => r.id === routeId);
                if (route && !routesLoaded[routeId]) {
                    await drawRoute(route);
                }
            }

            // Aislamiento: solo la ruta filtrada permanece visible en el mapa.
            Object.keys(routePolylines).forEach(key => {
                const rId = parseInt(key);
                if (!routeId || rId === routeId) {
                    routePolylines[key].addTo(map);
                } else {
                    map.removeLayer(routePolylines[key]);
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            const busIdFromUrl = urlParams.get('bus_id');
            const specificBusId = busIdFromUrl ? parseInt(busIdFromUrl) : null;

            simulatedBuses.forEach(bus => {
                let shouldShow = false;

                if (specificBusId) {
                    shouldShow = bus.id === specificBusId;
                } else {
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

            // Encuadre completo de la ruta consultada (ancla el centro del mapa).
            if (routeId && focus && routePolylines[routeId]) {
                centerLocked = true;
                map.fitBounds(routePolylines[routeId].getBounds(), { padding: [40, 40], maxZoom: 16 });
            }

            if (routeId && loading) {
                hideMapLoading();
            }
        }

        document.getElementById('route-filter').addEventListener('change', function(e) {
            applyRouteFilter(parseInt(e.target.value) || null);
        });

        console.log('🚀 Inicializando sistema de autobuses...');
        console.log('Rutas disponibles:', routes.length);
        console.log('Autobuses disponibles:', buses.length);
        console.log('Paradas disponibles:', stops.length);

        (async function init() {
            console.log('🚀 Iniciando sistema de mapas...');

            const urlParams = new URLSearchParams(window.location.search);

            // Elemento enlazado desde otra vista: ?route_id, ?stop_id o ?bus_id.
            // Si llega alguno, el centro queda anclado y el GPS no mueve el mapa.
            const target = {
                bus: urlParams.get('bus_id'),
                stop: urlParams.get('stop_id'),
                route: urlParams.get('route_id')
            };

            const hasTarget = !!(target.bus || target.stop || target.route);
            centerLocked = hasTarget;

            // Ventana de carga: tapa el "parpadeo" en el que se trazan todas las
            // rutas y solo al final queda aislada la que se consultó.
            showMapLoading(
                hasTarget ? 'Buscando en el mapa…' : 'Cargando mapa…',
                target.route ? 'Trazando la ruta consultada…'
                    : target.bus ? 'Localizando el autobús…'
                    : target.stop ? 'Localizando la parada…'
                    : 'Preparando rutas y autobuses'
            );

            try {
                const stopsPromise = Promise.resolve(drawStops());
                const routesPromise = loadVisibleRoutes();
                const locationPromise = getUserLocation().catch(err => {
                    console.warn('⚠ Continuando sin ubicación del usuario:', err.message);
                    return null;
                });

                await Promise.all([stopsPromise, routesPromise, locationPromise]);

                console.log('✅ Mapa base cargado');

                initializeSimulatedBuses();
                drawBuses();
                startSimulation();
                startRealtimeUpdates();
                wireMapSearch();

                console.log('✅ Sistema listo y en funcionamiento!');
                console.log('💡 Las rutas se cargan automáticamente al seleccionarlas en el filtro');

                // El contenedor puede cambiar de tamaño al terminar de cargar el layout.
                map.invalidateSize();

                // 1) Ruta consultada: aislarla de las demás y encuadrarla completa (fitBounds).
                //    La capa de carga la controla init(), por eso { loading: false }.
                if (target.route) {
                    console.log('📍 Enfocando ruta seleccionada:', target.route);
                    await applyRouteFilter(parseInt(target.route), { loading: false });
                }

                // 2) Parada consultada: volar hacia ella y abrir su popup.
                if (target.stop) {
                    const selectedStop = stops.find(s => s.id === parseInt(target.stop));
                    if (selectedStop && stopMarkers[selectedStop.id]) {
                        map.flyTo([selectedStop.latitude, selectedStop.longitude], 16, { duration: 1.2 });
                        stopMarkers[selectedStop.id].openPopup();
                        console.log('🚏 Parada seleccionada centrada:', selectedStop.name);
                    }
                }

                // 3) Autobús consultado: volar hacia él y abrir sus detalles (popup).
                if (target.bus) {
                    const selectedBus = simulatedBuses.find(b => b.id === parseInt(target.bus));
                    if (selectedBus && busMarkers[selectedBus.id]) {
                        map.flyTo([selectedBus.currentPosition.lat, selectedBus.currentPosition.lng], 16, { duration: 1.2 });
                        busMarkers[selectedBus.id].openPopup();
                        console.log('🚌 Autobús seleccionado centrado:', selectedBus.license_plate);
                    }
                }
            } finally {
                // Siempre se oculta, aunque algo falle: el mapa no queda bloqueado.
                hideMapLoading();
            }
        })();

        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }
    </script>
</x-passenger-layout>