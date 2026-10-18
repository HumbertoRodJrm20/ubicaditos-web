import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Tiempo real de Ubicaditos.
 *
 * Estrategia (en este orden):
 *   1. WebSocket privado "buses" vía Laravel Reverb + Echo (evento BusLocationUpdated).
 *   2. Si el servidor WebSocket no responde en `wsTimeout` ms, se recurre al
 *      polling del endpoint JSON /api/buses/active cada `pollInterval` ms.
 *
 * Ambas fuentes emiten el mismo payload normalizado a los suscriptores:
 *   { id, latitude, longitude, status, current_passengers, available_seats, source, received_at }
 *
 * Uso desde cualquier vista:
 *
 *   const rt = UbicaditosRealtime.start({ pollInterval: 5000 });
 *   rt.onBusLocation(payload => { ...mover marcador... });
 *   rt.onStatus(status => { ...indicador de conexión: 'websocket' | 'polling' | 'offline'... });
 *   rt.refresh(); // descarga completa puntual (útil al cargar la página)
 */
(function (global) {
    const DEFAULTS = {
        // Endpoint JSON usado para la descarga inicial y el fallback de polling.
        endpoint: '/api/buses/active',
        // Frecuencia del fallback por polling (ms).
        pollInterval: 5000,
        // Canal privado de broadcasting.
        channel: 'private-buses',
        // Evento broadcast (ver App\Events\BusLocationUpdated::broadcastAs).
        event: 'bus.location.updated',
        // Cuánto esperar a que el WebSocket se conecte antes de caer al polling (ms).
        wsTimeout: 4000,
        // Clave pública de Reverb (VITE_REVERB_APP_KEY). Si falta, solo polling.
        appKey: (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_REVERB_APP_KEY) || null,
        wsHost: (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_REVERB_HOST) || null,
        wsPort: Number((typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_REVERB_PORT)) || 8080,
        wssPort: Number((typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_REVERB_WSS_PORT)) || 443,
        forceTLS: (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_REVERB_SCHEME) === 'https',
    };

    let instance = null;

    function createInstance(options) {
        const config = Object.assign({}, DEFAULTS, options || {});
        const busListeners = [];
        const statusListeners = [];
        let echo = null;
        let channel = null;
        let pollTimer = null;
        let wsTimer = null;
        let status = 'idle';
        let stopped = false;

        function setStatus(next) {
            if (status === next || stopped) {
                return;
            }
            status = next;
            statusListeners.forEach(listener => {
                try {
                    listener(next);
                } catch (error) {
                    console.error('[ubicaditos:realtime] error en listener de estado', error);
                }
            });
        }

        function emit(payload) {
            if (stopped || !payload || payload.latitude == null || payload.longitude == null) {
                return;
            }

            const normalized = {
                id: payload.id,
                license_plate: payload.license_plate ?? null,
                latitude: Number(payload.latitude),
                longitude: Number(payload.longitude),
                status: payload.status ?? null,
                current_passengers: payload.current_passengers ?? null,
                available_seats: payload.available_seats ?? null,
                route_id: payload.route_id ?? null,
                last_update: payload.last_update ?? null,
                source: payload.source ?? 'unknown',
                received_at: new Date().toISOString(),
            };

            busListeners.forEach(listener => {
                try {
                    listener(normalized);
                } catch (error) {
                    console.error('[ubicaditos:realtime] error en listener de ubicación', error);
                }
            });
        }

        async function fetchOnce() {
            try {
                const response = await fetch(config.endpoint, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                const buses = Array.isArray(data) ? data : (data.buses ?? []);

                buses.forEach(bus => emit(Object.assign({}, bus, { source: 'polling' })));

                return buses;
            } catch (error) {
                console.warn('[ubicaditos:realtime] no se pudo consultar', config.endpoint, error);
                return [];
            }
        }

        function startPolling() {
            if (pollTimer || stopped) {
                return;
            }
            setStatus('polling');
            pollTimer = setInterval(fetchOnce, config.pollInterval);
        }

        function stopPolling() {
            if (!pollTimer) {
                return;
            }
            clearInterval(pollTimer);
            pollTimer = null;
        }

        function connectWebSocket() {
            if (!config.appKey || stopped || echo) {
                return;
            }

            try {
                global.Pusher = global.Pusher || Pusher;

                echo = new Echo({
                    broadcaster: 'reverb',
                    key: config.appKey,
                    wsHost: config.wsHost || global.location.hostname,
                    wsPort: config.wsPort,
                    wssPort: config.wssPort,
                    forceTLS: config.forceTLS,
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                });

                channel = echo.private(config.channel.replace(/^private-/, ''));
                channel.listen(`.${config.event}`, payload => emit(Object.assign({}, payload, { source: 'websocket' })));

                // Si el WebSocket conecta a tiempo, se apaga el polling.
                const pusher = echo.connector.pusher;
                pusher.connection.bind('statechange', states => {
                    if (states.current === 'connected') {
                        setStatus('websocket');
                        stopPolling();
                    } else if (states.current === 'failed' || states.current === 'disconnected') {
                        startPolling();
                    }
                });

                // Si en el plazo no conecta, se mantiene el polling activo.
                wsTimer = setTimeout(() => {
                    if (status !== 'websocket' && !stopped) {
                        startPolling();
                    }
                }, config.wsTimeout);
            } catch (error) {
                console.warn('[ubicaditos:realtime] WebSocket no disponible, solo polling', error);
                echo = null;
            }
        }

        return {
            start() {
                stopped = false;

                // La primera descarga siempre es por HTTP para tener el estado completo.
                fetchOnce().then(() => {
                    if (stopped) {
                        return;
                    }
                    startPolling();
                    connectWebSocket();
                });

                return this;
            },
            stop() {
                stopped = true;
                stopPolling();
                if (wsTimer) {
                    clearTimeout(wsTimer);
                    wsTimer = null;
                }
                if (echo) {
                    try {
                        echo.leave(config.channel.replace(/^private-/, ''));
                        echo.disconnect();
                    } catch (error) {
                        // El desconectar es opcional; se ignora cualquier error.
                    }
                    echo = null;
                    channel = null;
                }
                setStatus('idle');
            },
            refresh: fetchOnce,
            onBusLocation(listener) {
                busListeners.push(listener);
                return this;
            },
            onStatus(listener) {
                statusListeners.push(listener);
                return this;
            },
            getStatus() {
                return status;
            },
        };
    }

    global.UbicaditosRealtime = {
        start(options) {
            if (instance) {
                instance.stop();
            }
            instance = createInstance(options);
            return instance.start();
        },
        stop() {
            if (instance) {
                instance.stop();
                instance = null;
            }
        },
    };
})(window);
