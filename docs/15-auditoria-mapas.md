# Auditoría técnica de mapas (informe histórico)

> **Documento histórico.** Este informe se redactó el 21 de octubre de 2026 como diagnóstico previo a la iteración 3. Varias de sus observaciones ya fueron corregidas: el registro de `Broadcast::routes()`, la autorización del canal privado `buses`, la regeneración de trazados con `routes:generate-paths` y la deduplicación de tramos. Se conserva como antecedente; el estado vigente está en [Iteración 3](13-iteracion-3.md) y [Diagnóstico y optimización del dibujo de rutas](14-diagnostico-y-optimizacion-rutas.md).

**Proyecto:** `ubicaditos-web` (Laravel)
**Estado original:** solo diagnóstico y plan de implementación. **No se modificó código en ese momento.**
**Fecha:** 21 de octubre de 2026

> Convención utilizada en este informe: `[HECHO]` = comprobado leyendo el código; `[HIPÓTESIS]` = deducción que requiere verificación en ejecución (no se ejecutaron migraciones ni se probó la aplicación).


## 1. Resumen general de la implementación actual

Ubicaditos es una aplicación Laravel con tres roles (`admin`, `driver`, `passenger`) protegidos por middlewares de rol (`AdminMiddleware`, `DriverMiddleware`, `PassengerMiddleware`, alias en `bootstrap/app.php`).

El sistema de mapas se basa en:

- **Leaflet 1.9.4** (CDN `unpkg.com`) para la visualización cartográfica en ambas vistas.
- **OpenStreetMap** como capa de base de mapas (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`).
- **OSRM público** (`router.project-osrm.org`) para trazar rutas sobre calles a partir de las paradas (`drawRoute` / `generateRoute`).
- **Laravel Reverb + Laravel Echo + pusher-js** como infraestructura de WebSockets (configurada en `config/reverb.php`, variables `REVERB_*` y `VITE_REVERB_*`).
- Un módulo JS propio `resources/js/realtime.js` (`UbicaditosRealtime`) que combina WebSocket privado con fallback de **polling HTTP cada 5 s** a `/api/buses/active`.

El estado de ubicación de un autobús se guarda en la tabla **`buses`** (columnas `current_latitude`, `current_longitude`, `last_location_update`). El conductor lo actualiza mediante `POST /driver/update-location`; el pasajero lo consulta mediante `GET /api/buses/active`.

**Hallazgo estructural clave:** no se encontró ninguna llamada a `Broadcast::routes()` en el proyecto (grep sin coincidencias), por lo cual el endpoint `/broadcasting/auth` **no está registrado** en la aplicación real. Esto implica que los canales privados de WebSocket (incluido `private-buses`) no pueden autentificarse vía Echo a menos que se añada `Broadcast::routes()`. La aplicación se salva gracias al fallback a polling de `realtime.js`. [HECHO: ausencia de `Broadcast::routes()`; HIPÓTESIS: en producción real solo funciona polling hasta que se agregue.]


## 2. Funcionamiento del mapa del pasajero

### 2.1 Vista y controlador
- **Vista:** `resources/views/passenger/dashboard.blade.php`
- **Controlador/método:** `App\Http\Controllers\Passenger\PassengerController::map()` (ruta `GET /passenger`, nombre `passenger.dashboard`)
- La consulta trae:
  - `Route::with(['stops','buses'])->where('is_active', true)->get()`
  - `Stop::where('is_active', true)->get()`
  - `Bus::with(['driver','routes'])->where('status', 'active')->get()`

### 2.2 Inicialización y visualización del mapa
- Librería: **Leaflet 1.9.4** (CSS y JS por CDN, `<script defer>`).
- Capa base: OpenStreetMap (`L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')`).
- `initMap()` crea `L.map('map').setView([17.0654, -96.7256], 13)` — coordenadas hardcodeadas (aprox. Oaxaca).
- Controles manuales: `+`, `-` y "Mi ubicación" (`map.locate({setView: true, maxZoom: 16})`).
- Eventos Leaflet `locationfound` / `locationerror` manejan la ubicación del **pasajero** (no del conductor).

### 2.3 Cómo aparecen los autobuses y de dónde provienen sus coordenadas
Los autobuses se inyectan al JS como JSON embebido (`@json($buses)`) y se procesan en `initializeSimulatedBuses()`:

- Cada bus del servidor tiene `current_latitude` / `current_longitude` (tabla `buses`).
- `hasRealPosition = bus.current_latitude != null && bus.current_longitude != null`
  - Si **tiene** coordenadas reales: `isReal = true`, el marcador parte de esa coordenada y **no** se mueve por simulación local (recibe actualizaciones por tiempo real).
  - Si **no tiene**: `isReal = false`, se ubica en un punto arbitrario de la trazada OSRM de su ruta (`startProgress = (index * 0.3) % 1`) y se mueve por **simulación local independiente** (`startSimulation` → `moveBus` cada 200 ms).

Por tanto, los marcadores del pasajero son una **mezcla**: buses con posición real reportada por el servidor + buses movidos por simulación local del navegador cuando no hay coordenadas en BD.

### 2.4 Reales, simuladas, estáticas o combinación
**Combinación** [HECHO]:
- Reales: solo si el conductor (o la simulación del conductor) ejecutó `Bus::updateLocation()` y el cliente las recibe vía `/api/buses/active` o WebSocket.
- Simuladas (cliente del pasajero): `moveBus` desplaza marcadores sin coordenadas del servidor por la ruta OSRM, cada 200 ms, independientemente del GPS del conductor.
- Estáticas: las paradas (`Stop`) y los trazados de rutas son estáticas hasta que se filtran.

### 2.5 Actualización de posiciones y frecuencia
Dos mecanismos en paralelo [HECHO]:

1. **`startSimulation()`** (`resources/views/passenger/dashboard.blade.php`): `setInterval(moveBus, 200 ms)` solo para buses `!isReal`. Mueve el marcador localmente sin pedir nada al backend.
2. **`startRealtimeUpdates()`**: instancia `UbicaditosRealtime.start({pollInterval: 5000})` que:
   - Ejecuta `fetchOnce()` al cargar (`GET /api/buses/active`).
   - Inicia polling HTTP cada **5 s** al mismo endpoint.
   - Intenta conectar WebSocket privado `private-buses` (evento `bus.location.updated`); si conecta, apaga el polling; si falla, mantiene polling.

Cuando llega un payload con `latitude`/`longitude`, el JS establece `bus.isReal = true`, detiene el `setInterval` de simulación de ese bus y actualiza `bus.currentPosition` + `setLatLng` del marcador.

### 2.6 Peticiones periódicas, WebSocket o solo JS
**Ambas**: polling HTTP cada 5 s (siempre de respaldo) + WebSocket Reverb si está disponible y configurado. El módulo `UbicaditosRealtime` (`resources/js/realtime.js`) gestiona el fallback automático. No hay uso de `navigator.geolocation` del conductor en el lado del pasajero; el pasajero solo consume lo que el servidor expone.

### 2.7 Relación autobuses ↔ rutas de la BD
- `Bus` belongsToMany `Route` mediante tabla pivote `bus_route` (`app/Models/Bus.php:routes`).
- En el mapa del pasajero se usa `$bus->routes->first()` para obtener la ruta y su `color` (`DashboardController::getActiveBuses`, `Bus` acceso en blade).
- `Route` belongsToMany `Stop` mediante `route_stop` con pivote `order` (`app/Models/Route.php:stops`, `getOrderedStops`).
- `Trip` relaciona `bus_id`, `route_id`, `driver_id` (histórico de viajes, no se usa para dibujar la posición actual).

### 2.8 Dibujo de rutas e información de cada autobús
- Rutas: `drawRoute(route)` consulta OSRM (`router.project-osrm.org/route/v1/driving/{waypoints}?overview=full&geometries=geojson`) con hasta 3 intentos y 2 s de reintento. Si falla todo, **la ruta no se muestra** en la vista del pasajero (no hay fallback de línea recta ahí; el conductor sí tiene `fallbackStraightLine`).
- Se dibuja `L.polyline` con el color de la ruta, popup con nombre/código/descripción.
- Marcador de bus: `L.divIcon` (SVG de autobús dentro de círculo), popup con placa, modelo, ruta, capacidad y ocupación (`current_passengers / capacity`).
- Paradas: marcadores `stopIcon` con popup y botón "Ver tiempos de llegada" (tiempos calculados localmente sobre `simulatedBuses`, no consultados al backend por cada parada).

### 2.9 ¿Qué pasa si no hay coordenadas o falla una petición?
- `/api/buses/active` filtra `whereNotNull(current_latitude) ->whereNotNull(current_longitude)`: **los buses sin coordenadas no aparecen** en el mapa del pasajero. [HECHO]
- `fetchOnce()` atrapa errores, devuelve `[]` y solo avisa por `console.warn`. No hay aviso visual al usuario de "sin datos de flota". [HECHO]
- Si OSRM falla tras 3 intentos, la ruta no se traza y se loguea error en consola. [HECHO]
- La ubicación del pasajero (para centrar el mapa / rutas cercanas) usa `navigator.geolocation.watchPosition`; si falla o el navegador no lo soporta, usa `SIMULATED_LOCATION = {lat: 17.076323, lng: -96.744676}` fijo con `setInterval` cada 2 s. Esto **no se envía al servidor**; solo afecta la vista del pasajero. [HECHO]

### 2.10 ¿El pasajero ve GPS del conductor o simulación independiente?
**Verifica lo siguiente:** el pasajero ve posiciones que provienen de `buses.current_latitude/longitud`, que el conductor puede haber escrito:
- vía GPS real del navegador del conductor (`driver.update-location` → `Bus::updateLocation`), o
- vía simulación del conductor (`useSimulatedLocationDriver` envía coordenadas falsas cada 5 s al mismo endpoint), o
- estar `NULL` (entonces el pasajero simula localmente el movimiento del bus por la ruta).

No hay forma, desde la vista del pasajero ni del API, de distinguir si una coordenada fue capturada por GPS real o generada por simulación. [HECHO]


## 3. Funcionamiento del mapa del conductor

### 3.1 Vista y controlador
- **Vista:** `resources/views/driver/map.blade.php`
- **Controlador/método:** `App\Http\Controllers\Driver\DriverController::map()` (ruta `GET /driver/map`, nombre `driver.map`)
- Obtiene `$bus = auth()->user()->drivingBus`, `$route = $bus->routes->first()`, `$stops = $route->getOrderedStops()`, `$currentTrip`.
- Redirige si el conductor no tiene bus asignado.

### 3.2 Cómo se obtiene la ubicación del conductor
- `initMap()` centra el mapa en `$bus->current_latitude ?? 19.4326` (fallback Ciudad de México) y `$bus->current_longitude ?? -99.1332`.
- `updateLocation()` llama a `navigator.geolocation.watchPosition(...)` con `{enableHighAccuracy: true, maximumAge: 2000, timeout: 5000}`. En cada posición:
  - mueve/crea el marcador del bus en el mapa,
  - calcula velocidad (Haversine),
  - llama `sendLocationToServer(lat, lng)` (fetch POST a `driver.update-location`).
- Si falla la geolocalización o el navegador no la soporta → `useSimulatedLocationDriver()`.

### 3.3 Uso de `navigator.geolocation.watchPosition()` / `getCurrentPosition()`
- Se usa **`watchPosition`** (no `getCurrentPosition`) para seguimiento continuo mientras se comparte la ubicación. [HECHO]
- El botón "Mi ubicación" (`locate-btn`) puede llamar `map.locate({setView: true, maxZoom: 16})`.

### 3.4 Permisos del navegador y manejo de errores
- Requiere permiso de geolocalización del usuario (contexto seguro/HTTPS en producción para `watchPosition` en la mayoría de navegadores modernos).
- Manejo de errores: callback de error de `watchPosition` y evento `map.on('locationerror')` solo loguean en consola y caen a `useSimulatedLocationDriver()`. No hay notificación visual persistente de error al conductor más allá del indicador `gps-indicator`. [HECHO]

### 3.5 Controlador, ruta y método que reciben las coordenadas
- **Ruta:** `POST /driver/update-location` → nombre `driver.update-location` (`routes/web.php:63`)
- **Controlador:** `App\Http\Controllers\Driver\DriverController::updateLocation(Request $request)` (`app/Http/Controllers/Driver/DriverController.php:271`)
- Valida `latitude` (required|numeric) y `longitude` (required|numeric).
- Obtiene `$bus = auth()->user()->drivingBus`; si no hay bus, devuelve 400.
- Llama `$bus->updateLocation($latitude, $longitude)` (modelo `Bus`).

### 3.6 Datos guardados, tabla y modelo
- **Tabla:** `buses` (migración `2025_12_10_201756_create_buses_table.php`)
- **Columnas:** `current_latitude` (decimal 10,7), `current_longitude` (decimal 10,7), `last_location_update` (timestamp)
- **Modelo:** `app/Models/Bus.php`, método `updateLocation($latitude, $longitude)` (líneas 94-103) que actualiza esas columnas y dispara `event(new \App\Events\BusLocationUpdated($this))`.
- **Nota:** el modelo `User` también tiene columnas `current_latitude/longitud/last_location_update` pero **no se usan** para guardar la ubicación del conductor en este flujo; solo se escribe en `buses`. [HECHO]
- No se guarda histórico de posiciones; se sobrescribe la última ubicación. No hay precisión, rumbo, velocidad ni fuente (GPS/simulado) almacenados.

### 3.7 Relación conductor ↔ usuario ↔ autobús
- `User::drivingBus()` → `hasOne(Bus::class, 'driver_id')` (`app/Models/User.php:64`)
- `Bus::driver()` → `belongsTo(User::class, 'driver_id')` (`app/Models/Bus.php:55`)
- El `driver_id` del bus lo asigna el administrador (`AdminController::assignBusToDriver` / `unassignBusFromDriver`, rutas `admin/drivers/{driver}/assign-bus` y `unassign-bus`).

### 3.8 ¿Cómo se actualiza la ubicación?
- **Automática al moverse:** `watchPosition` dispara `sendLocationToServer` en cada fix del GPS mientras `share-location` esté activo y no haya viaje bloqueando.
- **Simulación automática:** si el toggle "Ubicación Simulada" está activo, `useSimulatedLocationDriver` reenvía `SIMULATED_LOCATION` con deriva aleatoria (±0.0003) cada **5 s** al servidor.
- **Manual:** click en el mapa (modo simulado) establece `customSimulatedLocation` y, si compartir está activo, envía al servidor.
- No hay envío por intervalo fijo en modo GPS real además del nativo del navegador (`watchPosition`).

### 3.9 Validaciones y autorización
- **Middleware:** `auth` + `driver` (`DriverMiddleware` verifica `auth()->user()->isDriver()`) sobre el grupo `driver.*`.
- **En `updateLocation`:** solo usa `auth()->user()->drivingBus`, por lo que un conductor solo puede actualizar el bus que le está asignado. No puede actualizar la ubicación del bus de otro conductor porque `drivingBus` filtra por `driver_id = auth()->id()`. [HECHO]
- **Faltantes:** no se valida que el conductor esté `is_active`, que el bus esté `status='active'`, que exista un viaje en progreso, ni rangos razonables de latitud/longitud (cualquier valor numérico pasa). No hay distinción de origen (GPS real vs simulado) al guardar.


## 4. Archivos, clases, rutas, tablas y métodos implicados

### Rutas Laravel (`routes/web.php`)
| Ruta | Método | Controlador@método | Middleware |
|------|--------|-------------------|------------|
| `/passenger` (mapa) | GET | `Passenger\PassengerController@map` | auth, passenger |
| `/passenger/buses` | GET | `Passenger\PassengerController@buses` | auth, passenger |
| `/passenger/routes` | GET | `Passenger\PassengerController@routes` | auth, passenger |
| `/driver/map` | GET | `Driver\DriverController@map` | auth, driver |
| `/driver/update-location` | POST | `Driver\DriverController@updateLocation` | auth, driver |
| `/driver/start-trip` | POST | `Driver\DriverController@startTrip` | auth, driver |
| `/driver/end-trip` | POST | `Driver\DriverController@endTrip` | auth, driver |
| `/api/buses/active` | GET | `DashboardController@getActiveBuses` | auth |
| `/api/buses/{bus}/location` | GET | `DashboardController@getBusLocation` | auth |
| `/api/routes/{route}/buses` | GET | `DashboardController@getRouteBuses` | auth |
| `/broadcasting/auth` | POST | (Laravel) | **NO REGISTRADO** (falta `Broadcast::routes()`) |

### Controladores y métodos
- `app/Http/Controllers/Passenger/PassengerController.php`: `map`, `buses`, `routes`
- `app/Http/Controllers/Driver/DriverController.php`: `map`, `updateLocation`, `startTrip`, `endTrip`, `updatePassengers`, `reportEmergency`
- `app/Http/Controllers/DashboardController.php`: `getActiveBuses`, `getBusLocation`, `getRouteBuses`
- `app/Http/Controllers/Admin/AdminController.php`: `drivers`, `assignBusToDriver`, `unassignBusFromDriver`

### Modelos y relaciones
- `app/Models/Bus.php`: `updateLocation()`, `driver()`, `routes()` (belongsToMany vía `bus_route`), `trips()`, fillable incluye `current_latitude`, `current_longitude`, `last_location_update`, `driver_id`, `status`, `is_active`, `current_passengers`.
- `app/Models/User.php`: `drivingBus()` (hasOne Bus por `driver_id`), `trips()`, rol `isDriver()/isPassenger()/isAdmin()`.
- `app/Models/Route.php`: `buses()`, `stops()`, `getOrderedStops()`.
- `app/Models/Stop.php`: `latitude`, `longitude`.
- `app/Models/Trip.php`: `bus_id`, `route_id`, `driver_id`, `status` (`in_progress`/`completed`/`cancelled`).

### Migraciones y tablas relevantes
- `2025_12_10_201756_create_buses_table.php` → tabla `buses` (`current_latitude`, `current_longitude`, `last_location_update`, `driver_id`, `status`, `is_active`).
- `2025_12_10_201755_add_role_to_users_table.php` → columnas `role`, `is_active`, `current_latitude`, `current_longitude`, `last_location_update` en `users` (estas dos últimas **no se usan** para la ubicación del conductor en el flujo actual).
- `2025_12_10_201757_create_routes_table.php`, `2025_12_10_201758_create_stops_table.php`, `2025_12_10_201759_create_bus_route_table.php`, `2025_12_10_201800_create_route_stop_table.php`, `2025_12_10_201801_create_trips_table.php`.

### Eventos y broadcasting
- `app/Events/BusLocationUpdated.php`: implementa `ShouldBroadcastNow`, canal `private-buses`, evento `bus.location.updated`, payload `{id, license_plate, latitude, longitude, status, current_passengers, available_seats, route_id, last_update}`.
- `routes/channels.php`: `Broadcast::channel('buses', fn($user) => $user !== null)` (cualquier autenticado).
- **Falta:** `Broadcast::routes()` no se llama en ningún archivo del proyecto (grep sin coincidencias) → `/broadcasting/auth` no existe en la app real.

### JavaScript
- `resources/js/realtime.js`: `UbicaditosRealtime` (WebSocket Reverb + polling fallback a `/api/buses/active` cada 5 s, `wsTimeout` 4000 ms).
- `resources/js/app.js`: importa `./bootstrap` y `./realtime`, inicia Alpine.
- `resources/js/bootstrap.js`: solo axios.
- `resources/views/passenger/dashboard.blade.php`: `initMap`, `getUserLocation` (watchPosition pasajero), `initializeSimulatedBuses`, `drawBuses`, `startSimulation` (200 ms), `startRealtimeUpdates`, `drawRoute` (OSRM).
- `resources/views/driver/map.blade.php`: `initMap`, `updateLocation` (watchPosition conductor), `sendLocationToServer` (POST `driver.update-location`), `useSimulatedLocationDriver` (simulación cada 5 s), `applySharingState` (localStorage `ubicaditos_share_location`), `generateRoute` (OSRM).

### Archivos de configuración/entorno
- `config/reverb.php`: servidor Reverb.
- `.env.example`: `BROADCAST_CONNECTION=reverb`, `REVERB_*`, `VITE_REVERB_*` (sin valores reales; son placeholders).
- `package.json`: `laravel-echo ^2.6.1`, `pusher-js ^8.6.0`.
- `composer.json`: `laravel/reverb ^1.12`.


## 5. Flujo de datos de las coordenadas

### 5.1 Flujo del conductor (implementado parcialmente)
```
GPS del navegador (watchPosition)
        ↓ JavaScript (driver/map.blade.php: updateLocation → sendLocationToServer)
        ↓ fetch POST /driver/update-location  (CSRF token, JSON {latitude, longitude})
        ↓ Laravel: DriverController@updateLocation
        ↓ valida latitude/longitude numeric
        ↓ $bus = auth()->user()->drivingBus
        ↓ Bus::updateLocation($lat, $lng)
        ↓ actualiza buses.current_latitude, current_longitude, last_location_update
        ↓ event(new BusLocationUpdated($bus))  → broadcast canal private-buses (si funcionara el auth WS)
```

- Parte **real**: `navigator.geolocation.watchPosition`.
- Parte **simulada**: toggle "Ubicación Simulada" / click en mapa / fallback ante error de GPS → `useSimulatedLocationDriver` envía coordenadas inventadas al mismo endpoint.
- Ambos flujos convergen en las mismas columnas de `buses`; no hay distinción de origen.

### 5.2 Flujo del pasajero (implementado parcialmente)
```
Carga del mapa (initMap)
        ↓ consulta de autobuses: @json($buses) desde PassengerController@map (solo status='active')
        ↓ drawStops / loadVisibleRoutes (OSRM para trazados)
        ↓ getUserLocation (watchPosition pasajero o SIMULATED_LOCATION fija)  → solo para centrar/rutas cercanas, NO se envía al servidor
        ↓ initializeSimulatedBuses: si bus tiene coordenadas reales → isReal=true; si no → simulación local sobre ruta OSRM
        ↓ drawBuses (marcadores Leaflet)
        ↓ startSimulation (setInterval 200 ms para buses !isReal)
        ↓ startRealtimeUpdates: fetchOnce + polling /api/buses/active cada 5 s + WebSocket private-buses
        ↓ al recibir payload con lat/lng: isReal=true, se detiene simulación local del bus, setLatLng del marcador
```

### 5.3 ¿Existe mecanismo para que el pasajero consulte la ubicación del conductor?
**Sí, pero indirecta y con limitaciones:**
- El conductor escribe en `buses.current_latitude/longitud` vía `driver.update-location`.
- El pasajero lee esa misma fila a través de `GET /api/buses/active` (usado por `UbicaditosRealtime`) o `GET /api/buses/{bus}/location`.
- El evento `BusLocationUpdated` se dispara al actualizar, pero **el canal privado no se puede autenticar en la app real por falta de `Broadcast::routes()`**, por lo que en práctica el pasajero recibe la ubicación mediante **polling HTTP cada 5 s**, no por WebSocket. [HIPÓTESIS: el flujo real dominante es polling.]


## 6. Diferencias entre simulación y ubicación GPS real

| Aspecto | Ubicación GPS real | Simulación actual |
|---------|-------------------|-------------------|
| Origen conductor | `navigator.geolocation.watchPosition` (permiso usuario) | `useSimulatedLocationDriver`: `SIMULATED_LOCATION` fija con deriva ±0.0003 cada 5 s, o click en mapa |
| Origen pasajero (centrar mapa) | `watchPosition` del pasajero | `SIMULATED_LOCATION = {17.076323, -96.744676}` fijo |
| Marcadores de bus en pasajero | `isReal=true` si llegó vía API/WS con lat/lng | `moveBus` cada 200 ms sobre ruta OSRM (local, sin servidor) |
| Almacenamiento BD | `buses.current_latitude/longitud/last_location_update` | Misma tabla y columnas (sin distinguir origen) |
| Indicador al pasajero | Ninguno que distinga GPS real vs simulado | Ninguno |
| Frecuencia conductor | Según fix del navegador (watchPosition) | Fija cada 5 s |
| Frecuencia pasajero (sim buses) | — | Cada 200 ms (local) |

**Conclusión:** hoy no es posible saber, ni desde el backend ni desde el frontend del pasajero, si una posición mostrada corresponde al GPS real del conductor o a una simulación. [HECHO]


## 7. Problemas y limitaciones detectados

1. **Falta `Broadcast::routes()`** → el endpoint `/broadcasting/auth` no está registrado; los canales privados (incluido `private-buses`) no se autentican con Echo. En la práctica solo funciona polling. `[HECHO: ausencia de la llamada; HIPÓTESIS: impacto en producción]`
2. **Sin distinción real/simulado en BD ni API** → `buses.current_latitude` guarda indistintamente GPS real y coordenadas inventadas; no hay campo `source`, `accuracy`, ni `heading`. `[HECHO]`
3. **Sin expiración/frescura de ubicación** → `last_location_update` se guarda pero `getActiveBuses` no filtra por antigüedad; un bus con ubicación de hace horas sigue apareciendo como activo. `[HECHO]`
4. **Sin validación de coordenadas** → `updateLocation` acepta cualquier valor numérico (latitud 999, longitud -5000 pasan la validación). `[HECHO]`
5. **Sin autorización contextual en `updateLocation`** → no verifica que el conductor esté `is_active`, que el bus esté `status='active'`, ni que haya un viaje en curso. Cualquier conductor con bus asignado puede enviar ubicación en cualquier momento. `[HECHO]`
6. **Canal de posiciones sin filtrado de privacidad** → `Broadcast::channel('buses', fn($user) => $user !== null)` autoriza a cualquier autenticado (pasajero incluido) a recibir posiciones de todos los buses. `[HECHO]`
7. **Dependencia de OSRM público sin clave** → `router.project-osrm.org` es un servicio público gratuito; si falla o limita, las rutas no se dibujan en el pasajero (sin fallback) y el conductor usa líneas rectas. `[HECHO]`
8. **Centro del mapa hardcodeado e inconsistente** → pasajero: `[17.0654, -96.7256]` (Oaxaca); conductor: `[19.4326, -99.1332]` (CDMX) como fallback. `[HECHO]`
9. **El módulo `realtime.js` depende de `VITE_REVERB_APP_KEY`** (placeholders en `.env.example`); sin valores reales ni servidor Reverb arrancado, solo polling. `[HECHO]`
10. **Limitaciones del navegador (geolocalización):** requiere permiso del usuario; en producción exige HTTPS (secure context) para `watchPosition`; el GPS puede no estar disponible; en segundo plano (pestaña oculta / pantalla apagada) iOS y Android restringen o detienen `watchPosition`, lo que detendría el envío de ubicación del conductor. `[HIPÓTESIS basada en comportamiento estándar de navegadores]`
11. **No hay throttling del envío del conductor** → `watchPosition` puede disparar muchos POST por segundo; no hay control de distancia mínima ni intervalo mínimo entre envíos. `[HECHO]`
12. **El pasajero no recibe indicador de "desactualizado"** → si un bus deja de enviar, su marcador se queda en la última posición conocida sin advertencia de antigüedad. `[HECHO]`


## 8. Arquitectura recomendada para ubicación GPS real

### Principios
- Reutilizar la tabla `buses` y las columnas existentes; agregar metadatos mínimos para distinguir origen y frescura.
- Mantener el fallback a polling HTTP (ya implementado) para no añadir complejidad innecesaria; completar el canal WebSocket solo si se requiere menor latencia.
- Separar claramente ubicación del **conductor** (envío) de ubicación del **pasajero** (solo lectura/consulta).

### 8.1 Conductor comparte ubicación real
1. En el navegador del conductor, pedir permiso de geolocalización solo cuando el usuario active "Compartir mi ubicación" (toggle persistente en `localStorage`).
2. Usar `navigator.geolocation.watchPosition({enableHighAccuracy: true, maximumAge: 2000, timeout: 5000})`.
3. Aplicar **throttling**: enviar solo si han pasado al menos N segundos (ej. 5-10 s) o si la posición cambió más de X metros, para evitar peticiones excesivas.
4. Enviar `POST /driver/update-location` con `{latitude, longitude}`.
5. Backend validar:
   - `auth()->check()` y rol `driver` (ya por middleware).
   - `$bus = auth()->user()->drivingBus` exista (conductor tiene bus asignado).
   - (Opcional) conductor `is_active` y bus `status='active'`; (opcional) viaje en progreso si se desea restringir el envío solo durante el servicio.
   - Rangos válidos: latitud [-90, 90], longitud [-180, 180].
6. Guardar en `buses`: `current_latitude`, `current_longitude`, `last_location_update`. Agregar columnas `location_source` (`'gps'`/`'simulated'`) y `location_accuracy` (metros) para trazabilidad.
7. Disparar `BusLocationUpdated` (ya existe) para notificar a suscriptores.

### 8.2 Pasajero consulta la última ubicación válida
- `GET /api/buses/active` debe devolver además `source`, `last_update` (ISO), y opcionalmente un booleano `is_fresh` (ej. ubicación menor a 5 minutos).
- Filtrar por `status='active'` y coordenadas no nulas (ya lo hace). Considerar excluir ubicaciones muy antiguas o marcarlas como desactualizadas en el frontend.
- `GET /api/buses/{bus}/location` ya existe para consulta individual.

### 8.3 Actualización periódica de marcadores sin recargar página
- Seguir usando `UbicaditosRealtime` (polling 5 s + WebSocket Reverb como mejora opcional).
- Al recibir cada payload, actualizar `setLatLng` del marcador y refrescar el popup (ocupación, hora de última actualización).
- Mostrar indicador por bus: "en vivo" (reciente), "desactualizado" (antiguo) o "sin datos".

### 8.4 Ubicación desactualizada o no disponible
- Si `last_update` es mayor a umbral (ej. 5-10 min), mostrar el marcador con estilo atenuado y leyenda "Ubicación desactualizada".
- Si no hay coordenadas, no mostrar el autobús en el mapa (comportamiento actual de `getActiveBuses`).

### 8.5 Control de frecuencia
- Backend: rate-light simple por conductor (throttle de aplicación) o dejar que el frontend throttlee `watchPosition`.
- Frontend pasajero: polling 5 s es razonable para una flota de autobuses; no requiere WebSocket obligatorio.

### 8.6 Permisos, roles y privacidad
- Middleware `driver` ya restringe `update-location` a conductores.
- `drivingBus` asegura que un conductor solo actualice su propio bus.
- Canal `private-buses`: cualquier autenticado recibe todas las posiciones. Si se requiere privacidad, se podría filtrar por ruta/bus autorizados, pero para el alcance actual (pasajeros ven la flota pública) el canal abierto a autenticados es aceptable. Se recomienda documentarlo como decisión de diseño.

### 8.7 Polling vs WebSocket
| Criterio | Polling HTTP (actual) | WebSocket (Reverb + Echo) |
|----------|----------------------|---------------------------|
| Complejidad | Baja, ya implementado | Media (servidor Reverb, `Broadcast::routes()`, env vars, HTTPS/WSS en prod) |
| Latencia | Hasta 5 s | Bajo (tiempo real) |
| Carga servidor | Peticiones cada 5 s por cliente | Conexión persistente, eventos push |
| Fiabilidad | Alta (sobre HTTP) | Requiere servidor Reverb levantado y auth de canales |
| Recomendación | **Mantener como base** | **Completar como mejora opcional** (ya tiene Reverb instalado y configurado; solo falta `Broadcast::routes()` y credenciales reales) |

**Recomendación realista:** implementar polling cada 5 s como mecanismo principal (ya funciona, sin infraestructura extra) y completar el soporte WebSocket (añadir `Broadcast::routes()`, configurar `REVERB_*` reales, arrancar `php artisan reverb:start`) solo si se desea reducir la latencia. No introducir ambos como obligatorios.

### 8.8 Limitaciones de geolocalización del navegador a considerar
- Requiere permiso explícito del usuario.
- HTTPS obligatorio en producción para `watchPosition` en la mayoría de navegadores.
- GPS puede estar desactivado o sin señal (interiores, túneles).
- En segundo plano (pestaña no visible, pantalla apagada) los navegadores móviles limitan o detienen `watchPosition`; no se puede garantizar ubicación continua del conductor sin una app nativa o notificaciones push/background geofencing (fuera del alcance de esta etapa).
- `maximumAge` y `timeout` ya configurados; considerar manejar `PositionError` con mensajes claros al conductor.


## 9. Plan de implementación por etapas

### Etapa 1 — Habilitar la infraestructura de tiempo real (WebSocket auth)
- **Archivo:** `routes/channels.php` → agregar `Broadcast::routes();` antes de `Broadcast::channel('buses', ...)`.
- **Archivo:** `.env` (y ejemplos) → completar `REVERB_APP_ID/KEY/SECRET/HOST/PORT/SCHEME` y `VITE_REVERB_*`.
- **Comando:** `php artisan reverb:start` (en producción, como servicio).
- **Verificación:** que `POST /broadcasting/auth` responda y que Echo se suscriba a `private-buses`.
- **Alternativa si no se usa WS:** dejar polling como único mecanismo y omitir esta etapa.

### Etapa 2 — Persistencia con trazabilidad de origen y precisión
- **Nueva migración:** agregar a tabla `buses`: `location_source` (enum `'gps','simulated'`), `location_accuracy` (decimal/nteger nullable). Índice opcional en `last_location_update`.
- **`app/Models/Bus.php`:** agregar campos a `$fillable` y casts; `updateLocation()` aceptar `source` y `accuracy` y guardarlos; el evento `BusLocationUpdated` incluir `source` y `last_update` en el payload.
- **Nota:** no tocar columnas de `users` (`current_latitude` de `User`) a menos que se decida usarlas; el flujo actual usa `buses`.

### Etapa 3 — Backend: validación y autorización del envío del conductor
- **`app/Http/Controllers/Driver/DriverController.php` → `updateLocation`:**
  - Validar rangos: latitud [-90, 90], longitud [-180, 180].
  - Verificar `$bus = auth()->user()->drivingBus` (ya existe); opcionalmente exigir `$driver->is_active` y `$bus->status === 'active'`.
  - Guardar `location_source = 'gps'` cuando provenga de `navigator.geolocation`; `'simulado'` solo para pruebas/desarrollo.
  - (Opcional) throttling de aplicación: rechazar si el último envío del mismo conductor fue hace menos de N segundos.

### Etapa 4 — Frontend del conductor: envío real con control
- **`resources/views/driver/map.blade.php`:**
  - `updateLocation()`: solo llamar `sendLocationToServer` si el toggle "Compartir mi ubicación" está activo.
  - Agregar throttling (tiempo mínimo y/o distancia mínima entre envíos).
  - Distinguir visualmente GPS real vs simulado (indicador en UI).
  - Mantener el fallback a simulación solo si el usuario lo elige explícitamente (desactivado por defecto en producción).
  - Manejo de errores de geolocalización con aviso visible al conductor.

### Etapa 5 — Backend: consulta del pasajero con frescura
- **`app/Http/Controllers/DashboardController.php` → `getActiveBuses`:**
  - Agregar `source`, `last_update` (ISO 8601) al payload.
  - Opcional: agregar filtro o bandera `is_fresh` según `last_location_update` (ej. < 5 min).
- **`getBusLocation`:** incluir `source` y `last_update`.

### Etapa 6 — Frontend del pasajero: marcadores con estado de frescura
- **`resources/views/passenger/dashboard.blade.php`:**
  - Al recibir cada ubicación, mostrar indicador por bus: en vivo / desactualizado / sin datos (según antigüedad de `last_update`).
  - No mezclar GPS real y simulado sin indicarlo; si se conserva la simulación local para pruebas, marcar esos marcadores como "simulado".
  - Mantener `startRealtimeUpdates` (polling 5 s / WebSocket).
  - Manejo graceful si `/api/buses/active` falla (aviso visual, no solo consola).

### Etapa 7 — Desactivar simulación por defecto en producción
- Feature flag o variable de entorno para que el modo "Ubicación Simulada" del conductor no esté activo por defecto en entornos productivos.

### Archivos que probablemente sería necesario modificar (resumen)
1. `routes/channels.php` — `Broadcast::routes()`
2. `.env` / `.env.example` / `config/reverb.php` — credenciales Reverb
3. `database/migrations/YYYY_MM_DD_HHMMSS_add_location_metadata_to_buses_table.php` — nuevas columnas
4. `app/Models/Bus.php` — fillable, casts, `updateLocation`, evento
5. `app/Events/BusLocationUpdated.php` — payload (source, accuracy, last_update)
6. `app/Http/Controllers/Driver/DriverController.php` — `updateLocation` (validaciones, autorización, throttling)
7. `app/Http/Controllers/DashboardController.php` — `getActiveBuses`, `getBusLocation` (source, frescura)
8. `resources/views/driver/map.blade.php` — envío condicional, throttling, UI real vs simulado
9. `resources/views/passenger/dashboard.blade.php` — indicador de frescura/origen por bus
10. `resources/js/realtime.js` — (opcional) ajustar pollInterval o manejo de estado de conexión


## Nota final

Este informe es exclusivamente diagnóstico. Todos los hallazgo marcados `[HECHO]` se verificaron leyendo el código del repositorio; los marcados `[HIPÓTESIS]` requieren comprobación en ejecución (levantar servidor, Reverb y probar los endpoints). No se ha modificado ningún archivo, ni se ha ejecutado migración, ni se ha instalado dependencia.
