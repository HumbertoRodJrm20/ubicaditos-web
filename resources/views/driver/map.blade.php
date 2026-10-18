@php
    $glass = 'bg-white/75 dark:bg-gray-800/70 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-lg';
    $iconBox = 'w-9 h-9 rounded-xl bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white flex items-center justify-center shrink-0';
    $busSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>';
@endphp

<x-passenger-layout :fullscreen="true" title="Ubicaditos · Mapa">
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin="">
    </x-slot>

    <style>
        #map { background: #e8edf8; }
        #map .leaflet-tile-pane { filter: saturate(.3) brightness(1.08) contrast(.88); opacity: .8; }
        .leaflet-popup-content-wrapper { border-radius: 16px; box-shadow: 0 12px 30px rgba(60, 70, 110, .25); }
        .panel-scroll::-webkit-scrollbar { width: 6px; }
        .panel-scroll::-webkit-scrollbar-thumb { background: rgba(75, 90, 133, .25); border-radius: 9999px; }
    </style>

    <div class="relative h-full w-full">

        <div id="map" class="absolute inset-0"></div>

        <div class="absolute inset-x-3 sm:inset-x-6 top-[5rem] bottom-16 z-[1000] pointer-events-none max-lg:overflow-y-auto">
            <div class="mx-auto max-w-[1400px] flex flex-col lg:flex-row lg:justify-between gap-3">

                <div class="flex flex-col gap-3 lg:w-[300px]">
                    @if(session('success'))
                        <div class="pointer-events-auto px-4 py-3 rounded-2xl bg-emerald-50/95 border border-emerald-200 text-emerald-700 text-[13px] shadow-lg" role="alert">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="pointer-events-auto px-4 py-3 rounded-2xl bg-rose-50/95 border border-rose-200 text-rose-700 text-[13px] shadow-lg" role="alert">{{ session('error') }}</div>
                    @endif

                    @if($route)
                        <div class="pointer-events-auto {{ $glass }} rounded-3xl p-4">
                            <h3 class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#4b5a85]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="3" stroke-width="2"></circle>
                                    <circle cx="12" cy="12" r="8" stroke-width="2"></circle>
                                    <path stroke-linecap="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3"></path>
                                </svg>
                                Rutas más cercanas
                                <span class="text-[11px] font-normal text-slate-400">5 km</span>
                            </h3>

                            <div class="flex items-center gap-3 rounded-xl bg-white/80 dark:bg-gray-700/60 p-2.5">
                                <span class="{{ $iconBox }}">{!! $busSvg !!}</span>
                                <div class="min-w-0">
                                    <div class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 truncate">{{ $route->name }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $route->description }}</div>
                                </div>
                            </div>

                            <div class="mt-3">
                                @if($currentTrip)
                                    <form method="POST" action="{{ route('driver.end-trip') }}" onsubmit="return confirm('¿Estás seguro de finalizar este viaje?')">
                                        @csrf
                                        <button type="submit" class="w-full h-10 rounded-full bg-gradient-to-r from-[#d62f3a] to-[#e8a0a6] text-white text-[13px] font-medium flex items-center justify-center gap-2 hover:brightness-105 transition">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"></rect></svg>
                                            Finalizar Viaje
                                        </button>
                                    </form>
                                @else
                                    @if(auth()->user()->is_active && $bus->status === 'active')
                                        <form method="POST" action="{{ route('driver.start-trip') }}">
                                            @csrf
                                            <button type="submit" class="w-full h-10 rounded-full bg-gradient-to-r from-[#86bcc0] to-[#3ed6e0] text-white text-[13px] font-medium flex items-center justify-center gap-2 hover:brightness-105 transition">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                                Iniciar Viaje
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled class="w-full h-10 rounded-full bg-slate-300 text-white text-[13px] font-medium cursor-not-allowed opacity-70 flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Iniciar Viaje
                                        </button>
                                        <p class="mt-2 text-[11px] text-rose-600 text-center">
                                            @if(!auth()->user()->is_active)
                                                Debes estar activo para iniciar un viaje. Contacta al administrador.
                                            @elseif($bus->status !== 'active')
                                                El autobús debe estar en estado activo para iniciar un viaje.
                                            @endif
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="lg:w-[300px] lg:self-start flex flex-col gap-3">
                    @if($stops && $stops->count() > 0)
                        <div class="pointer-events-auto {{ $glass }} rounded-3xl p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="{{ $iconBox }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-semibold text-slate-800 dark:text-gray-100 truncate">Paradas de la ruta</div>
                                        <div class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $route->name ?? '' }}</div>
                                    </div>
                                </div>
                                <button type="button" data-collapse-target="driver-stops-body" aria-expanded="true" aria-label="Minimizar panel" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100/80 dark:hover:bg-gray-700/60 shrink-0 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>

                            <div id="driver-stops-body">
                                <div class="border-t border-slate-200/70 dark:border-white/10 my-3"></div>

                                <div class="text-[10px] font-semibold tracking-[.12em] uppercase text-slate-400 mb-2">Próximas paradas</div>

                                <div class="space-y-1 max-h-[42vh] overflow-y-auto panel-scroll">
                                    @foreach($stops as $index => $stop)
                                        <div id="stop-{{ $stop->id }}" class="flex items-center gap-3 rounded-xl px-2 py-2 transition-all">
                                            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-[#6b7aa8] to-[#3f4c75] text-white flex items-center justify-center shrink-0">{!! $busSvg !!}</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[13px] font-semibold text-slate-800 dark:text-gray-100 truncate">{{ $stop->name }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $stop->address }}</p>
                                            </div>
                                            @if($index < $stops->count() - 1)
                                                <div class="text-[14px] font-bold text-[#3f4c75] dark:text-blue-300 whitespace-nowrap">
                                                    ~{{ $stop->pivot->estimated_time_from_previous ?? 5 }} min
                                                </div>
                                            @endif
                                            <div class="stop-status hidden">
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 text-green-800">Llegado</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

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
        </div>

        <div class="absolute bottom-6 left-3 sm:left-6 z-[1000]">
            <div class="relative">
                <button type="button" data-toggle="sim-panel" class="pointer-events-auto {{ $glass }} rounded-full pl-4 pr-3 py-2 flex items-center gap-2 text-[12px] font-medium text-slate-700 dark:text-gray-200">
                    <span id="gps-indicator" class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span id="gps-text">GPS Activo</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div id="sim-panel" data-popover class="hidden absolute bottom-[calc(100%+8px)] left-0 w-64 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/70 dark:border-white/10 shadow-xl rounded-2xl p-4 space-y-2">
                    <label class="flex items-center cursor-pointer" id="share-location-label">
                        <input type="checkbox" id="share-location-toggle" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-slate-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4b5a85]"></div>
                        <span class="ml-3 text-xs font-medium text-slate-700 dark:text-gray-300">Compartir mi ubicación</span>
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-gray-400">
                        Se envía automáticamente a los pasajeros mientras esté activo.
                    </p>
                    <label class="flex items-center cursor-pointer" id="simulated-location-label">
                        <input type="checkbox" id="simulated-location-toggle" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-slate-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#4b5a85]"></div>
                        <span class="ml-3 text-xs font-medium text-slate-700 dark:text-gray-300">Ubicación Simulada</span>
                    </label>
                    <p id="simulated-hint" class="text-[11px] text-slate-500 dark:text-gray-400 hidden">
                        Haz clic en el mapa para establecer tu posición
                    </p>
                    <p id="trip-active-warning" class="text-[11px] text-amber-600 dark:text-amber-400 hidden">
                        ⚠️ No puedes cambiar la ubicación durante un viaje
                    </p>
                    <div class="text-[11px] text-slate-500 dark:text-gray-400">
                        Última actualización: <span id="last-update">Iniciando...</span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-gray-400">
                        Velocidad: <span id="current-speed">0</span> km/h
                    </div>
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

        const map = L.map('map', { zoomControl: false }).setView([{{ $bus->current_latitude ?? 19.4326 }}, {{ $bus->current_longitude ?? -99.1332 }}], 14);

        map.attributionControl.setPosition('bottomright');

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        // ===== Controles manuales del mapa (antes eran botones muertos) =====
        document.getElementById('zoom-in-btn').addEventListener('click', () => map.zoomIn());
        document.getElementById('zoom-out-btn').addEventListener('click', () => map.zoomOut());

        document.getElementById('locate-btn').addEventListener('click', function() {
            if (lastPosition) {
                map.flyTo([lastPosition.lat, lastPosition.lng], 16);
            } else if (busMarker) {
                map.flyTo(busMarker.getLatLng(), 16);
            } else {
                // Sin posición conocida: pide una fix al navegador y centra con ella.
                map.locate({ setView: true, maxZoom: 16 });
            }
        });

        // Leaflet emite estos eventos cuando se llama a map.locate().
        map.on('locationfound', function(e) {
            if (busMarker) {
                busMarker.setLatLng(e.latlng);
            } else {
                createBusMarker(e.latlng.lat, e.latlng.lng);
            }
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

        const stops = @json($stops ?? []);
        const route = @json($route ?? null);
        const busData = {
            id: {{ $bus->id }},
            license_plate: '{{ $bus->license_plate }}',
            latitude: {{ $bus->current_latitude ?? 19.4326 }},
            longitude: {{ $bus->current_longitude ?? -99.1332 }}
        };

        const BUS_SVG = (size = 18) => `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="14" rx="3"></rect><path d="M4 11h16M8 21v-4M16 21v-4"></path><circle cx="8.5" cy="14" r=".6"></circle><circle cx="15.5" cy="14" r=".6"></circle></svg>`;

        const busIcon = L.divIcon({
            html: `<div style="
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6b7aa8, #3f4c75);
                border: 3px solid #fff;
                box-shadow: 0 4px 12px rgba(40,50,90,0.35);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
            ">${BUS_SVG(18)}</div>`,
            className: 'bus-marker-driver',
            iconSize: [38, 38],
            iconAnchor: [19, 19],
            popupAnchor: [0, -19]
        });

        const stopIcon = L.divIcon({
            html: '<div style="background-color: #fff; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #4b5a85; box-shadow: 0 2px 6px rgba(40,50,90,0.35);"></div>',
            className: 'stop-marker',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
            popupAnchor: [0, -9]
        });

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

        const SIMULATED_LOCATION = {
            lat: 17.076323,
            lng: -96.744676,
            accuracy: 20
        };

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
                    routePath = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                    const color = route?.color || '#4b5a85';
                    routePolyline = L.polyline(routePath, {
                        color: color,
                        weight: 5,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round'
                    }).addTo(map);

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

        function fallbackStraightLine() {
            if (!stops || stops.length < 2) return;

            routePath = stops.map(stop => [parseFloat(stop.latitude), parseFloat(stop.longitude)]);
            const color = route?.color || '#4b5a85';

            routePolyline = L.polyline(routePath, {
                color: color,
                weight: 5,
                opacity: 0.9,
                lineCap: 'round',
                dashArray: '10, 10'
            }).addTo(map);

            map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
            console.log('Ruta generada con líneas rectas (fallback)');
        }

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

        function createBusMarker(lat, lng) {
            busMarker = L.marker([lat, lng], { icon: busIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="font-semibold">${busData.license_plate}</div>
                    <div class="text-sm">Mi ubicación actual</div>
                `);
        }

        function calculateSpeed(oldPos, newPos, timeElapsed) {
            if (!oldPos || !newPos) return 0;

            const R = 6371;
            const dLat = (newPos.lat - oldPos.lat) * Math.PI / 180;
            const dLon = (newPos.lng - oldPos.lng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(oldPos.lat * Math.PI / 180) * Math.cos(newPos.lat * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c;

            const timeInHours = timeElapsed / 3600000;
            const speed = distance / timeInHours;

            return Math.round(speed);
        }

        // Envía la posición al servidor (el backend la retransmite en tiempo real).
        function sendLocationToServer(lat, lng) {
            fetch('{{ route("driver.update-location") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ latitude: lat, longitude: lng })
            })
            .then(response => response.json())
            .then(() => {
                console.log('Ubicación actualizada en servidor');
            })
            .catch(error => {
                console.error('Error al actualizar ubicación:', error);
            });
        }

        function applySimulatedPosition(lat, lng) {
            if (busMarker) {
                busMarker.setLatLng([lat, lng]);
            } else {
                createBusMarker(lat, lng);
            }

            map.panTo([lat, lng]);

            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            document.getElementById('current-speed').textContent = '0';

            lastPosition = { lat, lng, timestamp: Date.now() };

            checkProximityToStops(lat, lng);
        }

        function useSimulatedLocationDriver() {
            const location = customSimulatedLocation || SIMULATED_LOCATION;
            console.log('🎯 Usando ubicación GPS simulada para conductor:', location);

            applySimulatedPosition(location.lat, location.lng);
            sendLocationToServer(location.lat, location.lng);

            // Auto-actualización: reenvía la posición cada 5 s con una deriva mínima
            // para que los pasajeros vean la ubicación "viva" también en modo simulado.
            if (simulatedLocationInterval) {
                clearInterval(simulatedLocationInterval);
            }

            simulatedLocationInterval = setInterval(() => {
                const drift = 0.0003;
                location.lat += (Math.random() - 0.5) * drift;
                location.lng += (Math.random() - 0.5) * drift;

                applySimulatedPosition(location.lat, location.lng);
                sendLocationToServer(location.lat, location.lng);
            }, 5000);

            console.log('✅ Ubicación GPS simulada con auto-actualización cada 5 s');
        }

        function updateLocation() {
            if (!navigator.geolocation) {
                console.warn('⚠ Navegador no soporta geolocalización. Usando ubicación simulada.');
                useSimulatedLocationDriver();
                return;
            }

            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        if (hasTripActive && isSimulatedMode) {
                            console.log('🔒 Viaje activo: ignorando actualizaciones de GPS en modo simulado');
                            return;
                        }

                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const timestamp = Date.now();

                        let speed = 0;
                        if (lastPosition) {
                            const timeElapsed = timestamp - lastPosition.timestamp;
                            speed = calculateSpeed(
                                { lat: lastPosition.lat, lng: lastPosition.lng },
                                { lat, lng },
                                timeElapsed
                            );
                        }

                        if (busMarker) {
                            busMarker.setLatLng([lat, lng]);
                        } else {
                            createBusMarker(lat, lng);
                        }

                        map.panTo([lat, lng]);

                        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                        document.getElementById('current-speed').textContent = speed;
                        document.getElementById('gps-indicator').className = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';

                        lastPosition = { lat, lng, timestamp };

                        sendLocationToServer(lat, lng);

                        checkProximityToStops(lat, lng);
                    },
                    function(error) {
                        console.warn('⚠ Error al obtener ubicación GPS del conductor:', {
                            code: error.code,
                            message: error.message
                        });

                        console.log('🔄 Cambiando a ubicación GPS simulada para conductor...');
                        useSimulatedLocationDriver();
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 2000,
                        timeout: 5000
                    }
                );
            } else {
                alert('Tu navegador no soporta geolocalización');
            }
        }

        function checkProximityToStops(lat, lng) {
            const threshold = 0.001;

            stopMarkers.forEach((stopData, index) => {
                const stopLat = stopData.position[0];
                const stopLng = stopData.position[1];

                const distance = Math.sqrt(
                    Math.pow(stopLat - lat, 2) + Math.pow(stopLng - lng, 2)
                );

                const stopElement = document.getElementById(`stop-${stopData.id}`);
                if (distance < threshold) {
                    if (stopElement) {
                        stopElement.classList.add('bg-green-100', 'dark:bg-green-900');
                        stopElement.querySelector('.stop-status').classList.remove('hidden');
                    }
                } else {
                    if (stopElement) {
                        stopElement.classList.remove('bg-green-100', 'dark:bg-green-900');
                        stopElement.querySelector('.stop-status').classList.add('hidden');
                    }
                }
            });
        }

        console.log('Inicializando mapa del conductor...');

        Promise.all([
            generateRoute(),
            Promise.resolve(drawStops()),
            Promise.resolve(createBusMarker(busData.latitude, busData.longitude))
        ]).then(() => {
            console.log('✅ Mapa inicializado y listo');
        });

        const simulatedToggle = document.getElementById('simulated-location-toggle');
        const simulatedHint = document.getElementById('simulated-hint');
        const gpsIndicator = document.getElementById('gps-indicator');
        const gpsText = document.getElementById('gps-text');
        const tripActiveWarning = document.getElementById('trip-active-warning');
        const simulatedLabel = document.getElementById('simulated-location-label');

        if (hasTripActive) {
            simulatedToggle.disabled = true;
            simulatedLabel.classList.add('opacity-50', 'cursor-not-allowed');
            simulatedLabel.classList.remove('cursor-pointer');
            tripActiveWarning.classList.remove('hidden');
            simulatedHint.classList.add('hidden');
        }

        simulatedToggle.addEventListener('change', function() {
            if (hasTripActive) {
                this.checked = isSimulatedMode;
                alert('No puedes cambiar el modo de ubicación mientras hay un viaje activo');
                return;
            }

            isSimulatedMode = this.checked;

            if (isSimulatedMode) {
                simulatedHint.classList.remove('hidden');
                customSimulatedLocation = null;

                if (simulatedLocationInterval) {
                    clearInterval(simulatedLocationInterval);
                    simulatedLocationInterval = null;
                }
            } else {
                simulatedHint.classList.add('hidden');
                customSimulatedLocation = null;

                if (simulatedLocationInterval) {
                    clearInterval(simulatedLocationInterval);
                    simulatedLocationInterval = null;
                }
            }

            applySharingState();
        });

        // ===== Compartir ubicación (estado persistente entre sesiones) =====
        const shareToggle = document.getElementById('share-location-toggle');
        const SHARE_KEY = 'ubicaditos_share_location';

        function isSharingEnabled() {
            return localStorage.getItem(SHARE_KEY) !== 'off';
        }

        function stopSharing() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            if (simulatedLocationInterval) {
                clearInterval(simulatedLocationInterval);
                simulatedLocationInterval = null;
            }
            gpsIndicator.className = 'w-2 h-2 bg-slate-400 rounded-full';
            gpsText.textContent = 'GPS Inactivo';
        }

        function applySharingState() {
            const enabled = isSharingEnabled();
            shareToggle.checked = enabled;

            if (!enabled) {
                stopSharing();
                return;
            }

            if (isSimulatedMode) {
                gpsIndicator.className = 'w-2 h-2 bg-blue-500 rounded-full animate-pulse';
                gpsText.textContent = 'Modo Simulado';

                if (customSimulatedLocation) {
                    useSimulatedLocationDriver();
                }
            } else {
                gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';
                gpsText.textContent = 'GPS Activo';
                updateLocation();
            }
        }

        shareToggle.addEventListener('change', function() {
            localStorage.setItem(SHARE_KEY, this.checked ? 'on' : 'off');
            applySharingState();
        });

        map.on('click', function(e) {
            if (isSimulatedMode) {
                if (hasTripActive) {
                    alert('No puedes cambiar la ubicación mientras hay un viaje activo');
                    return;
                }

                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                customSimulatedLocation = { lat, lng, accuracy: 20 };

                console.log('📍 Nueva ubicación simulada establecida:', customSimulatedLocation);

                if (simulatedLocationInterval) {
                    clearInterval(simulatedLocationInterval);
                    simulatedLocationInterval = null;
                }

                if (isSharingEnabled()) {
                    useSimulatedLocationDriver();
                } else {
                    // Sin compartir: solo se mueve el marcador local, no se envía al servidor.
                    applySimulatedPosition(lat, lng);
                }

                L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#4b5a85',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map).bindPopup('Ubicación simulada establecida').openPopup();
            }
        });

        // Arranque: respeta la preferencia de compartir ubicación guardada.
        applySharingState();

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

        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }
    </script>
</x-passenger-layout>