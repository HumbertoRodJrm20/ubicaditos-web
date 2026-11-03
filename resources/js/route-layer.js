/**
 * Capa compartida para dibujar rutas en Leaflet.
 *
 * Objetivos:
 *  - Usar `route.path_coordinates` (un solo origen de verdad) en lugar de
 *    consultar OSRM cada vez que se abre el mapa.
 *  - Dibujar una sola vez cada tramo (parada A -> parada B) aunque lo compartan
 *    varias rutas. Los tramos compartidos se pintan en gris y su popup indica
 *    qué rutas los usan.
 *  - Evitar polígonos duplicados cuando dos acciones piden dibujar la misma
 *    ruta al mismo tiempo.
 *
 * El módulo no depende de Leaflet al cargarse: sólo lo usa cuando se llama a
 * `createMapRoutes()`.
 */

const DEFAULT_OPTIONS = {
    sharedColor: '#64748b',
    sharedWeight: 4,
    sharedOpacity: 0.55,
    uniqueWeight: 5,
    uniqueOpacity: 0.85,
    routeWeight: 6,
    routeOpacity: 1,
};

function toNumber(value) {
    const number = typeof value === 'number' ? value : parseFloat(value);
    return Number.isFinite(number) ? number : null;
}

function isValidPoint(lat, lng) {
    return lat !== null && lng !== null
        && lat >= -90 && lat <= 90
        && lng >= -180 && lng <= 180
        && !(lat === 0 && lng === 0);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function stopPoint(stop) {
    const lat = toNumber(stop?.latitude ?? stop?.lat);
    const lng = toNumber(stop?.longitude ?? stop?.lng);
    return isValidPoint(lat, lng) ? [lat, lng] : null;
}

/**
 * Paradas de la ruta ordenadas por el pivote `order`.
 *
 * Las paradas de alta frecuencia se excluyen del trazado: se usan para mostrar
 * puntos en el mapa, pero no deben fragmentar la ruta ni alterar la
 * deduplicación de tramos compartidos.
 */
export function orderedStops(route) {
    const stops = Array.isArray(route?.stops) ? route.stops : [];
    return stops
        .filter((stop) => stopPoint(stop))
        .filter((stop) => Number(stop?.pivot?.is_frequent ?? 0) === 0)
        .slice()
        .sort((a, b) => {
            const aOrder = Number(a?.pivot?.order ?? a?.order ?? 0);
            const bOrder = Number(b?.pivot?.order ?? b?.order ?? 0);
            return aOrder - bOrder;
        });
}

/**
 * Convierte `path_coordinates` al formato de Leaflet [[lat, lng], ...].
 * Acepta objetos {lat,lng}, {latitude,longitude} y arreglos GeoJSON [lng,lat].
 */
export function normalizePath(route) {
    const raw = Array.isArray(route?.path_coordinates) ? route.path_coordinates : [];
    const path = [];

    raw.forEach((point) => {
        let lat;
        let lng;

        if (Array.isArray(point)) {
            lng = toNumber(point[0]);
            lat = toNumber(point[1]);
        } else {
            lat = toNumber(point?.lat ?? point?.latitude);
            lng = toNumber(point?.lng ?? point?.longitude);
        }

        if (isValidPoint(lat, lng)) {
            path.push([lat, lng]);
        }
    });

    return path;
}

export function pathFromStops(route) {
    return orderedStops(route).map(stopPoint);
}

/**
 * Trazado de una ruta. Prefiere el guardado en BD y cae a líneas rectas entre
 * paradas si la ruta todavía no tiene `path_coordinates`.
 */
export function pathForRoute(route) {
    const stored = normalizePath(route);
    if (stored.length >= 2) {
        return stored;
    }

    const fromStops = pathFromStops(route);
    return fromStops.length >= 2 ? fromStops : [];
}

function squaredDistance(a, b) {
    const dLat = a[0] - b[0];
    const dLng = a[1] - b[1];
    return dLat * dLat + dLng * dLng;
}

/**
 * Índice del punto del trazado más cercano a cada parada, respetando el orden
 * de recorrido. Si no se puede emparejar de forma monótona se devuelve null y
 * el llamador usa líneas rectas.
 */
function splitIndices(path, stops) {
    const indices = [];
    let cursor = 0;

    for (const stop of stops) {
        const point = stopPoint(stop);
        if (!point) return null;

        let best = -1;
        let bestDistance = Infinity;

        for (let i = cursor; i < path.length; i += 1) {
            const distance = squaredDistance(path[i], point);
            if (distance < bestDistance) {
                bestDistance = distance;
                best = i;
            }
        }

        if (best < 0 || (indices.length > 0 && best < indices[indices.length - 1])) {
            return null;
        }

        indices.push(best);
        cursor = best;
    }

    return indices;
}

function segmentKey(fromStop, toStop) {
    const a = Number(fromStop?.id ?? 0);
    const b = Number(toStop?.id ?? 0);
    return a <= b ? `${a}|${b}` : `${b}|${a}`;
}

/**
 * Divide una ruta en tramos entre paradas consecutivas.
 *
 * Cada tramo tiene una clave estable basada en el par de paradas, de modo que
 * dos rutas que comparten el mismo tramo puedan dibujarlo una sola vez.
 */
export function segmentsForRoute(route) {
    const stops = orderedStops(route);
    if (stops.length < 2) {
        return [];
    }

    const path = pathForRoute(route);
    const indices = path.length >= 2 ? splitIndices(path, stops) : null;
    const segments = [];

    for (let i = 0; i < stops.length - 1; i += 1) {
        const from = stops[i];
        const to = stops[i + 1];
        const fromPoint = stopPoint(from);
        const toPoint = stopPoint(to);

        if (!fromPoint || !toPoint) {
            continue;
        }

        let segmentPath = [fromPoint, toPoint];

        if (indices) {
            const start = indices[i];
            const end = indices[i + 1];
            if (end > start) {
                segmentPath = path.slice(start, end + 1);
            }
        }

        segments.push({
            key: segmentKey(from, to),
            from,
            to,
            path: segmentPath,
        });
    }

    return segments;
}

function routePopup(route) {
    return `
        <div class="font-semibold">${escapeHtml(route.name)}</div>
        <div class="text-sm text-gray-600">${escapeHtml(route.code || '')}</div>
        ${route.description ? `<div class="text-xs mt-1">${escapeHtml(route.description)}</div>` : ''}
    `;
}

function sharedPopup(routes, segment) {
    const labels = routes
        .map((route) => `${escapeHtml(route.code || route.name)}`)
        .join(', ');

    return `
        <div class="font-semibold">Tramo compartido</div>
        <div class="text-xs text-gray-600 mt-1">
            ${escapeHtml(segment.from?.name || '')} → ${escapeHtml(segment.to?.name || '')}
        </div>
        <div class="text-[11px] text-gray-500 mt-1">Rutas: ${labels}</div>
    `;
}

/**
 * Crea el gestor de rutas de un mapa.
 *
 * @param {object} config
 * @param {L.Map} config.map
 * @param {Array} config.routes
 * @param {Function} [config.onRouteClick]
 * @param {object} [config.options]
 */
export function createMapRoutes({ map, routes, onRouteClick, options = {} }) {
    if (!map || typeof L === 'undefined') {
        throw new Error('createMapRoutes requiere un mapa Leaflet y L disponible');
    }

    const settings = { ...DEFAULT_OPTIONS, ...options };
    const routeById = new Map(routes.map((route) => [Number(route.id), route]));
    const pathCache = new Map();
    const lineCache = new Map();

    let network = null;

    function ensurePath(routeId) {
        const id = Number(routeId);
        if (!pathCache.has(id)) {
            const route = routeById.get(id);
            pathCache.set(id, route ? pathForRoute(route) : []);
        }
        return pathCache.get(id);
    }

    function ensureNetwork() {
        if (network) {
            return network;
        }

        const segments = new Map();
        const byRoute = new Map();

        routeById.forEach((route) => {
            const routeSegments = segmentsForRoute(route);
            byRoute.set(Number(route.id), new Set());

            routeSegments.forEach((segment) => {
                if (segment.path.length < 2) {
                    return;
                }

                let entry = segments.get(segment.key);
                if (!entry) {
                    entry = {
                        key: segment.key,
                        from: segment.from,
                        to: segment.to,
                        path: segment.path,
                        routeIds: new Set(),
                    };
                    segments.set(segment.key, entry);
                }

                entry.routeIds.add(Number(route.id));
                byRoute.get(Number(route.id)).add(segment.key);
            });
        });

        const layer = L.layerGroup();
        const segmentLayers = new Map();

        segments.forEach((entry) => {
            const shared = entry.routeIds.size > 1;
            const firstRoute = routeById.get([...entry.routeIds][0]);
            const color = shared
                ? settings.sharedColor
                : (firstRoute?.color || settings.sharedColor);

            const polyline = L.polyline(entry.path, {
                color,
                weight: shared ? settings.sharedWeight : settings.uniqueWeight,
                opacity: shared ? settings.sharedOpacity : settings.uniqueOpacity,
                lineCap: 'round',
                lineJoin: 'round',
            });

            if (shared) {
                const sharedRoutes = [...entry.routeIds]
                    .map((id) => routeById.get(id))
                    .filter(Boolean);
                polyline.bindPopup(sharedPopup(sharedRoutes, entry));
            } else {
                polyline.bindPopup(routePopup(firstRoute));
                polyline.on('click', () => onRouteClick?.(firstRoute));
            }

            segmentLayers.set(entry.key, { polyline, routeIds: entry.routeIds });
            layer.addLayer(polyline);
        });

        network = {
            layer,
            segments: segmentLayers,
            byRoute,
            segmentCount: segments.size,
            sharedCount: [...segments.values()].filter((entry) => entry.routeIds.size > 1).length,
        };

        return network;
    }

    function ensureRouteLine(routeId) {
        const id = Number(routeId);
        if (lineCache.has(id)) {
            return lineCache.get(id);
        }

        const route = routeById.get(id);
        if (!route) {
            return null;
        }

        const path = ensurePath(id);
        if (path.length < 2) {
            return null;
        }

        const polyline = L.polyline(path, {
            color: route.color || '#4b5a85',
            weight: settings.routeWeight,
            opacity: settings.routeOpacity,
            lineCap: 'round',
            lineJoin: 'round',
        });

        polyline.bindPopup(routePopup(route));
        polyline.on('click', () => onRouteClick?.(route));
        lineCache.set(id, polyline);

        return polyline;
    }

    function showNetwork(routeIds = null) {
        const data = ensureNetwork();
        data.layer.addTo(map);
        filterNetwork(routeIds);
        return data;
    }

    function hideNetwork() {
        if (network?.layer) {
            map.removeLayer(network.layer);
        }
    }

    /**
     * Deja visibles sólo los tramos usados por las rutas indicadas.
     * `null` vuelve a mostrar todos los tramos.
     */
    function filterNetwork(routeIds = null) {
        if (!network) {
            return;
        }

        const allowed = routeIds === null ? null : new Set([...routeIds].map(Number));

        network.segments.forEach(({ polyline, routeIds: owners }) => {
            const visible = !allowed || [...owners].some((owner) => allowed.has(owner));

            if (visible && !network.layer.hasLayer(polyline)) {
                network.layer.addLayer(polyline);
            } else if (!visible && network.layer.hasLayer(polyline)) {
                network.layer.removeLayer(polyline);
            }
        });
    }

    function hideAllRouteLines() {
        lineCache.forEach((polyline) => {
            if (map.hasLayer(polyline)) {
                map.removeLayer(polyline);
            }
        });
    }

    function showAllRoutes(routeIds = null) {
        hideAllRouteLines();
        showNetwork(routeIds);
    }

    function showOnlyRoute(routeId) {
        const id = Number(routeId);
        hideNetwork();
        hideAllRouteLines();

        const polyline = ensureRouteLine(id);
        if (polyline) {
            polyline.addTo(map);
            polyline.bringToFront();
        }

        return polyline;
    }

    function getRouteLine(routeId) {
        return lineCache.get(Number(routeId)) || null;
    }

    function getPath(routeId) {
        return ensurePath(routeId);
    }

    function fitRoute(routeId, fitOptions = { padding: [40, 40], maxZoom: 16 }) {
        const polyline = getRouteLine(routeId) || ensureRouteLine(routeId);
        if (!polyline) {
            return false;
        }

        map.fitBounds(polyline.getBounds(), fitOptions);
        return true;
    }

    function stats() {
        const data = ensureNetwork();
        const totalSegments = data.segmentCount;
        const sharedSegments = data.sharedCount;
        const routeSegments = [...data.byRoute.values()].reduce((sum, keys) => sum + keys.size, 0);

        return {
            routes: routeById.size,
            totalSegments,
            sharedSegments,
            deduplicatedSegments: routeSegments - totalSegments,
        };
    }

    return {
        ensureNetwork,
        showNetwork,
        hideNetwork,
        filterNetwork,
        showAllRoutes,
        showOnlyRoute,
        hideAllRouteLines,
        ensureRouteLine,
        getRouteLine,
        getPath,
        fitRoute,
        stats,
    };
}

if (typeof window !== 'undefined') {
    window.UbicaditosRouteLayer = {
        createMapRoutes,
        orderedStops,
        normalizePath,
        pathFromStops,
        pathForRoute,
        segmentsForRoute,
    };
}
