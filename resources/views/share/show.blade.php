<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ubicación compartida · Ubicaditos</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    @vite('resources/css/app.css')

    <style>
        html, body { height: 100%; }
        #share-map { background: #e8edf8; }
        #share-map .leaflet-tile-pane { filter: saturate(.3) brightness(1.08) contrast(.88); opacity: .8; }
        .leaflet-popup-content-wrapper { border-radius: 16px; }
    </style>
</head>
<body class="font-sans antialiased text-[#1e2a4a]">
    <div class="relative min-h-screen overflow-hidden bg-white">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0">
            <div class="absolute -top-32 -left-32 h-[520px] w-[620px] rounded-full bg-[#c3d7ec] opacity-90 blur-[110px]"></div>
            <div class="absolute -top-24 -right-24 h-[460px] w-[560px] rounded-full bg-[#c5cbe6] opacity-90 blur-[110px]"></div>
            <div class="absolute -bottom-40 left-1/4 h-[420px] w-[720px] rounded-full bg-[#cfd6ee] opacity-80 blur-[120px]"></div>
        </div>

        <main class="relative mx-auto flex min-h-screen max-w-3xl flex-col gap-4 px-4 py-6 sm:px-6">
            <header class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#7b88b8] to-[#4a5a8a] text-white shadow-md shadow-[#4a5a8a]/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold leading-tight">Ubicación compartida</h1>
                        <p class="mt-0.5 text-xs text-[#5b6788]">Este enlace muestra en vivo la posición de un pasajero de Ubicaditos.</p>
                    </div>
                    <span id="share-status" class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-600 ring-1 ring-emerald-100">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        En vivo
                    </span>
                </div>
            </header>

            <div class="relative overflow-hidden rounded-3xl border border-white/70 shadow-xl shadow-[#4a5a8a]/10">
                <div id="share-map" class="h-[52vh] min-h-[320px] w-full"></div>

                <div id="share-expired" class="absolute inset-0 z-[500] hidden items-center justify-center bg-white/70 backdrop-blur-md">
                    <div class="mx-4 rounded-2xl border border-white/70 bg-white/90 px-6 py-5 text-center shadow-xl">
                        <p class="text-sm font-bold text-[#273252]">Este enlace ya no está activo</p>
                        <p class="mt-1 text-xs text-[#5b6788]">La persona dejó de compartir su ubicación o el enlace caducó.</p>
                    </div>
                </div>
            </div>

            <section class="grid gap-3 rounded-3xl border border-white/70 bg-white/80 p-5 shadow-xl shadow-[#4a5a8a]/10 backdrop-blur-xl sm:grid-cols-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-[#8a94b3]">Última actualización</p>
                    <p id="share-updated" class="mt-1 text-sm font-semibold text-[#1e2a4a]">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-[#8a94b3]">Precisión</p>
                    <p id="share-accuracy" class="mt-1 text-sm font-semibold text-[#1e2a4a]">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-[#8a94b3]">El enlace caduca</p>
                    <p id="share-expires" class="mt-1 text-sm font-semibold text-[#1e2a4a]">—</p>
                </div>
            </section>

            <p class="pb-2 text-center text-[11px] text-[#8a94b3]">
                Ubicaditos no guarda el historial de este recorrido. La posición se actualiza cada 5 segundos mientras el enlace esté activo.
            </p>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <script>
        const SHARE_TOKEN = @json($token);
        const SHARE_DATA_URL = @json(route('api.share.data', $token));
        const initialShare = @json($share->toPublicArray());

        const map = L.map('share-map', { zoomControl: true }).setView(
            [initialShare.latitude, initialShare.longitude],
            16
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        const pulseIcon = L.divIcon({
            html: `<div style="width:44px;height:44px;border-radius:50%;background:rgba(100,110,140,.25);display:flex;align-items:center;justify-content:center">
                <div style="width:16px;height:16px;border-radius:50%;background:#3f4c75;border:3px solid #fff;box-shadow:0 2px 8px rgba(40,50,90,.45)"></div>
            </div>`,
            className: 'shared-location-marker',
            iconSize: [44, 44],
            iconAnchor: [22, 22],
        });

        let marker = L.marker([initialShare.latitude, initialShare.longitude], {
            icon: pulseIcon,
            zIndexOffset: 1000,
        }).addTo(map);

        let accuracyCircle = initialShare.accuracy
            ? L.circle([initialShare.latitude, initialShare.longitude], {
                radius: initialShare.accuracy,
                color: '#4b5a85',
                fillColor: '#6b7aa8',
                fillOpacity: 0.12,
                weight: 1,
            }).addTo(map)
            : null;

        function renderShare(data) {
            const position = [data.latitude, data.longitude];

            marker.setLatLng(position);

            if (accuracyCircle) {
                accuracyCircle.setLatLng(position).setRadius(data.accuracy || 10);
            } else if (data.accuracy) {
                accuracyCircle = L.circle(position, {
                    radius: data.accuracy,
                    color: '#4b5a85',
                    fillColor: '#6b7aa8',
                    fillOpacity: 0.12,
                    weight: 1,
                }).addTo(map);
            }

            document.getElementById('share-updated').textContent = data.updated_at
                ? new Date(data.updated_at).toLocaleTimeString()
                : '—';
            document.getElementById('share-accuracy').textContent = data.accuracy
                ? Math.round(data.accuracy) + ' m'
                : 'No informada';
            document.getElementById('share-expires').textContent = data.expires_at
                ? new Date(data.expires_at).toLocaleTimeString()
                : '—';
        }

        function markExpired() {
            document.getElementById('share-expired').classList.remove('hidden');
            document.getElementById('share-expired').classList.add('flex');
            const status = document.getElementById('share-status');
            status.className = 'ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-600 ring-1 ring-rose-100';
            status.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Finalizado';
            clearInterval(pollTimer);
        }

        let centeredOnce = false;

        async function refresh() {
            try {
                const response = await fetch(SHARE_DATA_URL, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });

                if (response.status === 404) {
                    markExpired();
                    return;
                }

                if (!response.ok) {
                    throw new Error('No se pudo consultar la ubicación');
                }

                const data = await response.json();
                renderShare(data);

                if (!centeredOnce) {
                    map.setView([data.latitude, data.longitude], 16);
                    centeredOnce = true;
                }
            } catch (error) {
                console.warn('Compartición de ubicación:', error.message);
            }
        }

        renderShare(initialShare);
        map.setView([initialShare.latitude, initialShare.longitude], 16);
        centeredOnce = true;

        const pollTimer = setInterval(refresh, 5000);
    </script>
</body>
</html>
