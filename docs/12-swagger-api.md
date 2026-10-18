# API SWAGGER — Iteración 1

Documento de la **primera iteración** del proyecto. Contiene la especificación OpenAPI (Swagger) de la API, el inventario de modelos, la seguridad por sesión de Laravel Breeze con roles y el informe de ejecución de las pruebas. Las novedades de la **iteración 3** se documentan en la sección [Ampliaciones de la iteración 3](#ampliaciones-de-la-iteración-3) al final de este documento; el resto del contenido describe la iteración 1 tal como se cerró.

## Alcance de la iteración 1

| Tarea | Resultado |
|-------|-----------|
| Bases de datos por ambiente | **Desarrollo (rama `develop`): SQLite.** **Producción (rama `main`) y staging: PostgreSQL** (ver `01-instalacion.md`). Se incluyen `.env.production.example` y `.env.staging.example`; el conector `pgsql` quedó registrado y documentado. |
| Autenticación y sesión | **Breeze Session Auth verificado y corregido.** La suite Breeze se alineó con el esquema real de `users`; registro, login, logout y roles funcionan. Trabajo realizado en la rama `feature/auth-breeze-roles` y fusionado a `develop`. |
| Documentación OpenAPI | Este documento y `swagger.yaml`. |
| Protección de endpoints | Verificado que todos los endpoints privados (web y JSON) exigen sesión y que los middleware de rol (`admin`, `driver`, `passenger`) rechazan con 403 los accesos cruzados. |
| Pruebas | Suite completa **40 pruebas / 172 aserciones en verde**. |

## Especificación OpenAPI

La especificación completa está en [`swagger.yaml`](swagger.yaml) (OpenAPI 3.0.3, en español). Cubre:

- Los flujos de autenticación de Breeze (registro, login, logout, recuperación de contraseña).
- Los endpoints JSON de tiempo real (`/api/*`).
- Las acciones de conductor, pasajero y administración.
- Los esquemas (schemas) de todos los modelos.
- El esquema de seguridad por cookie de sesión.

### Cómo visualizarla

| Herramienta | Uso |
|-------------|-----|
| [Swagger Editor](https://editor.swagger.io) | Pegar el contenido de `swagger.yaml`. |
| [Swagger UI](https://petstore.swagger.io) | Cargar `swagger.yaml` y generar la interfaz interactiva. |
| [Redocly CLI](https://redocly.com) | `bunx @redocly/cli build-docs docs/swagger.yaml -o docs/swagger.html` |

## Seguridad

La aplicación usa **autenticación por sesión (Laravel Breeze)** para todo, incluida la API JSON. No hay tokens Bearer.

| Aspecto | Detalle |
|---------|---------|
| Mecanismo | Cookie de sesión `laravel_session`; las peticiones que mutan estado requieren la cabecera `X-XSRF-TOKEN` (cookie `XSRF-TOKEN`) por protección CSRF. |
| Esquema OpenAPI | `SessionAuth` (`type: apiKey`, `in: cookie`, `name: laravel_session`), declarado como esquema de seguridad global. |
| Guest | Los usuarios no autenticados son redirigidos (302) a `/login` en las rutas web y JSON. |
| Roles | Middleware alias `admin`, `driver`, `passenger` registrados en `bootstrap/app.php`. Los endpoints de cada panel exigen su rol (403 en caso contrario). |
| Punto de entrada | `/dashboard` redirige al panel del rol autenticado (admin/driver/passenger). |
| Panel por rol | Admin: `/admin/*` — Driver: `/driver/*` — Passenger: `/passenger/*` |
| Verificación de correo | `/dashboard` usa el middleware `verified`; el modelo `User` no implementa `MustVerifyEmail`, por lo que no bloquea el acceso en la iteración actual. |
| Throttling | Login y envío de verificación limitados (5–6 intentos/min) para mitigar fuerza bruta. |

### Matriz de acceso por rol

| Recurso | guest | passenger | driver | admin |
|---------|:-----:|:---------:|:------:|:-----:|
| `/login`, `/register` | ✅ | ❌ (redirige) | ❌ | ❌ |
| `/dashboard` | ➜ login | ➜ `/passenger` | ➜ `/driver` | ➜ `/admin` |
| `/admin/*` | ➜ login | 🔒 403 | 🔒 403 | ✅ |
| `/driver/*` | ➜ login | 🔒 403 | ✅ | 🔒 403 |
| `/passenger/*` | ➜ login | ✅ | 🔒 403 | 🔒 403 |
| `/api/*` | ➜ login | ✅ | ✅ | ✅ |
| `/profile` | ➜ login | ✅ | ✅ | ✅ |

## Modelos

Inventario de modelos de la iteración 1 (detalle completo de columnas en `05-base-de-datos.md` y `11-diagramas.md`).

| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `User` | `users` | Usuario con rol (`passenger`, `driver`, `admin`), teléfono, ubicación opcional y turno para conductores. Accessor `name` = nombre completo. |
| `Bus` | `buses` | Autobús de la flota con ubicación en vivo, estado, capacidad y conductor asignado. |
| `Route` | `routes` | Ruta con polilínea (`[lat, lng]`), color y duración estimada. |
| `Stop` | `stops` | Parada con coordenadas. |
| `Trip` | `trips` | Viaje iniciado/finalizado por un conductor en un autobús. |
| `Rating` | `ratings` | Calificación 1–5 de pasajero a conductor. |
| `EmergencyReport` | `emergency_reports` | Emergencias reportadas por conductores (estado `pending`/`resolved`). |
| `Message` | `messages` | Mensajes entre usuarios. |
| `FavoriteStop` | `favorite_stops` | Paradas favoritas de pasajeros (con `nickname` en el pivot). |

## Schemas

`swagger.yaml` define los esquemas de los modelos y de las peticiones/respuestas:

**Modelos:** `User`, `Bus`, `Route`, `Stop`, `Trip`, `Rating`, `EmergencyReport`, `Message`, `FavoriteStop` y `BusLocation`.

**Peticiones:** `LoginRequest`, `RegisterRequest`, `ResetPasswordRequest`, `ProfileUpdateRequest`, `UpdateLocationRequest`, `StartTripRequest`, `EmergencyRequest`, `RateRequest`.

**Respuestas:** `ErrorResponse` (mensaje de error y errores por campo).

**Iteración 3:** nuevo schema `BusLocationUpdate` (evento broadcast); `Bus` incorpora `available_seats` y `occupancy_percentage` (calculados, `readOnly`); `EmergencyReport` incorpora `in_progress` a su enum de estado.

## Endpoints

Resumen de las rutas documentadas (todas las 75 rutas de la aplicación están descritas en `08-rutas.md`).

### Autenticación (sesión)

| Método | URI | Descripción | Seguridad |
|--------|-----|-------------|-----------|
| GET/POST | `/login` | Formulario e inicio de sesión | guest |
| POST | `/logout` | Cierre de sesión | sesión |
| GET/POST | `/register` | Registro; el usuario nace con rol `passenger` | guest |
| POST | `/check-email` | Disponibilidad de correo en tiempo real | guest |
| POST | `/forgot-password` | Envía enlace de recuperación | guest |
| POST | `/reset-password` | Restablece contraseña | guest |
| GET/PATCH/DELETE | `/profile` | Ver, actualizar y eliminar perfil | sesión |

### API JSON (tiempo real)

| Método | URI | Descripción | Respuesta |
|--------|-----|-------------|-----------|
| GET | `/api/buses/active` | Autobuses activos para mapas | `Bus[]` |
| GET | `/api/buses/{bus}/location` | Ubicación actual de un autobús | `BusLocation` |
| GET | `/api/routes/{route}/buses` | Autobuses de una ruta (con driver) | `Bus[]` |

### Conductor

| Método | URI | Descripción |
|--------|-----|-------------|
| POST | `/driver/update-location` | Actualiza la ubicación del autobús asignado |
| POST | `/driver/update-passengers` | Actualiza el conteo de pasajeros a bordo |
| POST | `/driver/start-trip` | Inicia viaje (valida turno y distancia ≤ 500 m con Haversine) |
| POST | `/driver/end-trip` | Finaliza el viaje activo |
| POST | `/driver/emergency` | Reporta emergencia (queda `pending`) |

### Pasajero

| Método | URI | Descripción |
|--------|-----|-------------|
| POST/DELETE | `/passenger/favorites/{stop}` | Agregar/eliminar parada favorita |
| POST | `/passenger/rate/{driver}` | Calificar conductor (1–5) |

## Ampliaciones de la iteración 3

La **iteración 3** amplía `swagger.yaml` con nuevas rutas web (vista de KPIs del conductor, gestión de incidencias y detalle de unidad), el endpoint de autorización de broadcasting, campos calculados en el esquema `Bus`, un nuevo esquema `BusLocationUpdate` para el evento en tiempo real y la corrección del enum de estado de `EmergencyReport`. El contenido de iteración 1 descrito arriba se mantiene intacto.

### Nuevos endpoints

| Método | URI | Tag | Descripción | Respuestas clave |
|--------|-----|-----|-------------|------------------|
| GET | `/driver/statistics` | Conductor | KPIs del conductor: viajes completados (sobre el total) y activos, pasajeros transportados, ocupación promedio, calificaciones, distancia (km), tiempo al volante e incidencias reportadas; además viajes de los últimos 7 días, sus 5 rutas más frecuentes y sus 5 calificaciones recientes | 200 (HTML), 403 |
| GET | `/admin/emergencies` | Administración | Listado de incidencias con filtros `status` (`pending`/`in_progress`/`resolved`) y `type` (`accident`/`breakdown`/`medical`/`security`/`other`); tarjetas con placa, reportero, descripción, coordenadas y marcas de tiempo, con contadores por estado | 200 (HTML), 403 |
| POST | `/admin/emergencies/{emergency}/resolve` | Administración | Marca una incidencia `pending` como `resolved` y registra `resolved_at`. Si no estaba `pending` redirige con mensaje de error en sesión; al resolverse devuelve el autobús asociado de `emergency` a `active` | 302 (flash), 404 |
| GET | `/admin/buses/{bus}` | Administración | Detalle de la unidad: KPIs de ocupación (capacidad, ocupación actual, asientos libres, porcentaje), datos generales, conductor y rutas asignadas, últimos 10 viajes y 5 incidencias recientes | 200 (HTML), 403, 404 |
| GET/POST | `/broadcasting/auth` | API en tiempo real | Endpoint informativo de Laravel Broadcasting (Laravel Reverb) para autorizar la suscripción al canal privado `buses`. Requiere sesión autenticada de cualquier rol; los canales se definen en `routes/channels.php` | 200, 403 |

### Cambios en schemas existentes

| Schema | Cambio (Iteración 3) |
|--------|----------------------|
| `Bus` | Nuevos campos calculados `available_seats` (entero, `readOnly`, mínimo 0: capacidad − ocupación actual, nunca negativo) y `occupancy_percentage` (`number`, `readOnly`, 0–100) — accessors de `app/Models/Bus.php`. `current_passengers` se documenta ahora como campo mas-assignable, actualizado por el conductor con `/driver/update-passengers` (recortado a la capacidad). |
| `EmergencyReport` | El enum de `status` queda en `[pending, in_progress, resolved]` (nuevo estado `in_progress` que ya usaban el controlador y la vista). |

### Evento de tiempo real y fallback de polling

| Atributo | Valor |
|----------|-------|
| Evento | `App\Events\BusLocationUpdated` |
| Canal | privado `buses` — cualquier usuario autenticado puede suscribirse (`routes/channels.php`) |
| Nombre broadcast | `bus.location.updated` |
| Implementación | `ShouldBroadcastNow`: se publica de inmediato, sin depender de un worker de colas |
| Payload | `id`, `license_plate`, `latitude`, `longitude`, `status`, `current_passengers`, `available_seats`, `route_id`, `last_update`, `source` |
| Esquema OpenAPI | `BusLocationUpdate` |

`resources/js/realtime.js` consume el canal con Laravel Echo/Reverb y normaliza tanto el mensaje del WebSocket (`source: websocket`) como los elementos de `/api/buses/active` (`source: polling`), añadiendo además `received_at`. Si el WebSocket no conecta en 4 s (o se desconecta), se activa un fallback de **polling cada 5 s** contra `GET /api/buses/active`, cuya respuesta ahora incluye los campos calculados `available_seats` y `occupancy_percentage`.

### Qué se agregó y dónde

| Elemento | `docs/swagger.yaml` | `docs/12-swagger-api.md` |
|----------|---------------------|--------------------------|
| GET `/driver/statistics` | Nueva path, tag Conductor | Tabla "Nuevos endpoints" |
| GET `/admin/emergencies` | Nueva path con parámetros `status`/`type` | Tabla "Nuevos endpoints" |
| POST `/admin/emergencies/{emergency}/resolve` | Nueva path (302/404) | Tabla "Nuevos endpoints" |
| GET `/admin/buses/{bus}` | Nueva path (200/403/404) | Tabla "Nuevos endpoints" |
| GET/POST `/broadcasting/auth` | Nueva path (informativa, sesión requerida) | Tabla "Nuevos endpoints" |
| `Bus`: `available_seats` y `occupancy_percentage` | Campos añadidos al schema `Bus` | Tabla "Cambios en schemas existentes" |
| `BusLocationUpdate` (evento broadcast) | Nuevo schema + descripción en `GET /api/buses/active` (fallback 5 s) | Sección "Evento de tiempo real y fallback de polling" |
| `EmergencyReport.status` con `in_progress` | Enum actualizado | Tabla "Cambios en schemas existentes" |
| `info.description` | Párrafo de ampliaciones de iteración 3 | Párrafo introductorio de esta sección |

Validación de la especificación tras la ampliación: `python3 -c "import yaml; yaml.safe_load(open('docs/swagger.yaml'))"` → **YAML OK** (OpenAPI 3.0.3, 31 paths, 20 schemas, todas las referencias `$ref` resueltas).

## Informe de ejecución de pruebas

Comando ejecutado al cierre de la iteración:

```bash
php artisan test
```

Resultado: **40 pruebas pasadas, 172 aserciones, 0 fallos**. Duración aproximada: 2.5 s (BCRYPT_ROUNDS=4 en `phpunit.xml`).

| Suit de pruebas | Pruebas | Cobertura |
|-----------------|:-------:|-----------|
| `Auth\AuthenticationTest` | 4 | Pantalla de login, autenticación, contraseña inválida, logout |
| `Auth\EmailVerificationTest` | 3 | Pantalla de verificación, verificación por hash, hash inválido |
| `Auth\PasswordConfirmationTest` | 3 | Confirmación de contraseña |
| `Auth\PasswordResetTest` | 4 | Pantallas y restablecimiento de contraseña |
| `Auth\PasswordUpdateTest` | 2 | Cambio de contraseña |
| `Auth\RegistrationTest` | 3 | Registro como pasajero, redirección y datos inválidos |
| `DatabaseConfigurationTest` | 5 | SQLite en dev, PostgreSQL registrado, archivos de entorno prod/staging |
| `EndpointProtectionTest` | 3 | Sesión exigida en web/JSON, API consumible por cualquier rol, acciones de conductor restringidas |
| `ProfileTest` | 5 | Perfil, actualización, verificación de correo, eliminación |
| `RoleAccessTest` | 6 | Redirección de invitados, acceso por rol, redirección del dashboard, factory válida |
| `ExampleTest` (Feature/Unit) | 2 | Redirección de `/` y ejemplo unitario |
| **Total** | **40** | **172 aserciones** |

Los detalles de los hallazgos y las correcciones están en `10-pruebas-y-estado.md` y en el [_devlog](_devlog.md).