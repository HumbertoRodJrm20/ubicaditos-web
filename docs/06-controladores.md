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
| `ProfileController` | `auth` | Edición y eliminación del perfil propio. |
| `Admin\AdminController` | `auth`, `admin` | Dashboard, estadísticas, conductores, pasajeros y emergencias. |
| `Admin\BusController` | `auth`, `admin` | CRUD de autobuses y detalle (`show`). |
| `Admin\RouteController` | `auth`, `admin` | CRUD de rutas y sus paradas. |
| `Admin\UserController` | `auth`, `admin` | Alta, edición y baja de usuarios. |
| `Admin\ReportController` | `auth`, `admin` | Reportes, gráficas y exportación PDF/CSV. |
| `Admin\StopController` | `auth`, `admin` | CRUD de paradas (**sin implementar**). |
| `Driver\DriverController` | `auth`, `driver` | Panel, mapa, estadísticas, viajes, GPS, pasajeros y emergencias. |
| `Passenger\PassengerController` | `auth`, `passenger` | Mapa, rutas, autobuses, favoritos y calificaciones. |
| `Auth\*` | `guest` / `auth` | Autenticación de Laravel Breeze. |

## DashboardController

`App\Http\Controllers\DashboardController`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index()` | `GET /dashboard` | Redirige a `admin.dashboard`, `driver.dashboard` o `passenger.dashboard` según el rol; responde 403 si el rol es desconocido. |
| `getActiveBuses()` | `GET /api/buses/active` | JSON con los autobuses activos que tienen coordenadas: placa, posición, capacidad, pasajeros, asientos libres (`available_seats`, calculados), conductor, ruta, color de ruta y hace cuánto se actualizó. Además del consumo directo, es el **fallback de polling** (cada 5 s) que usa `resources/js/realtime.js` cuando el WebSocket no está disponible. |
| `getBusLocation(Bus $bus)` | `GET /api/buses/{bus}/location` | JSON con posición, estado, pasajeros y fecha de actualización de un autobús. |
| `getRouteBuses(Route $route)` | `GET /api/routes/{route}/buses` | JSON con los autobuses activos de una ruta junto con su conductor. |

El formato de las respuestas JSON se detalla en [Rutas y API](08-rutas.md#endpoints-json).

## Admin\AdminController

| Método | Ruta | Vista | Descripción |
|--------|------|-------|-------------|
| `index()` | `GET /admin` | `admin.dashboard` | Estadísticas generales (autobuses totales y activos, rutas, paradas, conductores, pasajeros, viajes de hoy), últimos 10 viajes, autobuses activos y datos para el mapa en vivo (rutas activas con paradas y autobuses, paradas activas). |
| `statistics()` | `GET /admin/statistics` | `admin.statistics` | Viajes por día (30 días), uso de cada autobús (30 días) y top 10 conductores por número de viajes. |
| `drivers(Request)` | `GET /admin/drivers` | `admin.drivers` | Lista paginada de conductores con búsqueda por nombre, apellidos o correo; incluye autobús asignado, número de viajes y calificación promedio; y la lista de autobuses sin conductor para asignar. |
| `assignBusToDriver(Request, User $driver)` | `POST /admin/drivers/{driver}/assign-bus` | — | Valida `bus_id`, libera el autobús que el conductor tuviera antes y asigna el nuevo. Garantiza la relación uno a uno conductor ↔ autobús. |
| `unassignBusFromDriver(User $driver)` | `DELETE /admin/drivers/{driver}/unassign-bus` | — | Quita el conductor de su autobús. |
| `passengers(Request)` | `GET /admin/passengers` | `admin.passengers` | Lista paginada de pasajeros con búsqueda; incluye conteo de calificaciones emitidas y paradas favoritas. |
| `emergencies(Request)` | `GET /admin/emergencies` | `admin.emergencies` | Listado de incidencias reportadas por los conductores (con `reporter` y `bus`), filtrable por `status` (`pending`, `in_progress`, `resolved`) y `type` (`accident`, `breakdown`, `medical`, `security`, `other`); incluye contadores por estado y las etiquetas traducidas de estados y tipos. |
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
| `destroy(Bus $bus)` | `DELETE /admin/buses/{bus}` | Elimina el autobús (en cascada se borran sus viajes y asignaciones de ruta). |

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
| `create()` | `GET /admin/routes/create` | Formulario con todas las paradas disponibles. |
| `store(Request)` | `POST /admin/routes` | Crea la ruta y sincroniza las paradas: el índice de cada elemento de `stops[]` se guarda como `order` en el pivote. |
| `edit(Route $route)` | `GET /admin/routes/{route}/edit` | Formulario de edición. |
| `update(Request, Route $route)` | `PUT/PATCH /admin/routes/{route}` | Actualiza y vuelve a sincronizar paradas con su orden. |
| `destroy(Route $route)` | `DELETE /admin/routes/{route}` | Elimina la ruta. |

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
| `destroy(User $user)` | `DELETE /admin/users/{user}` | Elimina al usuario. **No permite eliminar administradores.** |

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
| `index()` | `GET /admin/reports` | Panel de reportes con: estadísticas generales (viajes totales y del mes, flota, usuarios, rutas, paradas, calificación promedio, emergencias totales y pendientes); serie de viajes por día de los últimos 30 días (rellenando con 0 los días sin viajes); top 10 autobuses por uso; top 10 conductores con calificación; top 5 rutas; ocupación promedio por hora (últimos 7 días); y emergencias por tipo. |
| `exportPdf()` | `GET /admin/reports/export-pdf` | Genera con DomPDF la vista `admin.reports-pdf` y la descarga como `reporte-ubicaditos-AAAA-MM-DD.pdf`. |
| `exportExcel()` | `GET /admin/reports/export-excel` | Construye los datos con `App\Exports\ReportsExport` y los transmite como `reporte-ubicaditos-AAAA-MM-DD.csv` (UTF-8 con BOM, compatible con Excel). |

### ReportsExport

`App\Exports\ReportsExport::collection()` devuelve una `Collection` de filas con cuatro secciones separadas por filas vacías:

1. **Viajes recientes** (últimos 30 días): conductor, autobús, ruta, inicio, fin, duración, pasajeros y estado.
2. **Conductores:** nombre, correo, viajes realizados y calificación promedio.
3. **Autobuses:** placa, modelo, capacidad, conductor, ruta, viajes y estado.
4. **Rutas:** código, nombre, descripción, paradas, viajes, duración estimada y estado.

## Admin\StopController

Está registrado como recurso (`/admin/stops`), pero todos sus métodos están vacíos: es el esqueleto generado por `make:controller --resource`. Las paradas actualmente solo se gestionan mediante seeders.

## Driver\DriverController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `index()` | `GET /driver` | Vista `driver.dashboard` | Autobús asignado, viaje en curso, últimos 10 viajes y calificación promedio. |
| `map()` | `GET /driver/map` | Vista `driver.map` | Mapa con la ruta, paradas ordenadas y viaje en curso. Redirige con error si no hay autobús asignado. |
| `statistics()` | `GET /driver/statistics` | Vista `driver.statistics` | Estadísticas del propio conductor (ver abajo). |
| `startTrip()` | `POST /driver/start-trip` | Redirección | Inicia un viaje tras validar las reglas de negocio (ver abajo). |
| `endTrip()` | `POST /driver/end-trip` | Redirección | Marca el viaje en curso como `completed` con `completed_at = now()` y registra la ocupación final del viaje en `total_passengers` (último conteo de pasajeros reportado en el autobús, 0 si no hay autobús). |
| `updateLocation()` | `POST /driver/update-location` | JSON | Recibe `latitude` y `longitude` y actualiza la posición del autobús mediante `Bus::updateLocation()`, que despacha el evento `BusLocationUpdated` (ver [Tiempo real](#tiempo-real-broadcasting)). Devuelve `{"success": true}` o error 400 si no hay autobús. |
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

`startTrip()` aplica las siguientes validaciones en orden; si alguna falla, regresa con un mensaje de error descriptivo:

1. El conductor debe estar **activo** (`is_active`).
2. Debe tener un **autobús asignado**.
3. El autobús debe tener una **ruta asignada**.
4. No debe existir otro **viaje en curso**.
5. **Horario:** la hora actual debe estar dentro de su turno.

   | Turno | Horario |
   |-------|---------|
   | `morning` (matutino) | 6:00 – 14:00 |
   | `afternoon` (vespertino) | 14:00 – 22:00 |
   | `night` (nocturno) | 22:00 – 6:00 (soportado en código, aunque no existe en el enum de la base de datos) |

6. **Ubicación:** el autobús debe tener coordenadas GPS registradas.
7. La ruta debe tener **paradas**.
8. **Proximidad:** mediante la fórmula de Haversine se calcula la parada más cercana; debe estar a **500 metros o menos**.

Si todo es válido, se crea un `Trip` con estado `in_progress` y se informa la parada inicial. El estado del autobús no se modifica: solo el administrador puede cambiarlo.

## Passenger\PassengerController

| Método | Ruta | Respuesta | Descripción |
|--------|------|-----------|-------------|
| `map()` | `GET /passenger` | Vista `passenger.dashboard` | Página principal del pasajero: mapa con rutas activas, paradas activas y autobuses activos. |
| `index()` | `GET /passenger/menu` | Vista `passenger.menu` | Menú de opciones y paradas favoritas del usuario. |
| `routes()` | `GET /passenger/routes` | Vista `passenger.routes` | Rutas activas con sus paradas y autobuses. |
| `buses(Request)` | `GET /passenger/buses` | Vista `passenger.buses` | Listado filtrado en servidor: `q` (placa, modelo o nombre del conductor), `route_id`, `status` (por defecto `active`; `all` muestra todos) y `sort` (`plate` A-Z por defecto, u `occupancy` por ocupación descendente). Pasa además las rutas activas y `$busDetailsData`, un arreglo con datos verificables por autobús (placa, modelo, capacidad, pasajeros, asientos libres, estado, ruta, conductor, última actualización y enlace al mapa) que alimenta el modal de detalles. |
| `addFavorite(Stop $stop)` | `POST /passenger/favorites/{stop}` | JSON o redirección | Agrega la parada a favoritos con `nickname` opcional. Evita duplicados. |
| `removeFavorite(Stop $stop)` | `DELETE /passenger/favorites/{stop}` | JSON o redirección | Quita la parada de favoritos. |
| `rateDriver(Request, $driverId)` | `POST /passenger/rate/{driver}` | Redirección | Registra una calificación (`rating` 1–5, `comment` ≤ 500). |

Los métodos de favoritos detectan con `request()->expectsJson()` si la llamada viene de JavaScript (mapa) o de un formulario (menú) y responden en el formato adecuado.

## ProfileController

Proviene de Breeze.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `edit()` | `GET /profile` | Formulario de perfil, cambio de contraseña y eliminación de cuenta. |
| `update(ProfileUpdateRequest)` | `PATCH /profile` | Actualiza los datos; si cambia el correo, anula su verificación. |
| `destroy()` | `DELETE /profile` | Elimina la cuenta tras confirmar la contraseña, cierra sesión e invalida la sesión. |

## Controladores de autenticación

Ubicados en `app/Http/Controllers/Auth`, provienen de Breeze:

| Controlador | Función |
|-------------|---------|
| `AuthenticatedSessionController` | Muestra el login, autentica (vía `LoginRequest`, con límite de 5 intentos) y cierra sesión. Tras el login redirige a `/dashboard`. |
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
| `App\Http\Requests\ProfileUpdateRequest` | Valida la actualización del perfil (`name` y `email` único). |

## Tiempo real (Broadcasting)

El modelo `App\Models\Bus::updateLocation($latitude, $longitude)` guarda las coordenadas y `last_location_update`, y despacha el evento `App\Events\BusLocationUpdated`. El evento implementa `ShouldBroadcastNow` (se publica de inmediato, sin worker de colas) y se transmite en el **canal privado `buses`** con el alias `bus.location.updated`:

- La autorización del canal está en `routes/channels.php`: cualquier usuario autenticado (cualquier rol) puede suscribirse, igual que con los endpoints JSON `/api/*`.
- El payload incluye `id`, `license_plate`, `latitude`, `longitude`, `status`, `current_passengers`, `available_seats`, `route_id` y `last_update`.
- El backend usa **Laravel Reverb** (`BROADCAST_CONNECTION=reverb`); la suscripción del navegador pasa por las rutas automáticas `GET|POST /broadcasting/auth` (ver [Rutas y API](08-rutas.md#tiempo-real-y-broadcasting)).
- En el cliente, `resources/js/realtime.js` (Echo + Reverb) recibe el evento y, si el WebSocket no conecta, cae al polling de `GET /api/buses/active`; ambas rutas emiten el mismo payload normalizado a los dashboards del pasajero y del administrador.
