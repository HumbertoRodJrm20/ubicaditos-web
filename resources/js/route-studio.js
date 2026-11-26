/**
 * Estudio de rutas y paradas.
 *
 * Componente Alpine ligero sobre Leaflet. Toda la escritura pasa por endpoints
 * del servidor (admin + CSRF); el mapa sólo manipula estado local hasta que se
 * pulsa Guardar. El trazado se dibuja con `route-layer.js`, que reutiliza
 * `path_coordinates` y deduplica tramos compartidos.
 */
import axios from 'axios';
import Alpine from 'alpinejs';
import { createMapRoutes } from './route-layer.js';

const DEFAULT_CENTER = [17.0654, -96.7256];
const DEFAULT_ZOOM = 13;

function round(value) {
    const number = Number(value);
    return Number.isFinite(number) ? Math.round(number * 1e7) / 1e7 : '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function errorMessage(error) {
    const data = error?.response?.data ?? {};

    if (data.errors) {
        return Object.values(data.errors).flat().join(' ');
    }

    return data.message || error?.message || 'No se pudo completar la operación.';
}

function asId(value) {
    const id = Number(value);
    return Number.isFinite(id) && id > 0 ? id : null;
}

Alpine.data('routeStudio', (initial = {}) => ({
    routes: Array.isArray(initial.routes) ? initial.routes : [],
    stops: Array.isArray(initial.stops) ? initial.stops : [],
    mainStops: Array.isArray(initial.stops) ? initial.stops : [],
    stopPage: 1,
    stopLastPage: 1,
    stopSearchRequest: 0,
    endpoints: {
        data: initial.endpoints?.data ?? '',
        routes: initial.endpoints?.routes ?? {},
        stops: initial.endpoints?.stops ?? {},
    },

    panel: new URLSearchParams(window.location.search).get('panel') === 'stops' ? 'stops' : 'routes',
    searchRoute: '',
    searchStop: '',
    selectedRouteId: asId(new URLSearchParams(window.location.search).get('route')),
    selectedStopId: asId(new URLSearchParams(window.location.search).get('stop')),
    sequence: [],
    savedSequence: [],
    frequentReviewNeeded: false,
    draft: {
        id: null,
        name: '',
        address: '',
        latitude: '',
        longitude: '',
    },
    mode: 'create',

    map: null,
    routeLayer: null,
    markerLayer: null,
    markers: new Map(),
    draftMarker: null,
    csrfToken: '',
    initialized: false,
    loading: false,
    error: '',
    success: '',

    get selectedRoute() {
        return this.routes.find((route) => Number(route.id) === Number(this.selectedRouteId)) ?? null;
    },

    get selectedStop() {
        return this.visibleStops.find((stop) => Number(stop.id) === Number(this.selectedStopId)) ?? null;
    },

    get sequenceDirty() {
        return this.sequence.length !== this.savedSequence.length
            || this.sequence.some((id, index) => Number(id) !== Number(this.savedSequence[index]));
    },

    get stopDraftDirty() {
        if (this.mode === 'create') {
            return Boolean(this.draft.name || this.draft.address || this.draft.latitude !== '' || this.draft.longitude !== '');
        }

        const stop = this.selectedStop;
        if (!stop) return false;

        return this.draft.name !== (stop.name ?? '')
            || this.draft.address !== (stop.address ?? '')
            || Number(this.draft.latitude) !== Number(stop.latitude)
            || Number(this.draft.longitude) !== Number(stop.longitude);
    },

    get hasUnsavedChanges() {
        return this.sequenceDirty || this.stopDraftDirty;
    },

    get visibleStops() {
        const selectedRouteStops = this.selectedRoute?.stops ?? [];
        return [...new Map(
            [...this.stops, ...selectedRouteStops].map((stop) => [Number(stop.id), stop])
        ).values()];
    },

    get filteredRoutes() {
        const term = this.searchRoute.trim().toLowerCase();

        if (!term) return this.routes;

        return this.routes.filter((route) =>
            `${route.name} ${route.code}`.toLowerCase().includes(term)
        );
    },

    get filteredStops() {
        const term = this.searchStop.trim().toLowerCase();

        if (!term) return this.stops;

        return this.stops.filter((stop) =>
            `${stop.name} ${stop.address ?? ''}`.toLowerCase().includes(term)
        );
    },

    get availableStops() {
        if (!this.selectedRoute) {
            return this.stops;
        }

        const attached = new Set(this.selectedRoute.stops.map((stop) => Number(stop.id)));
        return this.stops.filter((stop) => !attached.has(Number(stop.id)));
    },

    init() {
        // Alpine llama a init() automáticamente y la vista también lo invoca
        // desde x-init; este guard evita duplicar peticiones y listeners.
        if (this.initialized) return;
        this.initialized = true;

        this.csrfToken = document.querySelector('[data-csrf]')?.value ?? '';
        this.syncSequenceFromSelection();
        window.addEventListener('beforeunload', (event) => this.protectUnsavedChanges(event));

        if (typeof L === 'undefined') {
            window.addEventListener('load', () => this.setupMap(), { once: true });
        } else {
            this.setupMap();
        }

        this.refresh({ silent: true }).then(() => {
            const stopId = asId(new URLSearchParams(window.location.search).get('stop'));
            const routeId = asId(new URLSearchParams(window.location.search).get('route'));

            if (stopId) this.selectStop(stopId);
            else if (routeId) this.selectRoute(routeId);
        });
    },

    headers() {
        return {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrfToken,
        };
    },

    async run(task, successText = '') {
        this.loading = true;
        this.error = '';
        this.success = '';

        try {
            const result = await task();
            if (successText) this.success = successText;
            return result;
        } catch (error) {
            this.error = errorMessage(error);
            return null;
        } finally {
            this.loading = false;
        }
    },

    async refresh({ silent = true, confirmDiscard = true, preserveSequence = false } = {}) {
        if (confirmDiscard && this.hasUnsavedChanges) {
            if (!this.confirmDiscardChanges()) return null;
            this.resetDraft();
        }

        const keepSequence = preserveSequence && this.sequenceDirty;
        const data = await this.run(
            async () => (await axios.get(this.endpoints.data, { headers: this.headers() })).data,
            silent ? '' : 'Datos actualizados.'
        );

        if (!data) return null;

        this.routes = Array.isArray(data.routes) ? data.routes : [];
        this.mainStops = Array.isArray(data.stops) ? data.stops : [];
        this.stops = this.mainStops;
        this.stopPage = 1;
        this.stopLastPage = 1;
        this.endpoints = { ...this.endpoints, ...(data.endpoints ?? {}) };

        if (this.selectedRouteId && !this.selectedRoute) this.selectedRouteId = null;

        if (this.selectedRoute) {
            await this.loadSelectedRouteStops({ syncSequence: !keepSequence });
        }

        if (this.searchStop.trim()) {
            await this.searchStopCatalog({ reset: true });
        }

        if (this.selectedStopId && !this.selectedStop) this.resetDraft();

        if (!keepSequence) this.syncSequenceFromSelection();
        this.rebuildLayer();
        this.applyRouteFilter();

        return data;
    },

    confirmDiscardChanges() {
        return !this.hasUnsavedChanges || window.confirm('Hay cambios sin guardar. ¿Deseas descartarlos?');
    },

    protectUnsavedChanges(event) {
        if (!this.hasUnsavedChanges) return;

        event.preventDefault();
        event.returnValue = '';
    },

    async searchStopCatalog({ reset = true } = {}) {
        const query = this.searchStop.trim();
        const requestId = ++this.stopSearchRequest;

        if (!query) {
            this.stops = this.mainStops;
            this.stopPage = 1;
            this.stopLastPage = 1;
            this.renderMarkers();
            return;
        }

        if (!this.endpoints.stops.data) return;

        const page = reset ? 1 : this.stopPage + 1;
        const data = await this.run(async () => (
            await axios.get(this.endpoints.stops.data, {
                params: { q: query, page, per_page: 50 },
                headers: this.headers(),
            })
        ).data);

        if (!data) return;
        if (requestId !== this.stopSearchRequest || query !== this.searchStop.trim()) return;

        const pageStops = Array.isArray(data.data) ? data.data : [];
        this.stops = reset
            ? pageStops
            : [...new Map([...this.stops, ...pageStops].map((stop) => [Number(stop.id), stop])).values()];
        this.stopPage = Number(data.current_page ?? page);
        this.stopLastPage = Number(data.last_page ?? this.stopPage);

        if (this.selectedStopId && !this.selectedStop) this.resetDraft();

        this.renderMarkers();
    },

    setupMap() {
        if (this.map || typeof L === 'undefined') return;

        const element = document.getElementById('route-studio-map');
        if (!element) return;

        this.map = L.map(element, { scrollWheelZoom: true }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(this.map);

        this.map.on('click', (event) => this.onMapClick(event));
        this.rebuildLayer();
    },

    rebuildLayer() {
        if (!this.map || typeof L === 'undefined') return;

        if (this.routeLayer) {
            this.routeLayer.hideNetwork();
            this.routeLayer.hideAllRouteLines();
        }

        if (this.markerLayer && this.map.hasLayer(this.markerLayer)) {
            this.map.removeLayer(this.markerLayer);
        }

        this.routeLayer = createMapRoutes({
            map: this.map,
            routes: this.routes,
            onRouteClick: (route) => this.selectRoute(route.id),
        });

        this.renderMarkers();
    },

    applyRouteFilter() {
        if (!this.routeLayer) return;

        if (this.selectedRouteId) {
            this.routeLayer.showAllRoutes([this.selectedRouteId]);
            this.fitSelectedRoute();
        } else {
            this.routeLayer.showAllRoutes();
        }

        this.renderMarkers();
    },

    fitSelectedRoute() {
        if (!this.map || typeof L === 'undefined') return;

        if (this.selectedRouteId && this.routeLayer?.fitRoute(this.selectedRouteId)) {
            return;
        }

        const points = this.sequence
            .map((id) => this.visibleStops.find((stop) => Number(stop.id) === Number(id)))
            .filter(Boolean)
            .map((stop) => [Number(stop.latitude), Number(stop.longitude)]);

        if (points.length > 0) {
            this.map.fitBounds(L.latLngBounds(points), { padding: [48, 48], maxZoom: 16 });
        }
    },

    markerIcon(stop) {
        const selected = Number(stop.id) === Number(this.selectedStopId);
        const inRoute = this.selectedRoute?.stops.some((item) => Number(item.id) === Number(stop.id));
        const frequent = inRoute && Boolean(this.selectedRoute.stops.find(
            (item) => Number(item.id) === Number(stop.id)
        )?.pivot?.is_frequent);
        const classes = [
            'studio-stop-marker',
            selected ? 'is-selected' : '',
            inRoute ? 'is-in-route' : '',
            frequent ? 'is-frequent' : '',
        ].filter(Boolean).join(' ');

        return L.divIcon({
            className: classes,
            html: `<span>${escapeHtml(String(stop.name ?? '?').charAt(0).toUpperCase())}</span>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13],
            popupAnchor: [0, -13],
        });
    },

    renderMarkers() {
        if (!this.map || typeof L === 'undefined') return;

        if (this.markerLayer && this.map.hasLayer(this.markerLayer)) {
            this.map.removeLayer(this.markerLayer);
        }

        this.markerLayer = L.layerGroup();
        this.markers.clear();

        this.visibleStops.forEach((stop) => {
            const latitude = Number(stop.latitude);
            const longitude = Number(stop.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const marker = L.marker([latitude, longitude], {
                icon: this.markerIcon(stop),
                title: stop.name,
                draggable: true,
                riseOnHover: true,
            });

            marker.bindPopup(`
                <div class="studio-stop-popup">
                    <strong>${escapeHtml(stop.name)}</strong>
                    ${stop.address ? `<div>${escapeHtml(stop.address)}</div>` : ''}
                </div>
            `);

            marker.on('click', (event) => {
                L.DomEvent.stopPropagation(event);
                this.selectStop(stop.id);
            });

            marker.on('dragend', (event) => {
                const position = event.target.getLatLng();

                if (this.mode === 'edit' && Number(this.selectedStopId) === Number(stop.id)) {
                    this.draft.latitude = round(position.lat);
                    this.draft.longitude = round(position.lng);
                    this.success = 'Ubicación lista; guarda la parada para aplicarla.';
                } else {
                    this.beginCreateAt(position.lat, position.lng);
                }
            });

            this.markers.set(Number(stop.id), marker);
            this.markerLayer.addLayer(marker);
        });

        if (this.mode === 'create' && this.draft.latitude !== '' && this.draft.longitude !== '') {
            const latitude = Number(this.draft.latitude);
            const longitude = Number(this.draft.longitude);

            if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
                this.draftMarker = L.marker([latitude, longitude], {
                    icon: L.divIcon({
                        className: 'studio-stop-marker is-draft',
                        html: '<span>+</span>',
                        iconSize: [26, 26],
                        iconAnchor: [13, 13],
                    }),
                    draggable: true,
                    title: 'Nueva parada',
                });

                this.draftMarker.on('dragend', (event) => {
                    const position = event.target.getLatLng();
                    this.draft.latitude = round(position.lat);
                    this.draft.longitude = round(position.lng);
                });

                this.markerLayer.addLayer(this.draftMarker);
            }
        }

        this.markerLayer.addTo(this.map);
    },

    onMapClick(event) {
        if (this.mode === 'edit' && this.selectedStopId) {
            this.draft.latitude = round(event.latlng.lat);
            this.draft.longitude = round(event.latlng.lng);
            this.success = 'Ubicación lista; guarda la parada para aplicarla.';
            this.renderMarkers();
            return;
        }

        this.beginCreateAt(event.latlng.lat, event.latlng.lng);
    },

    beginCreateAt(latitude, longitude) {
        const replacingStop = this.mode === 'edit';

        if (this.mode === 'edit' && this.stopDraftDirty
            && !window.confirm('Hay cambios de la parada sin guardar. ¿Deseas descartarlos?')) {
            return;
        }

        this.mode = 'create';
        this.selectedStopId = null;
        this.panel = 'stops';
        this.draft = {
            id: null,
            name: replacingStop ? '' : (this.draft.name ?? ''),
            address: replacingStop ? '' : (this.draft.address ?? ''),
            latitude: round(latitude),
            longitude: round(longitude),
        };
        this.renderMarkers();
    },

    syncSequenceFromSelection() {
        if (!this.selectedRoute) {
            this.sequence = [];
            this.savedSequence = [];
            return;
        }

        this.sequence = this.selectedRoute.stops
            .filter((stop) => !Boolean(stop.pivot?.is_frequent))
            .map((stop) => Number(stop.id));
        this.savedSequence = [...this.sequence];
    },

    async selectRoute(id) {
        const routeId = asId(id);
        if (!routeId || !this.routes.some((route) => Number(route.id) === routeId)) return;
        if (routeId === Number(this.selectedRouteId)) return;
        if (!this.confirmDiscardChanges()) return;

        this.selectedRouteId = routeId;
        this.selectedStopId = null;
        this.frequentReviewNeeded = false;
        this.mode = 'create';
        this.resetDraft();
        this.syncSequenceFromSelection();
        this.applyRouteFilter();
        await this.loadSelectedRouteStops();
    },

    async loadSelectedRouteStops({ syncSequence = true } = {}) {
        const route = this.selectedRoute;
        if (!route?.urls?.stops || route.stopsLoaded) return;

        const routeId = Number(route.id);
        const data = await this.run(async () => (
            await axios.get(route.urls.stops, { headers: this.headers() })
        ).data);

        if (!data) return;

        route.stops = Array.isArray(data.stops) ? data.stops : [];
        route.stopsLoaded = true;

        if (syncSequence && Number(this.selectedRouteId) === routeId) {
            this.syncSequenceFromSelection();
            this.applyRouteFilter();
        }
    },

    async selectStop(id) {
        const stopId = asId(id);
        if (!stopId) return;

        let stop = this.visibleStops.find((item) => Number(item.id) === stopId);

        if (!stop && this.endpoints.stops.data) {
            const data = await this.run(async () => (
                await axios.get(this.endpoints.stops.data, {
                    params: { stop_id: stopId, per_page: 1 },
                    headers: this.headers(),
                })
            ).data);
            stop = data?.data?.[0] ?? null;

            if (stop) {
                this.stops = [stop, ...this.stops];
            }
        }

        if (!stop) return;
        if (this.mode === 'edit' && Number(this.selectedStopId) === stopId) return;
        if (this.stopDraftDirty && !window.confirm('Hay cambios de la parada sin guardar. ¿Deseas descartarlos?')) return;

        this.selectedStopId = Number(stop.id);
        this.mode = 'edit';
        this.panel = 'stops';
        this.draft = {
            id: Number(stop.id),
            name: stop.name ?? '',
            address: stop.address ?? '',
            latitude: round(stop.latitude),
            longitude: round(stop.longitude),
        };
        this.renderMarkers();

        const marker = this.markers.get(Number(stop.id));
        if (marker) {
            this.map?.panTo(marker.getLatLng());
            marker.openPopup();
        }
    },

    stopName(id) {
        return this.stops.find((stop) => Number(stop.id) === Number(id))?.name
            ?? this.selectedRoute?.stops.find((stop) => Number(stop.id) === Number(id))?.name
            ?? 'Parada';
    },

    stopMinutes(id) {
        const stop = this.selectedRoute?.stops.find((item) => Number(item.id) === Number(id));
        const minutes = stop?.pivot?.estimated_time_from_previous;

        return minutes === null || minutes === undefined ? '—' : `${minutes} min`;
    },

    frequentStops() {
        if (!this.selectedRoute) return [];

        return this.selectedRoute.stops.filter((stop) => Boolean(stop.pivot?.is_frequent));
    },

    addToSequence(id) {
        const stopId = asId(id);
        if (!stopId || this.sequence.includes(stopId)) return;

        this.sequence.push(stopId);
        this.success = 'Parada agregada al orden. Guarda para aplicar.';
    },

    removeFromSequence(index) {
        if (index < 0 || index >= this.sequence.length) return;

        this.sequence.splice(index, 1);
        this.success = 'Parada quitada del orden. Guarda para aplicar.';
    },

    move(index, direction) {
        const target = index + direction;
        if (index < 0 || target < 0 || target >= this.sequence.length) return;

        const current = this.sequence[index];
        this.sequence[index] = this.sequence[target];
        this.sequence[target] = current;
        this.success = 'Orden ajustado. Guarda para aplicar.';
    },

    async saveSequence() {
        if (!this.selectedRoute) {
            this.error = 'Selecciona una ruta para guardar el orden.';
            return;
        }

        const result = await this.run(async () => {
            const response = await axios.post(
                this.selectedRoute.urls.sync,
                { stops: this.sequence.map(Number) },
                { headers: this.headers() }
            );

            return response.data;
        });

        if (!result) return;

        this.savedSequence = this.sequence.map(Number);
        this.frequentReviewNeeded = Boolean(result.frequent_review_needed);
        await this.refresh({ confirmDiscard: false });

        if (result.frequent_review_needed) {
            this.success = 'Orden guardado. Revisa las paradas frecuentes ancladas.';
        } else {
            this.success = 'Orden de paradas guardado.';
        }
    },

    async regenerate() {
        if (!this.selectedRoute) return;
        if (!this.confirmDiscardChanges()) return;

        if (this.selectedRoute.has_path && !window.confirm(
            'Esta ruta ya tiene trazado. ¿Regenerarlo desde las paradas actuales?'
        )) {
            return;
        }

        if (this.hasUnsavedChanges) {
            this.resetDraft();
            this.syncSequenceFromSelection();
        }

        const result = await this.run(async () => {
            const response = await axios.post(
                this.selectedRoute.urls.geometry,
                {},
                { headers: this.headers() }
            );

            return response.data;
        });

        if (!result) return;

        await this.refresh({ confirmDiscard: false });
        this.success = `Trazado regenerado con ${result.points ?? 0} puntos.`;
    },

    async toggleRouteStatus() {
        if (!this.selectedRoute) return;
        if (!this.confirmDiscardChanges()) return;

        if (this.hasUnsavedChanges) {
            this.resetDraft();
            this.syncSequenceFromSelection();
        }

        const result = await this.run(async () => {
            const response = await axios.patch(
                this.selectedRoute.urls.status,
                {},
                { headers: this.headers() }
            );

            return response.data;
        });

        if (!result) return;

        await this.refresh({ confirmDiscard: false });
        this.success = result.message ?? 'Estado de la ruta actualizado.';
    },

    validCoordinates() {
        const latitude = Number(this.draft.latitude);
        const longitude = Number(this.draft.longitude);

        return Number.isFinite(latitude)
            && Number.isFinite(longitude)
            && latitude >= -90 && latitude <= 90
            && longitude >= -180 && longitude <= 180;
    },

    async saveStop() {
        if (!String(this.draft.name ?? '').trim()) {
            this.error = 'Escribe el nombre de la parada.';
            return;
        }

        if (!this.validCoordinates()) {
            this.error = 'Revisa las coordenadas: latitud -90 a 90, longitud -180 a 180.';
            return;
        }

        const isCreate = this.mode === 'create';
        const payload = {
            name: String(this.draft.name).trim(),
            address: String(this.draft.address ?? '').trim() || null,
            latitude: Number(this.draft.latitude),
            longitude: Number(this.draft.longitude),
        };

        const result = await this.run(async () => {
            const response = isCreate
                ? await axios.post(this.endpoints.stops.store, payload, { headers: this.headers() })
                : await axios.put(this.selectedStop.urls.update, payload, { headers: this.headers() });

            return response.data;
        }, isCreate ? 'Parada creada.' : 'Parada actualizada.');

        if (!result) return;

        const savedStop = result.stop ?? null;
        this.resetDraft();
        await this.refresh({ confirmDiscard: false, preserveSequence: true });

        if (savedStop && !this.stops.some((stop) => Number(stop.id) === Number(savedStop.id))) {
            this.stops = [savedStop, ...this.stops];
            this.renderMarkers();
        }

        if (savedStop && isCreate) {
            if (this.selectedRoute && !this.sequence.includes(Number(savedStop.id))) {
                this.sequence.push(Number(savedStop.id));
                await this.saveSequence();
            } else {
                this.selectStop(savedStop.id);
            }
        } else if (savedStop) {
            this.selectStop(savedStop.id);
        }
    },

    clearDraft() {
        if (this.stopDraftDirty && !window.confirm('Hay cambios de la parada sin guardar. ¿Deseas descartarlos?')) return;

        this.resetDraft();
    },

    async deleteStop() {
        if (!this.selectedStop) return;
        if (this.stopDraftDirty && !window.confirm('Hay cambios de la parada sin guardar. ¿Deseas descartarlos?')) return;

        if (!window.confirm(`¿Eliminar la parada "${this.selectedStop.name}"?`)) {
            return;
        }

        const destroyUrl = this.selectedStop.urls.destroy;
        this.resetDraft();

        const result = await this.run(async () => {
            const response = await axios.delete(
                destroyUrl,
                { headers: this.headers() }
            );

            return response.data;
        });

        if (!result) return;

        await this.refresh({ confirmDiscard: false, preserveSequence: true });
        this.success = result.message ?? 'Parada eliminada.';
    },

    resetDraft() {
        this.mode = 'create';
        this.selectedStopId = null;
        this.draft = {
            id: null,
            name: '',
            address: '',
            latitude: '',
            longitude: '',
        };
        if (this.map && this.draftMarker) {
            this.markerLayer?.removeLayer(this.draftMarker);
            this.draftMarker = null;
        }
    },
}));
