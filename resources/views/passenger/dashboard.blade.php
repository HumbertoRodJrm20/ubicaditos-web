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
                    <button type="button" id="share-location-btn" class="w-11 h-11 rounded-full bg-white/90 dark:bg-gray-700 shadow-lg flex items-center justify-center text-[#4b5a85] dark:text-gray-200 border border-white/70 dark:border-white/10" aria-label="Compartir mi ubicación" title="Compartir mi ubicación">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="18" cy="5" r="2.5" stroke-width="1.8"></circle>
                            <circle cx="6" cy="12" r="2.5" stroke-width="1.8"></circle>
                            <circle cx="18" cy="19" r="2.5" stroke-width="1.8"></circle>
                            <path stroke-linecap="round" stroke-width="1.8" d="m8.2 10.8 7.6-4.3M8.2 13.2l7.6 4.3"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel para compartir ubicación -->
        <div id="share-location-panel" class="hidden absolute inset-x-4 bottom-4 z-[1600] rounded-3xl border border-white/70 bg-white/90 p-5 shadow-2xl shadow-[#4a5a8a]/25 backdrop-blur-xl dark:border-white/10 dark:bg-gray-800/90 sm:inset-x-auto sm:right-6 sm:w-[380px]" role="dialog" aria-modal="false" aria-labelledby="share-panel-title">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="18" cy="5" r="2.5" stroke-width="1.8"></circle><circle cx="6" cy="12" r="2.5" stroke-width="1.8"></circle><circle cx="18" cy="19" r="2.5" stroke-width="1.8"></circle><path stroke-linecap="round" stroke-width="1.8" d="m8.2 10.8 7.6-4.3M8.2 13.2l7.6 4.3"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 id="share-panel-title" class="text-sm font-bold text-[#1e2a4a] dark:text-gray-100">Compartir mi ubicación</h2>
                        <p class="text-[11px] text-[#5b6788] dark:text-gray-400">Enlace temporal y en vivo</p>
                    </div>
                </div>
                <button type="button" data-share-close class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100/80 dark:hover:bg-gray-700" aria-label="Cerrar panel">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div id="share-inactive" class="mt-4">
                <p class="text-xs text-[#5b6788] dark:text-gray-300">
                    Genera un enlace privado para que alguien pueda ver tu posición durante los próximos 30 minutos. Puedes detenerlo cuando quieras.
                </p>
                <button type="button" id="share-start-btn" class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#7b88b8] to-[#4a5a8a] text-sm font-semibold text-white shadow-lg shadow-[#4a5a8a]/30 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#6b7bad]/30">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"></path></svg>
                    Activar y generar enlace
                </button>
            </div>

            <div id="share-active" class="mt-4 hidden">
                <label for="share-link" class="block text-[11px] font-medium text-slate-500 dark:text-gray-400">Enlace para compartir</label>
                <div class="mt-1 flex items-center gap-2">
                    <input id="share-link" type="text" readonly class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-[11px] text-slate-600 shadow-sm outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <button type="button" id="share-copy-btn" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-[#4b5a85] hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" aria-label="Copiar enlace">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="1.8"></rect><path stroke-linecap="round" stroke-width="1.8" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3"></path></svg>
                    </button>
                </div>

                <p id="share-expiry" class="mt-2 text-[10px] text-slate-500 dark:text-gray-400"></p>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" id="share-native-btn" class="flex h-10 items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white text-xs font-semibold text-[#4b5a85] transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7M12 3v12m0 0 4-4m-4 4-4-4"></path></svg>
                        Compartir
                    </button>
                    <button type="button" id="share-stop-btn" class="flex h-10 items-center justify-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2" stroke-width="1.8"></rect></svg>
                        Detener
                    </button>
                </div>
            </div>

            <p id="share-feedback" class="mt-3 text-[11px] font-medium text-[#5b6788] dark:text-gray-400" role="status" aria-live="polite"></p>
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

        // Al alejar el mapa se ocultan las paradas secundarias; al acercarse
        // se vuelven a mostrar. Así el mapa no queda lleno de puntos.
        map.on('zoomend moveend', refreshStopVisibility);

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
        // Mientras se preparan los recorridos guardados y los autobuses, el mapa
        // queda difuminado tras una ventana emergente de carga.
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
        let routePaths = {};
        let routesLoaded = {};
        let routeManager = null;
        let currentFilteredRoute = null;
        let stopsVisible = true;
        let activeStopRouteIds = null;
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

        // Marcador compacto para paradas de una sola ruta o poco concurridas.
        // Se usa cuando el zoom está lejos para no saturar el mapa.
        const stopMinorIcon = L.divIcon({
            html: '<div style="background-color: #fff; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #6b7aa8; box-shadow: 0 1px 4px rgba(40,50,90,0.28); opacity: .88;"></div>',
            className: 'stop-marker stop-marker--minor',
            iconSize: [12, 12],
            iconAnchor: [6, 6],
            popupAnchor: [0, -6]
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
        const likedStopIds = new Set(@json($likedStopIds));

        // Contador de likes por parada. Se mantiene en memoria para refrescar
        // el popup sin recargar el mapa.
        const stopLikesById = new Map(
            stops.map(stop => [Number(stop.id), Number(stop.likes_count ?? 0)])
        );

        // Índice parada -> rutas que la usan. Se utiliza para mostrar el
        // recorrido en el popup y para decidir qué paradas conviene enseñar
        // según el zoom o el filtro activo.
        const stopRoutesById = new Map();
        const stopHasPrimaryRouteById = new Map();
        routes.forEach(route => {
            (route.stops || []).forEach(stop => {
                const stopId = Number(stop.id);
                const routeId = Number(route.id);
                const isFrequent = Number(stop.pivot?.is_frequent ?? 0) === 1;

                if (!isFrequent) {
                    stopHasPrimaryRouteById.set(stopId, true);
                }

                if (!stopRoutesById.has(stopId)) {
                    stopRoutesById.set(stopId, []);
                }

                const routeList = stopRoutesById.get(stopId);
                if (!routeList.some(item => Number(item.id) === routeId)) {
                    routeList.push({
                        id: route.id,
                        code: route.code,
                        name: route.name,
                        color: route.color,
                    });
                }
            });
        });

        console.log('Autobuses cargados:', buses.length);
        console.log('Rutas cargadas:', routes.length);

        let userLocationWatchId = null;
        let simulatedLocationInterval = null;
        let latestUserCoordinates = null;

        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

        function updateUserLocationMarker(userLat, userLng, accuracy) {
            latestUserCoordinates = {
                latitude: userLat,
                longitude: userLng,
                accuracy: Math.min(Math.round(accuracy), 65535),
            };

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

            activeBuses.forEach(bus => {
                const busRoute = bus.route;
                const stopInRoute = busRoute.stops && busRoute.stops.find(s => s.id === stop.id);

                if (!stopInRoute) {
                    return;
                }

                const distance = calculateDistance(
                    bus.currentPosition.lat,
                    bus.currentPosition.lng,
                    stop.latitude,
                    stop.longitude
                );

                const avgSpeed = bus.avgSpeedKmh || 20;

                const timeInMinutes = (distance / avgSpeed) * 60;

                const totalTime = timeInMinutes;

                arrivalTimes.push({
                    bus: bus,
                    distance: distance,
                    time: Math.ceil(totalTime),
                    route: busRoute
                });
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

            const routeBuses = activeBuses.filter(bus => bus.route.id === route.id);

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
                    const distance = calculateDistance(
                        bus.currentPosition.lat,
                        bus.currentPosition.lng,
                        stop.latitude,
                        stop.longitude
                    );

                    if (distance < minDistance && distance > 0.05) {
                        minDistance = distance;
                        nextStop = stop;
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

        let activeBuses = [];

        function getRouteManager() {
            if (!routeManager && typeof L !== 'undefined' && window.UbicaditosRouteLayer) {
                routeManager = window.UbicaditosRouteLayer.createMapRoutes({
                    map,
                    routes,
                    onRouteClick: showRouteInfo,
                    options: { sharedColor: '#64748b' }
                });
            }
            return routeManager;
        }

        async function drawRoute(route) {
            if (!route) {
                return null;
            }

            const manager = getRouteManager();
            if (!manager) {
                console.warn('La capa compartida de rutas no está disponible.');
                return null;
            }

            // Si la red ya se dibujó, la línea individual puede no existir aún.
            // `ensureRouteLine` la crea una sola vez y la cachea.
            const polyline = manager.getRouteLine(route.id) || manager.ensureRouteLine(route.id);
            if (!polyline) {
                console.warn('Ruta sin trazado suficiente:', route.name);
                return null;
            }

            routePolylines[route.id] = polyline;
            routePaths[route.id] = manager.getPath(route.id);
            routesLoaded[route.id] = true;

            return polyline;
        }

        async function loadVisibleRoutes() {
            const manager = getRouteManager();
            if (!manager) {
                console.warn('No se pudo inicializar la capa de rutas.');
                return;
            }

            const routeIds = new Set();
            buses.forEach(bus => {
                if (bus.routes && bus.routes[0]) {
                    routeIds.add(bus.routes[0].id);
                }
            });

            // Si no hay autobuses activos, se muestra la red completa.
            const visibleIds = routeIds.size > 0
                ? routeIds
                : new Set(routes.map(route => route.id));

            // Limita las paradas a la red realmente visible. Si están todas
            // las rutas no hace falta filtrar.
            activeStopRouteIds = routeIds.size > 0 && routeIds.size < routes.length
                ? new Set([...visibleIds].map(Number))
                : null;

            // La red ya viene deduplicada por tramos: cada segmento se dibuja
            // una sola vez aunque lo compartan varias rutas.
            manager.showNetwork(visibleIds);
            refreshStopVisibility();

            routes.forEach(route => {
                routePaths[route.id] = manager.getPath(route.id);
                routesLoaded[route.id] = true;
            });

            const stats = manager.stats();
            console.log(
                `🧭 Red de rutas: ${stats.totalSegments} tramos únicos ` +
                `(${stats.sharedSegments} compartidos, ${stats.deduplicatedSegments} dibujos evitados)`
            );
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

        const STALENESS_THRESHOLD_MS = 5 * 60 * 1000; // 5 minutos

        function isLocationStale(lastUpdate) {
            if (!lastUpdate) {
                return true;
            }
            return Date.now() - new Date(lastUpdate).getTime() > STALENESS_THRESHOLD_MS;
        }

        function initializeBuses() {
            // Solo se muestran autobuses que tengan coordenadas reales almacenadas.
            // No se inventan posiciones ni se desplaza ningún autobús artificialmente.
            activeBuses = buses.filter(bus => {
                return bus.current_latitude != null && bus.current_longitude != null;
            }).map(bus => {
                const route = bus.routes && bus.routes[0];
                const fullRoute = route ? routes.find(r => r.id === route.id) : null;
                if (!fullRoute) {
                    console.warn('Ruta no encontrada para el autobús:', bus.license_plate);
                    return null;
                }

                return {
                    id: bus.id,
                    license_plate: bus.license_plate,
                    model: bus.model,
                    capacity: bus.capacity,
                    current_passengers: bus.current_passengers ?? 0,
                    route: fullRoute,
                    currentPosition: {
                        lat: Number(bus.current_latitude),
                        lng: Number(bus.current_longitude)
                    },
                    last_update: bus.last_location_update ?? null,
                    avgSpeedKmh: 20
                };
            }).filter(bus => bus !== null);

            console.log('✅ Autobuses con ubicación real inicializados:', activeBuses.length);
        }

        function routesForStop(stopId) {
            return stopRoutesById.get(Number(stopId)) || [];
        }

        function stopPopupHtml(stop) {
            const stopId = Number(stop.id);
            const isFavorite = favoriteStopIds.includes(stopId);
            const isLiked = likedStopIds.has(stopId);
            const likeCount = stopLikesById.get(stopId) ?? 0;
            const renderer = window.UbicaditosStopPopup?.render;

            const actions = [
                {
                    label: 'Ver tiempos de llegada',
                    icon: 'clock',
                    variant: 'primary',
                    onClick: `showArrivalTimesForStop(${stopId})`,
                },
                {
                    label: `${isLiked ? 'Te gusta' : 'Me gusta'} · ${likeCount}`,
                    icon: 'star',
                    variant: 'ghost',
                    active: isLiked,
                    onClick: `toggleStopLike(${stopId})`,
                },
                {
                    label: isFavorite ? 'Quitar de favoritos' : 'Guardar en favoritos',
                    icon: 'heart',
                    variant: 'ghost',
                    active: isFavorite,
                    onClick: isFavorite
                        ? `removeFromFavorites(${stopId})`
                        : `addToFavorites(${stopId})`,
                },
            ];

            if (typeof renderer === 'function') {
                return renderer(stop, {
                    routes: routesForStop(stopId),
                    isFavorite,
                    eyebrow: 'Parada del recorrido',
                    actions,
                    hint: 'Consulta los próximos autobuses o guarda esta parada.',
                });
            }

            return `
                <div class="font-semibold">${stop.name}</div>
                <div class="text-sm text-gray-600">${stop.address || ''}</div>
                <button onclick="showArrivalTimesForStop(${stopId})"
                        class="mt-2 mr-1 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                    Ver tiempos de llegada
                </button>
            `;
        }

        function selectedRouteId() {
            if (currentFilteredRoute) {
                return Number(currentFilteredRoute);
            }

            const filterValue = parseInt(document.getElementById('route-filter')?.value || '', 10);
            return Number.isFinite(filterValue) ? filterValue : null;
        }

        function stopRouteCount(stop) {
            return routesForStop(stop.id).length;
        }

        function stopMatchesActiveNetwork(stop) {
            if (!activeStopRouteIds) {
                return true;
            }

            return routesForStop(stop.id).some(route => activeStopRouteIds.has(Number(route.id)));
        }

        function stopIsPrimary(stop) {
            return stopHasPrimaryRouteById.get(Number(stop.id)) === true;
        }

        /**
         * Filtro base por zoom. Las paradas de alta frecuencia se revelan al
         * acercarse; después se aplica un adelgazamiento por distancia en
         * pantalla para que nunca se encimen dos marcadores.
         */
        function shouldShowStop(stop, zoom) {
            if (!stopsVisible) {
                return false;
            }

            const routeId = selectedRouteId();
            if (routeId) {
                return routesForStop(stop.id).some(route => Number(route.id) === routeId);
            }

            if (!stopMatchesActiveNetwork(stop)) {
                return false;
            }

            const routeCount = stopRouteCount(stop);

            if (zoom >= 16) return true;
            if (zoom >= 15) return routeCount >= 2;
            if (zoom >= 14) return routeCount >= 3;

            return routeCount >= 6;
        }

        function refreshStopVisibility() {
            const zoom = map.getZoom();
            const viewport = map.getBounds().pad(0.12);
            const minScreenDistance = zoom >= 17 ? 0 : (zoom >= 16 ? 22 : (zoom >= 15 ? 26 : 34));
            const candidates = [];

            stops.forEach(stop => {
                const marker = stopMarkers[stop.id];
                if (!marker) return;

                const icon = stopRouteCount(stop) >= 4
                    ? stopIcon
                    : stopMinorIcon;

                if (marker.options.icon !== icon) {
                    marker.setIcon(icon);
                }

                if (!shouldShowStop(stop, zoom)) {
                    setMarkerVisible(marker, false);
                    return;
                }

                const latlng = L.latLng(Number(stop.latitude), Number(stop.longitude));
                if (!viewport.contains(latlng)) {
                    setMarkerVisible(marker, false);
                    return;
                }

                candidates.push({ stop, marker, latlng });
            });

            // Primero las paradas abiertas, luego las principales y las que
            // comparten más rutas. Así el adelgazamiento conserva lo importante.
            candidates.sort((a, b) => {
                const aOpen = a.marker.isPopupOpen?.() ? 1 : 0;
                const bOpen = b.marker.isPopupOpen?.() ? 1 : 0;
                if (aOpen !== bOpen) return bOpen - aOpen;

                const aPrimary = stopIsPrimary(a.stop) ? 1 : 0;
                const bPrimary = stopIsPrimary(b.stop) ? 1 : 0;
                if (aPrimary !== bPrimary) return bPrimary - aPrimary;

                const countDiff = stopRouteCount(b.stop) - stopRouteCount(a.stop);
                if (countDiff !== 0) return countDiff;

                const aFavorite = favoriteStopIds.includes(Number(a.stop.id)) ? 1 : 0;
                const bFavorite = favoriteStopIds.includes(Number(b.stop.id)) ? 1 : 0;

                return bFavorite - aFavorite;
            });

            const shownPoints = [];

            candidates.forEach(({ marker, latlng }) => {
                const point = map.latLngToContainerPoint(latlng);
                const isOpen = marker.isPopupOpen?.();

                if (minScreenDistance > 0 && !isOpen) {
                    const overlaps = shownPoints.some(shown => (
                        Math.hypot(shown.x - point.x, shown.y - point.y) < minScreenDistance
                    ));

                    if (overlaps) {
                        setMarkerVisible(marker, false);
                        return;
                    }
                }

                shownPoints.push(point);
                setMarkerVisible(marker, true);
            });
        }

        function drawStops() {
            stops.forEach(stop => {
                if (!stopMarkers[stop.id]) {
                    const marker = L.marker([stop.latitude, stop.longitude], { icon: stopMinorIcon })
                        .addTo(map)
                        .bindPopup(stopPopupHtml(stop), {
                            className: 'ub-stop-popup-wrap',
                            maxWidth: 320,
                            autoPanPadding: [24, 24],
                        });

                    stopMarkers[stop.id] = marker;
                }
            });

            refreshStopVisibility();
        }

        window.showArrivalTimesForStop = function(stopId) {
            const stop = stops.find(s => s.id === stopId);
            if (stop) {
                showArrivalTimes(stop);
            }
        };

        function drawBuses() {
            activeBuses.forEach(bus => {
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



        // ===== Tiempo real (WebSocket con fallback a polling) =====
        // Mueve los marcadores con la posición reportada por los conductores.
        function startRealtimeUpdates() {
            if (!window.UbicaditosRealtime) {
                console.warn('⚠ Módulo de tiempo real no disponible; solo simulación local.');
                return;
            }

            const realtime = window.UbicaditosRealtime.start({ pollInterval: 5000 });

            realtime.onBusLocation(payload => {
                const bus = activeBuses.find(b => b.id === payload.id);
                if (!bus) return;

                bus.currentPosition = { lat: payload.latitude, lng: payload.longitude };
                bus.last_update = payload.last_update ?? bus.last_update;

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
                activeBuses.forEach(bus => {
                    const matches = !query
                        || bus.license_plate.toLowerCase().includes(query)
                        || bus.route.name.toLowerCase().includes(query);
                    setMarkerVisible(busMarkers[bus.id], matches);
                    if (matches) busesShown++;
                });

                if (query) {
                    // Al buscar, se muestran las coincidencias aunque estén
                    // ocultas por el nivel de zoom actual.
                    stops.forEach(stop => {
                        const matches = stopsVisible
                            && (stop.name.toLowerCase().includes(query)
                                || (stop.address || '').toLowerCase().includes(query));
                        setMarkerVisible(stopMarkers[stop.id], matches);
                    });
                } else {
                    // Al limpiar la búsqueda se recupera la visibilidad
                    // progresiva normal.
                    refreshStopVisibility();
                }

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
                const manager = getRouteManager();

                if (manager) {
                    if (currentFilteredRoute) {
                        await applyRouteFilter(currentFilteredRoute, { focus: false, loading: false });
                    } else {
                        manager.showNetwork(nearestRouteIds);
                        activeStopRouteIds = new Set(nearestRouteIds.map(Number));
                        refreshStopVisibility();
                    }
                }

                activeBuses.forEach(bus => {
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
                        // La parada puede estar oculta por el nivel de zoom;
                        // se muestra antes de abrir su popup.
                        stopMarker.addTo(map);
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
            const stop = stops.find(s => Number(s.id) === Number(stopId));
            const nickname = stopName || stop?.name || 'Parada';

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
            const stop = stops.find(s => Number(s.id) === Number(stopId));
            if (stop && stopMarkers[stopId]) {
                stopMarkers[stopId].setPopupContent(stopPopupHtml(stop));
            }
        }

        window.toggleStopLike = function(stopId) {
            fetch(`/likes/stops/${stopId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('No se pudo actualizar el me gusta');
                return response.json();
            })
            .then(data => {
                const id = Number(stopId);
                if (data.liked) {
                    likedStopIds.add(id);
                } else {
                    likedStopIds.delete(id);
                }

                stopLikesById.set(id, Number(data.count));
                updateStopPopup(id);
            })
            .catch(error => {
                console.error('Me gusta de parada:', error);
                alert('No se pudo actualizar el me gusta.');
            });
        };

        // ===== Compartir ubicación en vivo =====
        const SHARE_CSRF = '{{ csrf_token() }}';
        const sharePanel = document.getElementById('share-location-panel');
        const shareInactive = document.getElementById('share-inactive');
        const shareActive = document.getElementById('share-active');
        const shareLinkInput = document.getElementById('share-link');
        const shareFeedback = document.getElementById('share-feedback');
        const shareExpiry = document.getElementById('share-expiry');
        let activeShare = null;
        let shareUpdateTimer = null;

        function setShareFeedback(message) {
            if (shareFeedback) shareFeedback.textContent = message || '';
        }

        function openSharePanel() {
            sharePanel?.classList.remove('hidden');
        }

        function closeSharePanel() {
            sharePanel?.classList.add('hidden');
        }

        async function shareRequest(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': SHARE_CSRF,
                    ...(options.headers || {}),
                },
            });

            if (response.status === 404 || response.status === 410) {
                const expired = new Error('expired');
                expired.code = 'expired';
                throw expired;
            }

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo completar la solicitud.');
            }

            return response.json();
        }

        function renderActiveShare(share) {
            activeShare = share;
            shareInactive?.classList.add('hidden');
            shareActive?.classList.remove('hidden');

            if (shareLinkInput) shareLinkInput.value = share.url || '';

            if (shareExpiry && share.expires_at) {
                shareExpiry.textContent = 'Válido hasta las ' + new Date(share.expires_at).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }

            setShareFeedback('Tu ubicación se está compartiendo.');
        }

        function resetSharePanel() {
            activeShare = null;
            if (shareUpdateTimer) {
                clearInterval(shareUpdateTimer);
                shareUpdateTimer = null;
            }

            shareActive?.classList.add('hidden');
            shareInactive?.classList.remove('hidden');
            if (shareLinkInput) shareLinkInput.value = '';
            if (shareExpiry) shareExpiry.textContent = '';
        }

        function currentShareCoordinates() {
            if (latestUserCoordinates) return latestUserCoordinates;

            const center = map.getCenter();
            return {
                latitude: center.lat,
                longitude: center.lng,
                accuracy: null,
            };
        }

        async function pushSharePosition() {
            if (!activeShare) return;

            try {
                await shareRequest(`/passenger/share-location/${activeShare.id}`, {
                    method: 'PATCH',
                    body: JSON.stringify(currentShareCoordinates()),
                });
            } catch (error) {
                if (error.code === 'expired') {
                    resetSharePanel();
                    setShareFeedback('El enlace caducó. Genera uno nuevo si lo necesitas.');
                } else {
                    console.warn('Actualizar ubicación compartida:', error);
                }
            }
        }

        function startShareUpdates() {
            if (shareUpdateTimer) clearInterval(shareUpdateTimer);
            shareUpdateTimer = setInterval(pushSharePosition, 10000);
            pushSharePosition();
        }

        async function startSharing() {
            const button = document.getElementById('share-start-btn');
            if (button) button.disabled = true;
            setShareFeedback('Obteniendo tu ubicación…');

            try {
                const data = await shareRequest('{{ route('passenger.share-location.start') }}', {
                    method: 'POST',
                    body: JSON.stringify(currentShareCoordinates()),
                });

                renderActiveShare(data);
                startShareUpdates();
            } catch (error) {
                setShareFeedback(error.message || 'No se pudo compartir la ubicación.');
            } finally {
                if (button) button.disabled = false;
            }
        }

        async function stopSharing() {
            if (!activeShare) {
                resetSharePanel();
                return;
            }

            try {
                await shareRequest(`/passenger/share-location/${activeShare.id}`, { method: 'DELETE' });
                resetSharePanel();
                setShareFeedback('Dejaste de compartir tu ubicación.');
            } catch (error) {
                setShareFeedback(error.message || 'No se pudo detener la compartición.');
            }
        }

        async function copyShareLink() {
            if (!activeShare?.url) return;

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(activeShare.url);
                } else {
                    shareLinkInput?.select();
                    document.execCommand('copy');
                }

                setShareFeedback('Enlace copiado al portapapeles.');
            } catch (error) {
                setShareFeedback('Copia el enlace manualmente.');
            }
        }

        async function nativeShare() {
            if (!activeShare?.url) return;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Mi ubicación en Ubicaditos',
                        text: 'Sigue mi ubicación en vivo:',
                        url: activeShare.url,
                    });
                } catch (error) {
                    // El usuario canceló el diálogo nativo: no hacemos nada.
                }
            } else {
                copyShareLink();
            }
        }

        document.getElementById('share-location-btn')?.addEventListener('click', function() {
            sharePanel?.classList.toggle('hidden');
        });
        document.querySelectorAll('[data-share-close]').forEach(button => {
            button.addEventListener('click', closeSharePanel);
        });
        document.getElementById('share-start-btn')?.addEventListener('click', startSharing);
        document.getElementById('share-stop-btn')?.addEventListener('click', stopSharing);
        document.getElementById('share-copy-btn')?.addEventListener('click', copyShareLink);
        document.getElementById('share-native-btn')?.addEventListener('click', nativeShare);

        // Si el pasajero recarga la página, recuperamos el enlace que dejó activo.
        (async function restoreShare() {
            try {
                const data = await shareRequest('{{ route('passenger.share-location.current') }}', {
                    method: 'GET',
                });

                if (data.active && data.share) {
                    renderActiveShare(data.share);
                    startShareUpdates();
                }
            } catch (error) {
                console.warn('Restaurar enlace de ubicación:', error);
            }
        })();

        window.addEventListener('beforeunload', function() {
            if (shareUpdateTimer) {
                clearInterval(shareUpdateTimer);
            }
        });

        document.getElementById('show-stops').addEventListener('change', function(e) {
            stopsVisible = e.target.checked;
            refreshStopVisibility();
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
            const manager = getRouteManager();

            if (!show) {
                currentNearestRoutes = [];
                document.getElementById('nearest-routes-list').innerHTML = NEAREST_EMPTY;

                if (manager) {
                    if (currentFilteredRoute) {
                        await applyRouteFilter(currentFilteredRoute, { focus: false, loading: false });
                    } else {
                        manager.showAllRoutes();
                    }
                }

                activeStopRouteIds = null;
                refreshStopVisibility();

                activeBuses.forEach(bus => {
                    if (busMarkers[bus.id]) {
                        busMarkers[bus.id].addTo(map);
                    }
                });
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

            if (manager) {
                if (currentFilteredRoute) {
                    await applyRouteFilter(currentFilteredRoute, { focus: false, loading: false });
                } else {
                    // Sólo se muestran los tramos que pertenecen a esas rutas.
                    manager.showNetwork(nearestRouteIds);
                    activeStopRouteIds = new Set(nearestRouteIds.map(Number));
                    refreshStopVisibility();
                    console.log('🧭 Tramos visibles para rutas cercanas:', nearestRouteIds);
                }
            }

            activeBuses.forEach(bus => {
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

        // Aplica el filtro de ruta: dibuja su trazado guardado, la aísla de las
        // demás rutas y encuadra el mapa en ella con fitBounds.
        async function applyRouteFilter(routeId, { focus = true, loading = true } = {}) {
            currentFilteredRoute = routeId;

            if (routeId && loading) {
                showMapLoading('Trazando la ruta…', 'Leyendo el recorrido guardado');
            }

            const manager = getRouteManager();

            if (manager) {
                if (routeId) {
                    const route = routes.find(r => r.id === routeId);
                    if (route) {
                        await drawRoute(route);
                    }
                    manager.showOnlyRoute(routeId);
                } else {
                    const nearestChecked = document.getElementById('show-nearest-routes')?.checked;
                    const nearestIds = nearestChecked && currentNearestRoutes.length
                        ? currentNearestRoutes.map(item => item.route.id)
                        : null;
                    manager.showAllRoutes(nearestIds);
                }
            }

            const urlParams = new URLSearchParams(window.location.search);
            const busIdFromUrl = urlParams.get('bus_id');
            const specificBusId = busIdFromUrl ? parseInt(busIdFromUrl) : null;

            activeBuses.forEach(bus => {
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
            if (routeId && focus && manager) {
                centerLocked = true;
                manager.fitRoute(routeId, { padding: [40, 40], maxZoom: 16 });
            }

            if (routeId && loading) {
                hideMapLoading();
            }

            if (!routeId) {
                const nearestChecked = document.getElementById('show-nearest-routes')?.checked;
                activeStopRouteIds = nearestChecked && currentNearestRoutes.length
                    ? new Set(currentNearestRoutes.map(item => Number(item.route.id)))
                    : null;
            }

            refreshStopVisibility();
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

                initializeBuses();
                drawBuses();
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
                        const stopMarker = stopMarkers[selectedStop.id];
                        // Puede estar oculta por la divulgación progresiva;
                        // se hace visible antes de abrir el popup.
                        stopMarker.addTo(map);
                        map.flyTo([selectedStop.latitude, selectedStop.longitude], 16, { duration: 1.2 });
                        setTimeout(() => stopMarker.openPopup(), 1250);
                        console.log('🚏 Parada seleccionada centrada:', selectedStop.name);
                    }
                }

                // 3) Autobús consultado: volar hacia él y abrir sus detalles (popup).
                if (target.bus) {
                    const selectedBus = activeBuses.find(b => b.id === parseInt(target.bus));
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