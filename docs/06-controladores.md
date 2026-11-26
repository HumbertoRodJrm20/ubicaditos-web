# Controladores

Los controladores están en `app/Http/Controllers` y se agrupan por rol en subcarpetas (`Admin`, `Driver`, `Passenger`, `Auth`). Todos extienden la clase base abstracta `App\Http\Controllers\Controller`.

Convenciones comunes:

- **Route model binding:** los parámetros tipados (`Bus $bus`, `Route $route`, `User $user`, `Stop $stop`) se resuelven automáticamente desde la URL.
- **Validación inline** con `$request->validate()` y mensajes de error personalizados en español.
- **Mensajes flash:** las acciones redirigen con `->with('success', ...)` o `->with('error', ...)`, que las vistas muestran como alertas.
- **Búsqueda y paginación:** los listados aceptan `?search=` y `?per_page=` (15 por defecto) y conservan los parámetros con `withQueryString()`.

## Mapa de controladores

| Controlador | Middleware | Responsabilidad |
|-------------|------------|-----------------|
| `DashboardController` | `auth` | Redirección por rol y endpoints JSON de autobuses. |
| `Api\ArrivalEstimateController` | `auth`, `roles` | ETA por parada con banderas de frescura e incertidumbre. |
| `ProfileController` | `auth` | Edición del perfil; eliminación del pasajero o desactivación segura del conductor. |
| `Admin\AdminController` | `auth`, `admin` | Dashboard, estadísticas, conductores, pasajeros y emergencias. |
| `Admin\BusController` | `auth`, `admin` | CRUD de autobuses y detalle (`show`). |
| `Admin\RouteController` | `auth`, `admin` | CRUD de rutas y sus paradas. |
| `Admin\UserController` | `auth`, `admin` | Alta, edición y baja de usuarios. |
| `Admin\ReportController` | `auth`, `admin` | Reportes, gráficas y exportación PDF/CSV. |
| `Admin\IncidentManagementController` | `auth`, `admin` | Listado global, filtros y cambio de estado de los reportes ciudadanos. |
| `Admin\StopController` | `auth`, `admin` | CRUD de paradas, catálogo JSON acotado y eliminación segura. |
| `Driver\DriverController` | `auth`, `driver` | Panel, mapa, estadísticas, viajes, GPS, pasajeros y emergencias. |
| `Passenger\PassengerController` | `auth`, `passenger` | Mapa, rutas, autobuses, favoritos, calificaciones y accesos a reportes. |
| `Passenger\FavoriteRouteController` | `auth`, `passenger` | Alta, baja, orden y etiqueta de rutas favoritas. |
| `Passenger\EmergencyController` | `auth`, `passenger` | Formulario y envío de emergencias del pasajero (RF-19). |
| `Passenger\NotificationSubscriptionController` | `auth`, `passenger` | Suscripciones in-app autobús/parada, idempotentes y privadas. |
| `LikeController` | `auth` | Toggle de me gusta sobre rutas y paradas (cualquier rol). |
| `LocationShareController` | `auth` / público | Enlaces temporales de ubicación y consulta pública del token. |
| `IncidentController` | `auth` | Reportes ciudadanos del propio usuario: listado, formulario y registro. |
| `Auth\*` | `guest` / `auth` | Autenticación de Laravel Breeze. |

## DashboardController

`App\Http\Controllers\DashboardController`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index()` | `GET /dashboard` | Redirige a `admin.dashboard`, `driver.dashboard` o `passenger.dashboard` según el rol; responde 403 si el rol es desconocido. |
| `getActiveBuses()` | `GET /api/buses/active` | JSON con los autobuses activos que tienen coordenadas: placa, posición, capacidad, pasajeros, asientos libres (`available_seats`, calculados), conductor, ruta, color de ruta, última actualización, `accuracy`, `fresh`, `fresh_until` y `sharing_passengers_count`. Además del consumo directo, es el **fallback de polling** (cada 5 s) que usa `resources/js/realtime.js` cuando el WebSocket no está disponible. |
| `getBusLocation(Bus $bus)` | `GET /api/buses/{bus}/location` | JSON con posición, estado, pasajeros, fecha de actualización, precisión, frescura y conteo agregado de un autobús. |
| `getRouteBuses(Route $route)` | `GET /api/routes/{route}/buses` | JSON con los autobuses activos de una ruta, su conductor y los mismos campos de frescura/precisión/conteo. |

El formato de las respuestas JSON se detalla en [Rutas y API](08-rutas.md#endpoints-json). Los endpoints están detrás de `auth` y del middleware `roles:admin,driver,passenger`, que declara explícitamente la matriz de roles (RNF-02). `/api/share/{token}` es público y no incluye nombre, correo ni ID del pasajero.

## Api\ArrivalEstimateController

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index(Stop $stop)` | `GET /api/stops/{stop}/eta` | Delegado a `ArrivalEstimateService`, devuelve la ETA por autobús activo que atiende la parada, con `estimated`, `uncertain`, `notice` y `eta_minutes` nulo cuando la posición está antigua o no hay servicio. Sin OSRM en tiempo de consulta. |

## Admin\AdminController

| Método | Ruta | Vista | Descripción |
|--------|------|-------|-------------|
| `index()` | `GET /admin` | `admin.dashboard` | Estadísticas generales (autobuses totales y activos, rutas, paradas, conductores, pasajeros, viajes de hoy), calificación promedio global y su cantidad de votos, últimos 10 viajes, autobuses activos y datos para el mapa en vivo (rutas activas con paradas y autobuses, paradas activas). |
| `drivers(Request)` | `GET /admin/drivers` | `admin.drivers` | Lista paginada solo de conductores activos, con búsqueda por nombre, apellidos o correo; incluye autobús asignado, número de viajes y calificación promedio; y la lista de autobuses sin conductor para asignar. |
| `assignBusToDriver(Request, User $driver)` | `POST /admin/drivers/{driver}/assign-bus` | — | Valida `bus_id`, verifica que el destinatario tenga rol `driver` (403 en caso contrario; RNF-02), libera el autobús que el conductor tuviera antes y asigna el nuevo. Garantiza la relación uno a uno conductor ↔ autobús. |
| `unassignBusFromDriver(User $driver)` | `DELETE /admin/drivers/{driver}/unassign-bus` | — | Quita el conductor de su autobús. |
| `passengers(Request)` | `GET /admin/passengers` | `admin.passengers` | Lista paginada de pasajeros con búsqueda; incluye conteo de calificaciones emitidas y paradas favoritas. |
| `emergencies(Request)` | `GET /admin/emergencies` | `admin.emergencies` | Listado de emergencias reportadas por conductores o pasajeros (con `reporter` y `bus`), filtrable por `status` (`pending`, `in_progress`, `resolved`) y `type` (`accident`, `breakdown`, `medical`, `security`, `other`); incluye contadores por estado, etiquetas traducidas y ordena las pendientes primero con el distintivo «Requiere atención». |
| `resolveEmergency(EmergencyReport $emergency)` | `POST /admin/emergencies/{emergency}/resolve` | — | Marca la incidencia como `resolved` con `resolved_at = now()`, **solo si estaba `pending`** (en caso contrario redirige con mensaje de error). Al resolverse, si el autobús asociado estaba en estado `emergency`, **vuelve a `active`**. Redirige al listado con mensaje de éxito. |

## Admin\BusController

Controlador de recurso (`Route::resource('buses', ...)`); además de las operaciones CRUD, implementa `show()`.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index(Request)` | `GET /admin/buses` | Lista paginada con búsqueda por placa, modelo o nombre del conductor. |
| `show(Bus $bus)` | `GET /admin/buses/{bus}` | Detalle del autobús: carga conductor y rutas, últimos 10 viajes (con ruta y conductor) y últimas 5 emergencias. |
| `create()` | `GET /admin/buses/create` | Formulario con todas las rutas y solo los conductores **sin** autobús asignado. |
| `store(Request)` | `POST /admin/buses` | Valida, crea el autobús y sincroniza sus rutas (`routes[]`). |
| `edit(Bus $bus)` | `GET /admin/buses/{bus}/edit` | Formulario con conductores libres más el conductor actual. |
| `update(Request, Bus $bus)` | `PUT/PATCH /admin/buses/{bus}` | Valida, actualiza y sincroniza rutas. |
| `destroy(Bus $bus)` | `DELETE /admin/buses/{bus}` | Bloquea la acción si hay viaje activo o conductor asignado; si existen viajes, rutas, mensajes o reportes, desactiva la unidad (`is_active = false`, `status = inactive`) y conserva el historial. Solo elimina físicamente autobuses sin referencias históricas. |

Reglas de validación:

| Campo | Reglas |
|-------|--------|
| `license_plate` | Obligatorio, exactamente 6 caracteres, único, formato `^[A-Z]{3}[0-9]{3}$` (ej. `ABC123`). |
| `model` | Obligatorio, uno de: `Foton AUV`, `Yutong`, `Mercedes-Benz`, `Dina`, `Toyota`, `Honda`. |
| `capacity` | Obligatorio, entero entre 30 y 50. |
| `driver_id` | Opcional, debe existir en `users`. |
| `status` | Obligatorio: `active`, `inactive` o `maintenance`. |

## Admin\RouteController

Controlador de recurso (`Route::resource('routes', ...)`).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index(Request)` | `GET /admin/routes` | Lista paginada con búsqueda por nombre, código o descripción; incluye conteo de autobuses y paradas. |
| `create()` | `GET /admin/routes/create` | Formulario de metadatos; no consulta el catálogo de paradas y enlaza al Estudio para asignarlas. |
| `store(Request)` | `POST /admin/routes` | Crea la ruta; si se envía `stops[]`, lo sincroniza mediante `RouteStopSyncService`. |
| `edit(Route $route)` | `GET /admin/routes/{route}/edit` | Formulario de metadatos con enlace a la ruta seleccionada en el Estudio; no consulta el catálogo. |
| `update(Request, Route $route)` | `PUT/PATCH /admin/routes/{route}` | Actualiza metadatos; sincroniza `stops[]` solo cuando se envía, y conserva las asignaciones si se omite. |
| `destroy(Route $route)` | `DELETE /admin/routes/{route}` | Desactiva si tiene viajes, autobuses, likes o favoritos; elimina físicamente solo si carece de esas referencias. |

Reglas de validación:

| Campo | Reglas |
|-------|--------|
| `name` | Obligatorio, máx. 30 caracteres, único al crear. |
| `code` | Obligatorio, único. |
| `description` | Opcional. |
| `color` | Opcional (hexadecimal). |
| `estimated_duration` | Opcional, entero entre 40 y 90 minutos. |
| `is_active` | Booleano. |

## Admin\UserController

Controlador de recurso; solo implementa `create`, `store`, `edit`, `update` y `destroy`. Los listados se resuelven en `AdminController@drivers` y `AdminController@passengers`.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `create()` | `GET /admin/users/create` | Formulario de alta. |
| `store(Request)` | `POST /admin/users` | Crea el usuario con contraseña cifrada y `is_active = true`. Redirige a la lista de conductores o de pasajeros según el rol. |
| `edit(User $user)` | `GET /admin/users/{user}/edit` | Formulario de edición. |
| `update(Request, User $user)` | `PUT/PATCH /admin/users/{user}` | Actualiza; la contraseña solo cambia si se envía. |
| `destroy(User $user)` | `DELETE /admin/users/{user}` | Conductores: bloquea si tienen viaje activo; si no, los desactiva, libera y desactiva su autobús, conservando viajes y calificaciones. Pasajeros: elimina la cuenta. **No permite eliminar administradores.** |

Reglas de validación:

| Campo | Reglas |
|-------|--------|
| `first_name`, `last_name`, `maternal_last_name` | Obligatorios, máx. 35 caracteres, solo letras (incluye acentos, ñ y ü) y espacios. |
| `email` | Obligatorio, formato de correo, único. |
| `password` | Mín. 8 caracteres con al menos una letra, un número y un carácter especial. Obligatoria al crear, opcional al editar. |
| `role` | `admin`, `driver` o `passenger`. |
| `work_shift` | Obligatorio si el rol es `driver`: `morning` o `afternoon`. |
| `phone` | Obligatorio, exactamente 10 dígitos numéricos. |
| `is_active` | Booleano (solo al editar). |

## Admin\ReportController

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index()` | `GET /admin/reports` | Vista única **Reportes y Estadísticas** con: estadísticas generales (viajes totales y del mes, flota, usuarios, calificación promedio); serie de viajes por día de los últimos 30 días; top 10 autobuses por uso; top 10 conductores con calificación; top 5 rutas más utilizadas; y emergencias por tipo. Incluye accesos directos a la exportación PDF y CSV. La ruta histórica `/admin/statistics` redirige aquí. |
| `exportPdf()` | `GET /admin/reports/export-pdf` | Genera con DomPDF la vista `admin.reports-pdf` y la descarga como `reporte-ubicaditos-AAAA-MM-DD.pdf`. |
| `exportExcel()` | `GET /admin/reports/export-excel` | Construye los datos con `App\Exports\ReportsExport` y los transmite como `reporte-ubicaditos-AAAA-MM-DD.csv` (UTF-8 con BOM, compatible con Excel). |

### ReportsExport

`App\Exports\ReportsExport::collection()` devuelve una `Collection` de filas con cuatro secciones separadas por filas vacías:

1. **Viajes recientes** (últimos 30 días): conductor, autobús, ruta, inicio, fin, duración, pasajeros y estado.
2. **Conductores:** nombre, correo, viajes realizados y calificación promedio.
3. **Autobuses:** placa, modelo, capacidad, conductor, ruta, viajes y estado.
4. **Rutas:** código, nombre, descripción, paradas, viajes, duración estimada y estado.

## Admin\StopController

`GET /admin/stops/data` entrega búsqueda y paginación con tope de 50. El CRUD y el cambio de estado se operan desde el Estudio. `destroy()` elimina físicamente solo paradas sin rutas, favoritos ni likes; en caso contrario las desactiva y conserva las asociaciones.

## Driver\DriverController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `index()` | `GET /driver` | Vista `driver.dashboard` | Autobús asignado, viaje en curso, últimos 10 viajes y calificación promedio. |
| `map()` | `GET /driver/map` | Vista `driver.map` | Mapa con la ruta, paradas ordenadas y viaje en curso. Entrega `$buses` (asignados al conductor o disponibles) y `$routes` vinculadas a esas unidades para los selectores de RF-10; durante un viaje usa la unidad y ruta del `Trip`. Sin asignación permanente propone la primera unidad disponible y solo redirige con error si no hay ninguna opción. |
| `statistics()` | `GET /driver/statistics` | Vista `driver.statistics` | Estadísticas del propio conductor (ver abajo). |
| `startTrip(StartTripRequest)` | `POST /driver/start-trip` | Redirección | Inicia un viaje tras validar las reglas de negocio (ver abajo). Acepta `bus_id` y `route_id` opcionales (RF-10). |
| `endTrip()` | `POST /driver/end-trip` | Redirección | Marca el viaje en curso como `completed` con `completed_at = now()`, registra la ocupación final en `total_passengers` (último conteo del autobús del viaje, 0 si no hay autobús) y calcula `distance_km` con Haversine sobre `path_taken` (RF-12). |
| `updateLocation()` | `POST /driver/update-location` | JSON | Valida `latitude`/`longitude` y `accuracy` opcional (0–65535 m), actualiza posición y precisión mediante `Bus::updateLocation()` —que despacha `BusLocationUpdated` (ver [Tiempo real](#tiempo-real-broadcasting))— y agrega la lectura al `path_taken` del viaje activo (máximo 2000 puntos). Durante un viaje usa la unidad del `Trip`, no la asignación permanente; no acepta `bus_id` para impedir actualizar otra unidad. Devuelve `{"success": true}` o error 400/422 si no hay autobús o los datos son inválidos. |
| `updatePassengers()` | `POST /driver/update-passengers` | Redirección | Recibe `passengers` (entero ≥ 0) y lo limita a la capacidad del autobús. |
| `reportEmergency()` | `POST /driver/emergency` | Redirección | Valida `type` (`accident`, `breakdown`, `medical`, `security`, `other`) y `description` (≤ 500); crea un `EmergencyReport` en estado `pending` con las coordenadas actuales del autobús y cambia el autobús a estado `emergency`. |

### Estadísticas del conductor

`statistics()` calcula a partir de los viajes, calificaciones e incidencias del conductor:

| Dato | Descripción |
|------|-------------|
| `total_trips`, `completed_trips`, `active_trips` | Total, completados y en curso. |
| `total_passengers` | Suma de `total_passengers` de los viajes completados. |
| `avg_occupancy` | Ocupación promedio (0–100 %) de los viajes completados respecto a la capacidad del autobús. |
| `avg_rating`, `ratings_count` | Calificación promedio (1–5) y número de calificaciones recibidas. |
| `total_distance_km`, `total_minutes`, `avg_minutes` | Distancia acumulada (`distance_km`), minutos al volante (diferencia `started_at`→`completed_at`) y promedio por viaje. |
| `emergencies_reported` | Incidencias reportadas por el conductor. |
| `by_day` | Viajes iniciados por día en los últimos 7 días (para la gráfica de barras CSS). |
| `by_route` | Top 5 de rutas por número de viajes completados. |
| `recentRatings` | Últimas 5 calificaciones recibidas, con el pasajero. |

### Reglas para iniciar un viaje

`startTrip()` aplica las siguientes validaciones en orden; si alguna falla, regresa con un mensaje de error descriptivo. Los campos `bus_id`/`route_id` se validan primero en `StartTripRequest` (`nullable`, entero y existente); los errores de negocio se devuelven además en la clave `trip` o en el campo correspondiente para conservar el mensaje heredado:

1. El conductor debe estar **activo** (`is_active`).
2. Debe seleccionar (o tener por defecto) un autobús **activo asignado a él o disponible** (`driver_id = null`). Por defecto se usa `drivingBus`; el comportamiento histórico no cambia.
3. La ruta seleccionada —o la primera del autobús por defecto— debe estar **vinculada a ese autobús** en `bus_route`.
4. No debe existir otro **viaje en curso** del conductor ni un viaje activo con el autobús seleccionado.
5. **Horario:** la hora actual debe estar dentro de su turno.

   | Turno | Horario |
   |-------|---------|
   | `morning` (matutino) | 6:00 – 14:00 |
   | `afternoon` (vespertino) | 14:00 – 22:00 |
   | `night` (nocturno) | 22:00 – 6:00 (soportado en código, aunque no existe en el enum de la base de datos) |

6. **Ubicación:** el autobús debe tener coordenadas GPS registradas.
7. La ruta debe tener **paradas**.
8. **Proximidad:** `GeoDistanceService::distanceKm()` calcula la parada más cercana; debe estar a **500 metros o menos**.

Si todo es válido, se crea un `Trip` con estado `in_progress`, `bus_id` y `route_id` persistentes, y se informa la parada inicial. El estado del autobús no se modifica: solo el administrador puede cambiarlo.

### Registro de distancia del viaje (RF-12)

No se añadieron columnas: `trips.distance_km` (decimal(8,2)) y `trips.path_taken` (JSON) ya existían. Cada `POST /driver/update-location` durante un viaje agrega `{latitude, longitude, recorded_at}` al JSON, conservando como máximo 2000 lecturas recientes. Al finalizar, `GeoDistanceService::pathDistanceKm()` suma los tramos consecutivos y guarda el total redondeado a 2 decimales; sin lecturas se guarda `0.00` y `path_taken = null`. El mismo servicio Haversine se usa en la validación de proximidad, en las estimaciones de `RouteStopSyncService` y en el cálculo final, para que dos reglas no produzcan distancias distintas.

## Passenger\PassengerController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `map()` | `GET /passenger` | Vista `passenger.dashboard` | Página principal del pasajero: mapa con rutas activas, paradas activas (con `likes_count`) y autobuses activos; incluye `likedStopIds` para pintar el estado personal de los me gusta. |
| `index()` | `GET /passenger/menu` | Vista `passenger.menu` | Menú de opciones, paradas favoritas, rutas favoritas y conteo de reportes enviados/pendientes. Incluye una tarjeta con acceso directo a `incidents.create`. |
| `routes()` | `GET /passenger/routes` | Vista `passenger.routes` | Rutas activas con todas sus paradas, autobuses, `likes_count`, `favoriteRouteIds` y `likedRouteIds`. La tarjeta permite desplegar el recorrido completo cuando hay más de tres paradas. |
| `buses(Request)` | `GET /passenger/buses` | Vista `passenger.buses` | Listado filtrado en servidor: `q` (placa, modelo o nombre del conductor), `route_id`, `status` (por defecto `active`; `all` muestra todos) y `sort` (`plate` A-Z por defecto, u `occupancy` por ocupación descendente). Pasa además las rutas activas y `$busDetailsData`, un arreglo con datos verificables por autobús (placa, modelo, capacidad, pasajeros, asientos libres, estado, ruta, conductor, última actualización y enlace al mapa) que alimenta el modal de detalles. |
| `addFavorite(Stop $stop)` | `POST /passenger/favorites/{stop}` | JSON o redirección | Agrega la parada a favoritos con `nickname` opcional. Evita duplicados. |
| `removeFavorite(Stop $stop)` | `DELETE /passenger/favorites/{stop}` | JSON o redirección | Quita la parada de favoritos. |
| `rateDriver(RateDriverRequest, $driverId)` | `POST /passenger/rate/{driver}` | Redirección | Registra una calificación (`rating` 1–5, `comment` ≤ 500) solo si el destinatario tiene rol `driver` y existe un viaje `completed` con él. Guarda `trip_id`; si el formulario no lo envía, se vincula automáticamente al último viaje completado del conductor (RNF-02). |

Los métodos de favoritos detectan con `request()->expectsJson()` si la llamada viene de JavaScript (mapa) o de un formulario (menú) y responden en el formato adecuado.

## Passenger\FavoriteRouteController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `add(Route $route)` | `POST /passenger/favorite-routes/{route}` | JSON o redirección | Guarda la ruta en favoritos con `position` consecutivo; si ya existe responde 400 y si se supera el máximo de cinco responde 422 en JSON. |
| `update(Request, Route $route)` | `PATCH /passenger/favorite-routes/{route}` | JSON o redirección | Valida `position` (1…n) y `label` opcional (≤40), reordena el resto, persiste la etiqueta y responde 404 si la ruta no es favorita del usuario. |
| `remove(Route $route)` | `DELETE /passenger/favorite-routes/{route}` | JSON o redirección | Quita la ruta y compacta la numeración restante. |

## Passenger\NotificationSubscriptionController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `index(Request)` | `GET /passenger/notifications` | `{"subscriptions": [...]}` | Lista sólo los avisos del pasajero autenticado con placa y nombre de parada. |
| `subscribe(Request)` | `POST /passenger/notifications/subscribe` | `{"subscribed": true, "id": …, "bus_id": …, "stop_id": …}` | Valida que el autobús y la parada existan y reutiliza la suscripción si ya estaba (idempotente). |
| `unsubscribe(Request, int $subscription)` | `DELETE /passenger/notifications/{subscription}` | `{"subscribed": false}` | Borra el aviso propio (403 si es de otro usuario). Repetir la llamada es inofensivo. |

No hay envío externo: el registro es una preferencia in-app. Ninguna respuesta incluye `user_id`, correo ni nombre.

## LikeController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `toggleRoute(Route $route)` | `POST /likes/routes/{route}` | JSON `{liked, count}` o redirección | Alterna el me gusta del usuario autenticado. |
| `toggleStop(Stop $stop)` | `POST /likes/stops/{stop}` | JSON `{liked, count}` o redirección | Alterna el me gusta sobre la parada. |

El contador se calcula con `$likeable->likes()->count()` después de crear o borrar el registro, y el estado personal se expone en las vistas con `likedRouteIds` / `likedStopIds`.

## LocationShareController

| Método | Ruta | Middleware | Descripción |
|--------|------|------------|-------------|
| `start(Request)` | `POST /passenger/share-location` | `auth`, `passenger` | Valida coordenadas y `bus_id` opcional, crea un enlace nuevo y revoca el anterior. Responde `{active, id, token, bus_id, url, credentials, expires_at}` en JSON. |
| `update(Request, LocationShare)` | `PATCH /passenger/share-location/{share}` | `auth`, `passenger` | Solo el dueño puede actualizar la posición (403 en caso contrario). |
| `stop(Request, LocationShare)` | `DELETE /passenger/share-location/{share}` | `auth`, `passenger` | Solo el dueño puede revocar el enlace. |
| `current(Request)` | `GET /passenger/share-location/current` | `auth`, `passenger` | Devuelve `{active, share}` para rehidratar la UI tras recargar. |
| `show(string $token)` | `GET /share/{token}` | público | Página `share.show`; 404 si el enlace caducó o fue revocado. |
| `data(string $token)` | `GET /api/share/{token}` | público | JSON con la última posición; nunca incluye nombre, correo ni ID del pasajero. |

Validaciones: `latitude` entre −90 y 90, `longitude` entre −180 y 180, `accuracy` entero entre 0 y 65535 y `bus_id` opcional que debe existir en `buses`. El token es `Str::random(48)` y la caducidad por defecto es de 30 minutos (`LocationShare::TTL_MINUTES`). El conteo agregado del mapa se calcula con `scopeActive()` + `scopeForBus()`; no expone quién comparte.

## IncidentController

Reportes de incidencias enviados por el propio usuario autenticado. Es independiente de la gestión administrativa de `/admin/incidents`.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index(Request)` | `GET /incidents` | Listado de los reportes del usuario, ordenados del más reciente al más antiguo. |
| `create()` | `GET /incidents/create` | Formulario con las categorías de `Incident::CATEGORIES` y el límite de caracteres de la descripción. |
| `store(Request)` | `POST /incidents` | Valida `category` (catálogo cerrado) y `description` (obligatoria, máximo 1000); crea el reporte con `status = pending` y el dueño tomado de la sesión. No acepta `status`, `user_id` ni `resolved_at` desde el formulario. |

## Admin\IncidentManagementController

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index(Request)` | `GET /admin/incidents` | Listado global paginado con filtros `status`, `category` y búsqueda `q` (descripción o datos del autor); incluye contadores por estado. |
| `update(Request, Incident)` | `PATCH /admin/incidents/{incident}` | Cambia el estado a `pending`, `in_review` o `resolved`; fija `resolved_at` al resolver y lo limpia al reabrir. |

## ProfileController

Proviene de Breeze.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `edit()` | `GET /profile` | Formulario de perfil, métricas reales (rutas/paradas favoritas, calificaciones emitidas, me gusta y reportes), listas de favoritos, cambio de contraseña y eliminación de cuenta. |
| `update(ProfileUpdateRequest)` | `PATCH /profile` | Actualiza nombre, apellido paterno, apellido materno, teléfono y correo; si cambia el correo, anula su verificación. |
| `destroy()` | `DELETE /profile` | Tras confirmar la contraseña, elimina al pasajero o desactiva al conductor (bloqueado durante un viaje activo), conserva su historial/libera su autobús y cierra e invalida la sesión. |

## Controladores de autenticación

Ubicados en `app/Http/Controllers/Auth`, provienen de Breeze:

| Controlador | Función |
|-------------|---------|
| `AuthenticatedSessionController` | Muestra el login, autentica usuarios activos (vía `LoginRequest`, con límite de 5 intentos) y cierra sesión. El logout revoca los enlaces de ubicación activos, invalida la sesión y regenera el token CSRF. Tras el login redirige a `/dashboard`. |
| `RegisteredUserController` | Registro de pasajeros con validación personalizada y método `checkEmail()` para comprobar disponibilidad del correo vía AJAX. |
| `PasswordResetLinkController` | Envía el enlace de recuperación de contraseña. |
| `NewPasswordController` | Restablece la contraseña con el token recibido. |
| `PasswordController` | Cambia la contraseña desde el perfil. |
| `ConfirmablePasswordController` | Solicita confirmar la contraseña para acciones sensibles. |
| `EmailVerificationPromptController` | Muestra el aviso de verificación de correo. |
| `EmailVerificationNotificationController` | Reenvía el correo de verificación. |
| `VerifyEmailController` | Marca el correo como verificado desde el enlace firmado. |

## Form Requests

| Clase | Uso |
|-------|-----|
| `App\Http\Requests\Auth\LoginRequest` | Valida correo y contraseña, intenta la autenticación y aplica limitación de intentos por correo + IP. |
| `App\Http\Requests\ProfileUpdateRequest` | Valida `first_name`, `last_name`, `maternal_last_name`, `phone` (opcional, 10 dígitos) y `email` único, con mensajes en español. |

## Tiempo real (Broadcasting)

El modelo `App\Models\Bus::updateLocation($latitude, $longitude, ?int $accuracy = null)` guarda coordenadas, precisión y `last_location_update`, y despacha el evento `App\Events\BusLocationUpdated`. El evento implementa `ShouldBroadcastNow` (se publica de inmediato, sin worker de colas) y se transmite en el **canal privado `buses`** con el alias `bus.location.updated`:

- La autorización del canal está en `routes/channels.php`: cualquier usuario autenticado con rol `admin`, `driver` o `passenger` puede suscribirse, igual que con los endpoints JSON `/api/*` (matriz declarada en `roles:admin,driver,passenger`).
- El payload incluye `id`, `license_plate`, `latitude`, `longitude`, `status`, `current_passengers`, `available_seats`, `route_id`, `last_update`, `accuracy`, `fresh`, `fresh_until` y `sharing_passengers_count`.
- `fresh` y `fresh_until` se calculan en servidor con `Bus::LOCATION_FRESH_MINUTES = 5`; el cliente no duplica el umbral, sólo compara `fresh_until` como salvaguarda.
- El backend usa **Laravel Reverb** (`BROADCAST_CONNECTION=reverb`); la suscripción del navegador pasa por las rutas automáticas `GET|POST /broadcasting/auth` (ver [Rutas y API](08-rutas.md#tiempo-real-y-broadcasting)).
- En el cliente, `resources/js/realtime.js` (Echo + Reverb) recibe el evento y, si el WebSocket no conecta, cae al polling de `GET /api/buses/active`; ambas rutas emiten el mismo payload normalizado (con `stale` derivado) a los dashboards del pasajero y del administrador.
