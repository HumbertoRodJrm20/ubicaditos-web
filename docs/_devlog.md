# _devlog — Bitácora técnica

Registro técnico del desarrollo de Ubicaditos Web. Formato interno de desarrollo, complementario a la documentación numerada `01–13`.

Este documento cubre la **iteración 1** (a continuación) y la **iteración 3** (al final). La iteración 2 se documentó en `tests/load/README.md`.

## Resumen

| Tarea | Descripción | Estado |
|-------|-------------|:------:|
| **1. Bases de datos por ambiente** | PostgreSQL para producción (`main`) y staging; SQLite para desarrollo (`develop`). | ✅ Hecho |
| **2. Autenticación Breeze + roles** | Verificación y corrección de la autenticación, sesión y roles. Trabajo en rama separada. | ✅ Hecho (rama `feature/auth-breeze-roles`, fusionada) |
| **3. Documentación Swagger** | Especificación OpenAPI de la iteración 1 con modelos, seguridad y schemas. | ✅ Hecho |
| **4. Protección de endpoints + informe** | Verificación de sesión Breeze en todos los endpoints; suite completa en verde con informe. | ✅ Hecho |
| **Nota: gestor JS** | Todo el tooling se migró a **bun** (reemplaza a npm). | ✅ Hecho |

## Flujo de trabajo

Enfoque **TDD**: los tests se escribieron antes de cada implementación (fase roja) y se verificaron en verde antes de cerrar cada tarea. La suite completa se ejecutó al final para el informe.

1. Rama `feature/auth-breeze-roles` (tarea 2): tests de auth/roles → implementación → verde → merge a `develop`.
2. Rama `develop` (tareas 1, 3, 4): tests de configuración y protección → implementación → verde → documentación.

## Tarea 1 — PostgreSQL (producción/staging) vs SQLite (desarrollo)

### Análisis previo

- `config/database.php` ya registraba el conector `pgsql` (driver, host, puerto 5432, charset utf8).
- La extensión PHP `pdo_pgsql` está disponible en el entorno.
- `.env.example` apuntaba a SQLite y no documentaba el motor de los ambientes reales.
- No había archivos de entorno para producción ni staging.

### Implementación

| Archivo | Cambio |
|---------|--------|
| `.env.production.example` | **Nuevo.** `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=pgsql`, puerto 5432, base `ubicaditos`, SMTP y colas. |
| `.env.staging.example` | **Nuevo.** `APP_ENV=staging`, `APP_DEBUG=true`, `DB_CONNECTION=pgsql`, base `ubicaditos_staging`. |
| `.env.example` | Se mantiene `DB_CONNECTION=sqlite` (desarrollo); se agrega el bloque de PostgreSQL como referencia comentada. Se ajustaron `APP_NAME=Ubicaditos`, `APP_LOCALE=es` y `APP_FAKER_LOCALE=es_MX`. |
| `.gitignore` | Se agrega `.env.staging` (`.env.production` ya estaba ignorado). |
| `config/database.php` | Sin cambios (el conector ya existía). |

### Tests (escritos primero)

`tests/Feature/DatabaseConfigurationTest.php` (5 pruebas):

- `test_development_and_testing_use_sqlite_driver` — `config('database.default') === 'sqlite'`.
- `test_pgsql_connection_is_registered_and_ready_for_production` — conector `pgsql` presente con puerto 5432.
- `test_default_env_example_uses_sqlite_for_development` — `.env.example` usa `sqlite` y no `pgsql`.
- `test_production_env_example_configures_postgresql` — `.env.production.example` declara `APP_ENV=production`, `APP_DEBUG=false` y `pgsql:5432`.
- `test_staging_env_example_configures_postgresql` — `.env.staging.example` declara `APP_ENV=staging` y `pgsql:5432`.

## Tarea 2 — Autenticación Breeze y roles (rama aparte)

La tarea se realizó en la rama `feature/auth-breeze-roles` (creada desde `develop`) para no exponer `develop` a regresiones, y se fusionó al terminar la suite en verde.

### Diagnóstico

La autenticación, sesión y middleware de roles ya estaban implementados (Breeze). El problema era la **desincronización de la suite** con el esquema real de `users`: la iteración 0 cambió `users.name` por `first_name` + `last_name` + `maternal_last_name` y agregó `phone`, `role`, `is_active`, pero la `UserFactory` y las pruebas de Breeze seguían usando `name`. La suite partía de **4 pasadas / 21 fallidas**.

### Correcciones

| Archivo | Cambio |
|---------|--------|
| `database/factories/UserFactory.php` | `definition()` ahora genera `first_name`, `last_name`, `maternal_last_name`, `phone` (10 dígitos), `role='passenger'`, `is_active=true`. |
| `tests/Feature/Auth/RegistrationTest.php` | Registro con el formulario real (apellidos, teléfono, contraseña fuerte), verificación de rol `passenger` por defecto, redirección a `passenger.menu` y prueba de datos inválidos. |
| `tests/Feature/ExampleTest.php` | `/` responde 302 → `route('login')`. |
| `tests/Feature/ProfileTest.php` | Actualización con los tres campos de nombre; se valida el accessor `name` compuesto. |
| `app/Http/Requests/ProfileUpdateRequest.php` | Reglas `first_name`, `last_name`, `maternal_last_name` (regex de letras/espacios, máx. 35) en lugar de `name`. |
| `resources/views/profile/partials/update-profile-information-form.blade.php` | Los tres campos de nombre + teléfono en grilla de 3 columnas. |
| `tests/Feature/RoleAccessTest.php` | **Nuevo (6 pruebas).** Invitados → login; acceso por rol (admin/driver/passenger); cruce de áreas → 403; redirección de `/dashboard` por rol; factory válida por defecto. |

### Resultado en la rama

```text
Tests: 32 passed (102 assertions)
```

Merge a `develop` con merge commit (`merge: feature/auth-breeze-roles`).

## Tarea 3 — Documentación Swagger de la iteración 1

| Archivo | Contenido |
|---------|-----------|
| `docs/swagger.yaml` | **Nuevo.** OpenAPI 3.0.3: info de la iteración, servidores (local/staging/prod), esquema de seguridad `SessionAuth` (cookie `laravel_session`), 26+ paths (auth Breeze, API JSON, conductor, pasajero, admin, perfil) y componentes: 10 schemas de modelos + 8 de peticiones + `ErrorResponse`. |
| `docs/12-swagger-api.md` | **Nuevo.** Documento de la iteración 1: cómo visualizar `swagger.yaml`, tabla de seguridad, matriz de acceso por rol, inventario de modelos, resumen de schemas y endpoints, e informe de pruebas. |

Notas de la especificación:

- La seguridad es por cookie de sesión (Breeze); se omitieron esquemas Bearer.
- `RegisterRequest` refleja la contraseña fuerte del formulario de registro (regex, mín. 8, símbolo `@$!%*?&`).
- `Bus.current_passengers` se documenta como columna existente en BD pendiente de alta total en `$fillable` (ver `10-pruebas-y-estado.md`).

## Tarea 4 — Protección de endpoints e informe final

### Verificación de protección (tests escritos primero)

`tests/Feature/EndpointProtectionTest.php` (3 pruebas):

| Prueba | Qué verifica |
|--------|--------------|
| `test_unauthenticated_requests_are_redirected_to_login` | 16 endpoints web + 3 JSON exigen sesión (302 → `route('login')`). |
| `test_any_authenticated_user_can_consume_the_json_api` | Con fixtures (ruta + autobús), cada rol consume `/api/*` con 200 JSON. |
| `test_driver_write_endpoints_require_driver_session` | Pasajero recibe 403 en `/driver/update-location` y `/driver/emergency`; el conductor con autobús asignado actualiza ubicación (200) y crea la emergencia `pending` con redirección. |

### Test final (informe)

```bash
php artisan test
```

```text
Tests:    40 passed (172 assertions)
Duration: ~2.5s
```

Desglose completo en `12-swagger-api.md` → "Informe de ejecución de pruebas".

## Nota — Migración definitiva a bun

- `composer.json`: script `setup` usa `bun install` + `bun run build`; script `dev` usa `bunx concurrently` + `bun run dev`.
- `docs/README.md` y `docs/01-instalacion.md` actualizados: `bun install`, `bun run dev`, `bun run build`.
- El repositorio ya incluía `bun.lock` (se eliminó `package-lock.json`).

## Archivos modificados en la iteración

**Nuevos**

- `.env.production.example`, `.env.staging.example`
- `docs/swagger.yaml`, `docs/12-swagger-api.md`, `docs/_devlog.md`
- `tests/Feature/DatabaseConfigurationTest.php`, `tests/Feature/EndpointProtectionTest.php`, `tests/Feature/RoleAccessTest.php`

**Modificados**

- `.env.example`, `.gitignore`, `composer.json`
- `database/factories/UserFactory.php`
- `app/Http/Requests/ProfileUpdateRequest.php`
- `resources/views/profile/partials/update-profile-information-form.blade.php`
- `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/ExampleTest.php`, `tests/Feature/ProfileTest.php`
- `docs/README.md`, `docs/01-instalacion.md`, `docs/10-pruebas-y-estado.md`

## Pendientes para la siguiente iteración

Véase `10-pruebas-y-estado.md` (observaciones 1, 3–8): alta de `current_passengers` en `Bus::$fillable`, `EXTRACT(HOUR)` para PostgreSQL en el reporte de ocupación, implementar `Admin\StopController`, rutas/controlador de resolución de emergencias, `available_seats` en `DashboardController@getActiveBuses`, límites correctos de `Route::resource` y el enum `work_shift` (turno `night`).
## Iteración 3 — Tiempo real, búsqueda, estadísticas e incidencias

Rama de trabajo: `develop`. Enfoque **TDD** (tests en rojo primero, implementación, verde; suite completa al final): **86 pruebas / 289 aserciones en verde**. Gestor de paquetes JS: **bun** en todo el proyecto.

### Resumen

| # | Tarea | Descripción | Estado |
|---|-------|-------------|:------:|
| 1 | Tiempo real con WebSockets | Laravel Reverb + canal privado `buses` + evento `BusLocationUpdated` (`ShouldBroadcastNow`); módulo frontend `resources/js/realtime.js` (Echo + fallback de sondeo a `/api/buses/active` cada 5 s). | ✅ Hecho |
| 2 | Búsqueda y filtros de autobuses | Filtros en servidor (`q`, `route_id`, `status`, `sort`) + refinamiento client-side; ocupación y asientos en tarjetas y modal. | ✅ Hecho |
| 3 | Detalle de autobús (admin) | `Admin\BusController@show` + vista con KPIs, historial de viajes y emergencias. | ✅ Hecho |
| 4 | Ciclo de viaje y GPS automático | Inicio/fin de viaje con validaciones; interruptor de compartir ubicación persistido en `localStorage`; auto-actualización con deriva cada 5 s en modo simulado. | ✅ Hecho |
| 5 | Estadísticas de conductor | `DriverController@statistics` + vista con KPIs y gráfica de barras; accesos desde el nav y el panel. | ✅ Hecho |
| 6 | Incidencias (ciclo completo) | Formulario del conductor (5 tipos), listado con filtros del admin, resolución solo de pendientes y **vuelta de la unidad a servicio**. | ✅ Hecho |
| 7 | Restyle responsive | Todas las vistas restantes al estilo cristal y mobile-first (objetivo 375×667). | ✅ Hecho |
| 8 | Pruebas (TDD) | Suites nuevas: tiempo real, filtros del pasajero, ciclo de viaje, estadísticas, incidencias y detalle de autobús. | ✅ Hecho |
| 9 | Scripts E2E (Selenium) | `tests/selenium/` (Python + pytest + selenium, Chrome headless, 5 specs). No se ejecutan en esta rama. | ✅ Hecho (ejecución pendiente en la rama de pruebas) |
| 10 | Pruebas de carga (k6) | `tests/load/ubicaditos-iteration3-load.js` (solo lectura, 3 roles). No se ejecutó: k6 no está disponible en este entorno. | ✅ Hecho (ejecución pendiente) |
| 11 | Documentación y swagger | `06`, `07`, `08`, `10`, nuevo `13-iteracion-3.md`, `_devlog`, `swagger.yaml` y `12-swagger-api.md`. | ✅ Hecho |

### Decisiones técnicas

| Decisión | Motivo |
|----------|--------|
| Laravel Reverb (v1.12) como servidor de broadcasting | Solución oficial, liviana y sin dependencias externas (Pusher/AWS). |
| Canal privado `buses` autorizado para cualquier usuario autenticado (`routes/channels.php`) | Los mapas de pasajero, conductor y admin muestran la misma flota; la sesión Breeze ya define quién entra. |
| `BusLocationUpdated implements ShouldBroadcastNow` | El evento se emite en el mismo request (sin worker de colas); suficiente para el volumen actual. |
| Módulo `resources/js/realtime.js` con fallback de sondeo (5 s) | La app sigue funcionando si Reverb no está corriendo (entorno de desarrollo); ambas fuentes emiten el mismo payload normalizado `{id, license_plate, latitude, longitude, status, current_passengers, available_seats, route_id, last_update, source, received_at}`. |
| `$busDetailsData` calculado en `PassengerController@buses` (no en la vista) | Permite aserción con `assertViewHas` en pruebas; `$busFilterData` (búsqueda client-side) se calcula en el bloque `@php` de la vista. |
| Filtros de lista en servidor (query string) + búsqueda instantánea en cliente | Los filtros son compartibles/enlazables (chips como enlaces) y la búsqueda no recarga; el cliente solo refina `q`. |
| La resolución de una incidencia devuelve la unidad a `active` | Regla de dominio incorporada en la iteración 3 (con pruebas): resolver el incidente restaura el servicio de la unidad. |
| Interruptor de compartir ubicación persistido en `localStorage` | El conductor controla el envío de su GPS; el estado sobrevive a recargas (por defecto activo). |
| Pruebas E2E con `pytest` + `selenium` y localizadores `By.*` | `pytest` es el runner estándar de Python; los localizadores se construyen con la API `By.*` de Selenium, ya que las cadenas tipo `css=…` no son válidas en la API de Python. |

### Ciclo de incidencias (nuevo comportamiento)

1. El conductor reporta desde su panel (`POST /driver/emergency`, tipos `accident|breakdown|medical|security|other`); la unidad pasa a estado `emergency` y se guarda la posición del bus.
2. El administrador ve el reporte en `GET /admin/emergencies` (filtros por estado y tipo, contadores) con la placa de la unidad.
3. Solo los reportes `pending` pueden resolverse (`POST /admin/emergencies/{id}/resolve`); se registra `resolved_at` y **si la unidad estaba en `emergency` vuelve a `active`**.

### Restyle responsive

Se maquetaron al nuevo estilo (cristal + mobile-first): `admin/buses/index|create|edit`, `admin/routes/index|create|edit`, `admin/passengers`, `admin/users/create|edit`, además de las vistas nuevas (`admin/emergencies`, `driver/statistics`, `admin/buses/show`, `passenger/buses` reescrita) y las modificadas (`driver/dashboard` con modal de incidencia, `driver/map` con interruptor de GPS, `passenger/dashboard` con tiempo real). Los partials `profile/partials/*` son código heredado de Breeze que ya no se incluye en ninguna vista (sin uso; no se tocó).

### Pruebas nuevas (fase roja → verde)

| Archivo | Enfoque |
|---------|---------|
| `tests/Feature/RealTime/BusLocationUpdatedTest.php` | Payload del evento, nombre de canal `private-buses`, autorización de canal con el driver real (reverb) y `broadcastNow`. |
| `tests/Feature/Passenger/BusSearchFilterTest.php` | `q`, `status`, `route_id`, `sort` (plate/occupancy) y `$busDetailsData` (asientos disponibles). |
| `tests/Feature/Bus/OccupancyTest.php` | Accessors `available_seats` y `occupancy_percentage` (promedio excluye viajes sin capacidad). |
| `tests/Feature/Driver/TripLifecycleTest.php` | Iniciar/finalizar viaje, validaciones de turno y cercanía a parada, pasajeros registrados al finalizar. |
| `tests/Feature/Driver/DriverStatisticsTest.php` | KPIs con tipos estrictos y desglose por ruta. |
| `tests/Feature/Admin/EmergencyManagementTest.php` | Listado, filtros, resolución solo de pendientes, 404 y vuelta de la unidad a servicio. |
| `tests/Feature/Admin/BusShowTest.php` | Detalle de autobús: KPIs, viajes y emergencias. |

### Archivos creados

- `app/Events/BusLocationUpdated.php`, `routes/channels.php`, `resources/js/realtime.js`
- `resources/views/admin/emergencies.blade.php`, `resources/views/driver/statistics.blade.php`, `resources/views/admin/buses/show.blade.php`
- `tests/Feature/RealTime/BusLocationUpdatedTest.php` y las suites indicadas arriba
- `tests/selenium/` (`config.py`, `conftest.py`, 5 specs, `requirements.txt`, `README.md`, `.gitignore`)
- `tests/load/ubicaditos-iteration3-load.js`
- `docs/13-iteracion-3.md`
- `.env.production.example`, `.env.staging.example` (bloques Reverb; `.env.example` ampliado)

### Archivos modificados (principales)

- Controladores: `PassengerController@buses`, `DriverController@statistics|endTrip|reportEmergency`, `AdminController@emergencies|resolveEmergency`, `Admin\BusController@show`
- Modelo `Bus` (`current_passengers` en `$fillable`, accessors `available_seats`/`occupancy_percentage`, `updateLocation()`), `bootstrap/app.php` (canales), `routes/web.php`
- Vistas: `passenger/buses`, `passenger/dashboard`, `driver/dashboard`, `driver/map`, `driver/statistics`, `admin/dashboard`, `admin/buses/*`, `admin/routes/*`, `admin/passengers`, `admin/users/*`, `components/passenger-nav`
- Assets y entorno: `package.json` (`laravel-echo@2.6.1`, `pusher-js@8.6.0`), `resources/js/app.js`, `.env.example`(+staging/production), `composer.json` (sin cambios de dependencias PHP fuera de Reverb)
- Documentación: `docs/06`, `docs/07`, `docs/08`, `docs/10`, `docs/12`, `docs/13`, `docs/README.md`, `docs/swagger.yaml`, `tests/load/README.md`

### Pendientes para la siguiente iteración

- Ejecutar la suite Selenium y el script k6 en la rama de pruebas (con servidor local + `migrate:fresh --seed`) y archivar resultados en `tests/load/results/`.
- Incidencias preexistentes sin resolver (ver `10-pruebas-y-estado.md`): `Admin\StopController`, `strftime`→`EXTRACT` para PostgreSQL en `ReportController`, enum `work_shift` con turno `night`, límites de `Route::resource`, seeders obsoletos y validaciones de autorización (calificaciones, `driver_id`, coordenadas acotadas).
- Despliegue: proceso de Reverb en segundo plano (supervisor/systemd) y WSS con certificado TLS en producción/staging.
- Limpieza: eliminar o reutilizar los partials de perfil heredados; extraer el JavaScript de los mapas a módulos (parcialmente avanzado con `realtime.js`).
