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

## Iteración 4 — Likes, favoritos, ubicación compartida y reportes ciudadanos

Rama de trabajo: `test`. Enfoque **TDD** (pruebas en rojo primero, implementación, verde; suite completa al final): **134 pruebas / 503 aserciones en verde**. Gestor de paquetes JS: **bun**.

### Resumen

| # | Tarea | Descripción | Estado |
|---|-------|-------------|:------:|
| 1 | Likes polimórficos | Me gusta públicos sobre rutas y paradas, con contador y estado por usuario. | ✅ |
| 2 | Rutas favoritas | Tabla `favorite_routes`, endpoints de alta/baja y listas en menú, rutas y perfil. | ✅ |
| 3 | Ubicación compartida | Enlace temporal `/share/{token}` con token de 48 caracteres, TTL de 30 min y revocación. | ✅ |
| 4 | Reportes ciudadanos | Listado global, filtros, búsqueda y transiciones `pending`/`in_review`/`resolved`. | ✅ |
| 5 | Perfil enriquecido | Métricas reales de favoritos, calificaciones, likes y reportes. | ✅ |
| 6 | Restilizado glass | Navegación, componentes Breeze, layouts y vistas heredadas. | ✅ |
| 7 | Documentación y Swagger | Secciones 04–12, 15 y 16, `swagger.yaml` y este devlog. | ✅ |
| 8 | Pruebas E2E y carga | Selenium y k6 actualizados (no ejecutados en este entorno). | ✅ |

### Decisiones

| Decisión | Motivo |
|----------|--------|
| Likes polimórficos (`likeable_type`/`likeable_id`) | Una sola tabla y contadores homogéneos para rutas y paradas. |
| Favoritos separados de likes | Los likes son públicos; los favoritos son privados y personales. |
| Un enlace activo por pasajero | Al iniciar uno nuevo se revoca el anterior; evita enlaces olvidados. |
| Token aleatorio de 48 caracteres | No expone el ID del pasajero y es inviable de adivinar. |
| TTL de 30 minutos | Suficiente para un viaje urbano y limita la exposición. |
| `resolved_at` gestionado en el controlador | Mantiene consistencia al resolver y reabrir reportes. |
| Reutilizar `<x-passenger-nav>` | Una navegación única para los tres roles y un solo lugar para el modo oscuro. |

### Cambios por capa

| Capa | Cambios |
|------|---------|
| Migraciones | `create_likes_table`, `create_favorite_routes_table`, `create_location_shares_table`, `add_resolved_at_to_incidents_table`. |
| Modelos | Nuevos `Like`, `FavoriteRoute` y `LocationShare`; relaciones y helpers en `User`, `Route`, `Stop` e `Incident`. |
| Controladores | `LikeController`, `Passenger\FavoriteRouteController`, `LocationShareController`, `Admin\IncidentManagementController`. |
| Rutas | `likes.*`, `passenger.favorite-routes.*`, `passenger.share-location.*`, `share.show`, `api.share.data`, `admin.incidents.*`. |
| Vistas | Mapa del pasajero (like por parada + panel de ubicación), rutas (like/favorito/filtro), menú, perfil, reportes de administración y página pública. |
| Estilo | Navegación sticky glass, componentes Breeze, `layouts/guest`, `layouts/app`, `welcome`, `dashboard` y parciales de perfil. |
| Pruebas | `Likes/LikeTest`, `Passenger/FavoriteRouteTest`, `Passenger/LocationShareTest`, `Admin/IncidentManagementTest`. |
| Documentación | `docs/04`, `05`, `06`, `07`, `08`, `10`, `12`, `15`, `16`, `README.md`, `swagger.yaml` y este devlog. |

### Pruebas nuevas (fase roja → verde)

| Archivo | Enfoque |
|---------|---------|
| `tests/Feature/Likes/LikeTest.php` | Alternancia de likes, contador público, unicidad por usuario y estado personal en rutas y paradas. |
| `tests/Feature/Passenger/FavoriteRouteTest.php` | Alta/baja, duplicado (400), acceso por rol y exposición en rutas, menú y perfil. |
| `tests/Feature/Passenger/LocationShareTest.php` | Token y caducidad, revocación del enlace anterior, actualización solo del dueño, endpoints públicos y 404 al caducar. |
| `tests/Feature/Admin/IncidentManagementTest.php` | Listado, filtros, búsqueda, transiciones de estado y gestión de `resolved_at`. |

### Archivos creados

- `app/Models/Like.php`, `app/Models/FavoriteRoute.php`, `app/Models/LocationShare.php`
- `app/Http/Controllers/LikeController.php`, `app/Http/Controllers/Passenger/FavoriteRouteController.php`, `app/Http/Controllers/LocationShareController.php`, `app/Http/Controllers/Admin/IncidentManagementController.php`
- `database/migrations/2026_11_08_000001_…` a `…000004_…`
- `resources/views/admin/incidents/index.blade.php`, `resources/views/share/show.blade.php`
- `tests/Feature/Likes/LikeTest.php`, `tests/Feature/Passenger/FavoriteRouteTest.php`, `tests/Feature/Passenger/LocationShareTest.php`, `tests/Feature/Admin/IncidentManagementTest.php`
- `tests/load/ubicaditos-iteration4-load.js`
- `docs/15-auditoria-mapas.md` (movido desde la raíz), `docs/16-iteracion-4.md`

### Archivos modificados (principales)

- Modelos `User`, `Route`, `Stop`, `Incident` (relaciones, `resolved_at` en `$fillable` y casts).
- Controladores `Passenger\PassengerController` y `ProfileController` (conteos, listas y métricas reales).
- Vistas `passenger/dashboard`, `passenger/routes`, `passenger/menu`, `profile/edit`, `layouts/navigation`, `layouts/app`, `layouts/guest`, `dashboard`, `welcome`, `components/*` y `profile/partials/*`.
- `routes/web.php`, `docs/swagger.yaml` y secciones de documentación 04–12.

### Verificación

| Comando | Resultado |
|---------|-----------|
| `php artisan test` | 134 pruebas, 503 aserciones, 0 fallos. |
| `bun run build` | Vite compila CSS y JS correctamente. |
| `php artisan migrate --force` | Nuevas migraciones aplicadas en la base local. |

### Pendientes para la siguiente iteración

- Ejecutar Selenium y k6 en un entorno con Chrome y k6, y archivar resultados.
- Cobertura dedicada del CRUD de administración.
- Revisar el límite de 30 minutos o permitir configurarlo por usuario.
- Extraer el JavaScript del mapa y del panel de ubicación a módulos si el archivo crece más.
- Regenerar trazados reales en la base local con `php artisan routes:generate-paths --force` (requiere internet).

## Iteración 5 y 6 — Reportes, rutas, perfil y sesión

Se auditaron las iteraciones 5 y 6 contra el código existente y se corrigieron los huecos que impedían cerrar el flujo de extremo a extremo. El trabajo siguió TDD: pruebas en rojo primero, implementación después y suite completa en verde al final.

### Resumen

| #   | Tarea                     | Descripción                                                                                          | Estado |
| --- | ------------------------- | ---------------------------------------------------------------------------------------------------- | :----: |
| 1   | Acceso a reportes         | Tarjeta en el menú del pasajero, enlace en la navegación y rutas `incidents.*`.                      |   ✅    |
| 2   | Captura y envío           | Selector de categoría, comentarios con contador, validación y estado inicial `pending`.              |   ✅    |
| 3   | Listado completo de rutas | Recorrido completo desplegable con todas las paradas de cada ruta activa.                            |   ✅    |
| 4   | Perfil alineado           | El formulario ahora envía apellido materno y teléfono, además de nombre, apellido paterno y correo.  |   ✅    |
| 5   | Sesión segura             | Logout con CSRF, invalidación de sesión, regeneración de token y revocación de enlaces de ubicación. |   ✅    |
| 6   | Pruebas y documentación   | Nuevas pruebas de flujo, sección 17, Swagger y actualización de esta bitácora.                       |   ✅    |
|     |                           |                                                                                                      |        |

### Decisiones

| Decisión | Motivo |
|----------|--------|
| El teléfono es opcional en la actualización del perfil | Hay usuarios heredados sin teléfono; si se captura, se exigen exactamente 10 dígitos. |
| El apellido materno sigue siendo obligatorio | Coincide con el registro y completa el nombre en los componentes de navegación y perfil. |
| Revocar enlaces de ubicación al cerrar sesión | Evita que un enlace público siga exponiendo la posición después del logout. |
| Desplegar las paradas dentro de la tarjeta | Satisface el listado completo sin introducir una página de detalle adicional. |
| Mantener el formulario de reportes accesible a cualquier rol | La ruta usa `auth`; conductores y administradores también pueden reportar si lo necesitan. |

### Cambios por capa

| Capa | Cambios |
|------|---------|
| Controladores | `AuthenticatedSessionController` revoca enlaces activos antes del logout; `PassengerController` calcula reportes enviados y pendientes. |
| Requests | `ProfileUpdateRequest` valida `phone` y añade mensajes en español. |
| Vistas | `passenger/menu` (tarjeta de reportes), `passenger/routes` (recorrido completo) y `profile/edit` (apellido materno, teléfono, visibilidad de contraseñas y nota de seguridad). |
| Pruebas | `Incidents/IncidentTest`, `Passenger/RouteListingTest`, `ProfileTest` y `Auth/AuthenticationTest`. |
| Documentación | `docs/17-iteracion-5-6.md`, `README.md`, `docs/README.md`, `06`, `07`, `08`, `10`, `12` y `swagger.yaml`. |

### Verificación

| Comando | Resultado |
|---------|-----------|
| `php artisan test` | 143 pruebas, 554 aserciones, 0 fallos. |
| `php artisan view:cache` | Todas las plantillas Blade compilan. |
| `bun run build` | Vite compila CSS y JS correctamente. |

### Pendientes

- Ejecutar Selenium y k6 en un entorno con Chrome y k6.
- Regenerar los trazados con `php artisan routes:generate-paths --force` cuando haya conectividad.
- Añadir una prueba E2E específica del formulario de perfil y del logout con revocación de ubicación.

**Objective**


- Maintain the Laravel app "Ubicaditos" (branch test, TDD); verify and complete iterations 5 and 6, which the user says are incomplete.
Requirements
- Iteración 5: Reportes de Incidencias — acceso al formulario de reportes; selección de tipo y comentarios; registro y envío de incidencias; validación funcional del flujo de reportes.
- Iteración 6: Rutas, Perfil y Sesión — listado completo y rutas favoritas; perfil personal y configuración; cierre de sesión seguro; integración frontend-backend.
- TDD workflow: tests first, full suite green at the end.
- Use bun for JS tooling/build (bun run build), not npm.
- Final docs in Spanish (sections, tables, Mermaid, no --- separators) + swagger updates.
- Selenium E2E and k6 scripts written but not executed here (no Chrome/k6).
- Teaching-oriented, step-by-step explanations.
- Glass mobile-first style (375×667) across all views.
Decisions
- Likes are polymorphic on routes and stops: public count, per-user toggle, separate from personal favorites.
- Passenger location sharing = temporary live link /share/{token}, public map polling every 5 s, 30-min TTL, revoke previous active share on start.
- Favorite routes stored in favorite_routes (distinct from favorite_stops).
- Admin incidents: global list with filters/search and status transitions pending/in_review/resolved; resolved_at set/cleared automatically.
- Realtime via Laravel Reverb private channel buses, BusLocationUpdated; 5 s polling fallback /api/buses/active.
- Incidents remain per-user self-service with status forced to pending server-side; only driver/passenger nav shows "Reportes".
- Seeder route paths remain null; regenerate via php artisan routes:generate-paths --force.
- layouts/navigation.blade.php reuses <x-passenger-nav /> inside a sticky glass wrapper.
- Admin nav gained "Incidencias" (admin.incidents.index); responsive breakpoint changed to lg.
- Selenium specs extended for likes, favorite routes, share panel, citizen reports and responsive checks.
- k6 iteration 4 script created as a read-only extension of iteration 3.
Work State
Completed
- Iteration 4 delivered: likes, favorite routes, passenger live-location sharing, admin incident management, profile enrichment, glass restyling.
- New test suites pass: LikeTest (8), FavoriteRouteTest (7), LocationShareTest (11), IncidentManagementTest (11).
- Full suite green: 134 tests / 503 assertions.
- bun run build succeeds; php artisan view:cache compiles all Blade templates.
- Local DB migrated with the four new migrations.
- Docs updated: docs/README.md, 04–12, _devlog.md, docs/15-auditoria-mapas.md, docs/16-iteracion-4.md.
- Swagger updated and validated: 41 paths / 26 schemas, YAML OK.
- Selenium and k6 artifacts updated (tests/selenium/*, tests/load/ubicaditos-iteration4-load.js, READMEs).
- package-lock.json removed.
- All iteration 4 work committed: bbbb85d.
Active
- Auditing iterations 5 and 6 against existing code to identify gaps before implementing.
- Reviewed so far: IncidentController, IncidentTest, incident views, passenger-nav, auth routes, ProfileController, ProfileTest, PassengerController, passenger/menu, profile/edit, ProfileUpdateRequest, AuthenticationTest, RoleAccessTest.
- No code changes made yet in this turn; working tree was clean at bbbb85d.
Blocked
- (none)
Next Move
1. Finish the iteration 5/6 audit (routes listing/favorites filter, profile/settings UI, logout flow, nav links, frontend-backend wiring).
2. Write failing feature tests for any missing behavior.
3. Implement missing iteration 5/6 functionality.
4. Re-run full suite, bun run build, and php artisan view:cache.
5. Update Spanish docs/swagger and commit.
Relevant Files
- app/Http/Controllers/IncidentController.php: incident list/create/store flow.
- app/Http/Controllers/ProfileController.php: profile edit/update/destroy.
- app/Http/Requests/ProfileUpdateRequest.php: profile validation rules.
- app/Http/Controllers/Passenger/PassengerController.php: routes listing, favorites, menu data.
- resources/views/passenger/menu.blade.php: passenger home with favorites.
- resources/views/incidents/index.blade.php / create.blade.php: report list/form UI.
- resources/views/profile/edit.blade.php: profile/settings UI.
- resources/views/components/passenger-nav.blade.php: role nav incl. "Reportes"/"Incidencias".
- routes/web.php / routes/auth.php: likes, favorites, share, incidents, profile, logout routes.
- tests/Feature/Incidents/IncidentTest.php: iteration 5 coverage.
- tests/Feature/ProfileTest.php: profile coverage.
- tests/Feature/Auth/AuthenticationTest.php: login/logout coverage.
- tests/Feature/RoleAccessTest.php: role-based access coverage.
- docs/swagger.yaml: OpenAPI spec to extend.
- docs/16-iteracion-4.md: format template for new iteration docs.
Important Context
- Branch test; latest commit bbbb85d; tree clean before this audit.
- Iterations 5/6 appear largely implemented already; user insists they are incomplete, so gaps must be verified rather than assumed.
- Selenium and k6 remain unexecuted (no Chrome/k6).
- Local DB has 18 routes with 0 path_coordinates; regenerate with php artisan routes:generate-paths --force (needs internet).
- Seed logins: admin@ubicaditos.com, chofer1@ubicaditos.com, pasajero1@ubicaditos.com; password password.
- phpunit.xml sets BROADCAST_CONNECTION=null; local .env uses Reverb.
- public/build is gitignored; rebuild with bun run build.
- k6 command: k6 run tests/load/ubicaditos-iteration4-load.js -e K6_VUS=15.


## Iteración 7 — Modo oscuro y administración

Se activó el modo oscuro en toda la aplicación y se reforzó el panel administrativo con contadores de flota y reportes, además de un listado de autobuses con resumen y filtros. El trabajo siguió TDD: pruebas de listado, filtros, contadores y tema primero; implementación después; suite completa en verde al final.

### Resumen

| #   | Tarea                          | Descripción                                                                                          | Estado |
| --- | ------------------------------ | ---------------------------------------------------------------------------------------------------- | :----: |
| 1   | Inicialización del tema        | Parcial `theme-init` en el `<head>` que aplica la preferencia antes del primer pintado.              |   ✅    |
| 2   | Conmutador compartido          | Componente `<x-theme-toggle>` con iconos luna/sol, persistencia y API `UbicaditosTheme`.             |   ✅    |
| 3   | Cobertura visual               | Variantes `dark:` en layouts, autenticación, pasajero, conductor, reportes y administración.         |   ✅    |
| 4   | Mapas oscuros                  | Reglas globales para contenedores Leaflet, teselas, controles y popups de paradas.                   |   ✅    |
| 5   | Contadores del dashboard       | Flota en mantenimiento/emergencia/inactiva e incidencias/emergencias pendientes con enlaces filtrados. |   ✅    |
| 6   | Listado de autobuses           | Resumen de flota, chips de estado, búsqueda, paginación validada y estados vacíos diferenciados.     |   ✅    |
| 7   | Pruebas y documentación        | Nuevas pruebas de autobuses, dashboard y tema; sección 18 y actualización de índices.                |   ✅    |

### Decisiones

| Decisión | Motivo |
|----------|--------|
| Un solo componente para el toggle | Evita duplicar la lógica entre navegación autenticada y pantallas de autenticación. |
| Clave `ubicaditos-theme` con respaldo en `darkMode` | Conserva la preferencia de usuarios que ya habían usado el conmutador anterior. |
| Respetar `prefers-color-scheme` sin preferencia guardada | El tema inicial coincide con el sistema operativo y se actualiza si este cambia. |
| Filtros del listado como enlaces y formularios GET | Permiten compartir la URL, conservar el estado al paginar y funcionar sin JavaScript. |
| La emergencia no se edita desde el CRUD | Ese estado lo controla el flujo de reportes del conductor y su resolución. |
| Contadores del dashboard con enlaces filtrados | Convierten los indicadores en accesos directos al listado correspondiente. |

### Cambios por capa

| Capa | Cambios |
|------|---------|
| Controladores | `AdminController@index` agrega contadores de flota y reportes; `BusController@index` valida `status`/`per_page`, filtra y calcula `$summary`. |
| Vistas | Nuevos `partials/theme-init` y `components/theme-toggle`; variantes oscuras en layouts y vistas; dashboard con tarjeta de estado; listado de autobuses con resumen y filtros. |
| CSS | Reglas de `color-scheme`, transición de tema, mapas Leaflet oscuros y popups adaptados. |
| Pruebas | `Admin/BusManagementTest` (12), `Admin/AdminDashboardTest` (4), `Admin/AdminReportsTest` (4) y `ThemeTest` (3). |
| Documentación | `docs/18-iteracion-7.md`, `README.md`, `docs/README.md`, `07`, `10` y esta bitácora. |

### Verificación

| Comando | Resultado |
|---------|-----------|
| `php artisan test` | 166 pruebas, 655 aserciones, 0 fallos. |
| `php artisan view:cache` | Todas las plantillas Blade compilan. |
| `bun run build` | Vite compila CSS y JS correctamente. |

### Pendientes

- Ejecutar Selenium y k6 en un entorno con Chrome y k6.
- Añadir cobertura dedicada al CRUD de rutas y paradas.
- Revisar el contraste de los mapas oscuros en dispositivos de gama baja.

### Corrección posterior — fondos oscuros y vista única

Al revisar el panel con el tema oscuro se detectó que los degradados locales de las vistas administrativas seguían usando la paleta clara. Se añadieron reglas globales `.dark .<page>` en `resources/css/app.css`, se retiró el `@media (prefers-color-scheme: dark)` de incidencias y se ajustó el mapa del dashboard para oscurecer fondo y teselas.

También se unificaron `admin/reports` y `admin/statistics` en la vista **Reportes y Estadísticas**: `/admin/statistics` redirige a `/admin/reports`, la navegación muestra un solo enlace y la página incorpora rutas más utilizadas, emergencias por tipo y accesos de exportación PDF/CSV. `ReportController@index` pasó a entregar `tripsByDay` como colección y se retiró `AdminController@statistics`.

| Capa | Cambios |
|------|---------|
| Controladores | `ReportController@index` entrega los datos de la vista única; se eliminó `AdminController@statistics`. |
| Rutas | `admin.statistics` redirige a `admin.reports`. |
| Vistas | `admin/reports` concentra reportes y estadísticas; se eliminó `admin/statistics`; navegación con un solo enlace. |
| CSS | Fondos oscuros para `.buses-page`, `.buses-form-page`, `.bus-show-page`, `.drivers-page`, `.emergencies-page`, `.incidents-admin`, `.passengers-page`, `.reports-page`, `.routes-page`, `.routes-form-page` y `.users-form-page`. |
| Pruebas | `Admin/AdminReportsTest` (4). |
| Verificación | 166 pruebas, 655 aserciones; `view:cache` y `bun run build` correctos. |

## 2026-11-25 — Catálogo acotado del Estudio (RNF-05, parcial)

El payload inicial del Estudio incluye como máximo 200 paradas principales distintas; las secuencias completas (incluidas las frecuentes) se solicitan solo para la ruta seleccionada. `GET /admin/stops/data` ofrece búsqueda, lookup validado por ID y paginación de hasta 50 elementos. En este seguimiento, `RouteController@create/edit` dejó de consultar/renderizar todo el catálogo: los formularios heredados se limitan a metadatos y enlazan al Estudio para asignar paradas.

El Estudio compara la secuencia local con la guardada, avisa antes de descartar cambios, protege la salida de página y muestra `estimated_time_from_previous`. La lectura de la secuencia usa `route_stop.id` como desempate estable. La generación OSRM permanece en la acción explícita y mantiene la geometría guardada cuando falla.

La revisión de paradas frecuentes se indica al guardar, pero no se persiste porque el campo requeriría una migración pendiente de aprobación. RNF-05 sigue parcial hasta medir/añadir guardas de consultas en otros listados principales. Sin migraciones en esta fase.

Pruebas de trazabilidad: `StopCatalogTest`, `RouteCrudCharacterizationTest::test_legacy_route_forms_do_not_load_or_render_the_stop_catalog`, `RouteCrudCharacterizationTest::test_selected_route_stops_endpoint_preserves_combined_order` y `RouteStudioManagementTest::test_studio_load_does_not_call_osrm_and_exposes_unsaved_change_status`. Verificación: suite enfocada (26 pruebas, 156 aserciones), suite completa (234 pruebas, 970 aserciones), `bun run build` y Pint en los archivos PHP tocados; no se afirma revisión manual de navegador.

## 2026-11-25 — Integridad referencial y bajas seguras (RNF-12, HU-038, HU-043, HU-047)

Con aprobación del propietario, se agregó un índice único a `route_stop(route_id, stop_id)`; la migración hace preflight y falla ante duplicados sin borrar datos. El SQLite de desarrollo no se migró; la migración se ejercitó en SQLite en memoria con PHPUnit. El DDL usa el índice único portable de Laravel, pero PostgreSQL no estuvo disponible para una ejecución directa.

Las rutas con viajes, asignaciones, likes o favoritos se desactivan; los autobuses con viaje activo o conductor asignado no se eliminan y los autobuses con historial se desactivan; los conductores con viaje activo no se desactivan. La baja administrativa y propia de un conductor desactiva usuario/autobús, libera la asignación y preserva viajes/calificaciones/reportes; el login rechaza usuarios inactivos. El borrado de pasajeros conserva el flujo Breeze existente.

Trazabilidad: `RouteCrudCharacterizationTest`, `StopManagementTest`, `BusManagementTest`, `DriverLifecycleTest`, `ProfileTest` y `AuthenticationTest`. Verificación final: `php artisan test` (250 pruebas, 1,038 aserciones), `php artisan view:cache`, Pint y `git diff --check`; PostgreSQL y navegador no se ejecutaron.

## 2026-11-25 — Ciclo de viaje y autorización por roles (RF-10, RF-12, RNF-02)

Se cerró el paso 3 del backlog con TDD: primero se escribieron cinco suites nuevas y se confirmó la fase roja (17 fallas / 11 pasadas / 69 aserciones) porque el selector se ignoraba, no se guardaban distancia/trayecto, `/api` no declaraba roles, la asignación aceptaba no conductores y las calificaciones no exigían viaje completado. Después se implementó la fase verde y se refactorizó con la suite en verde. Durante el refactor se detectó que el mapa todavía redirigía a un conductor sin asignación permanente aunque hubiera unidades disponibles; se añadió una prueba que falló con 302 y se ajustó `map()` para proponer la primera unidad disponible.

### Cambios por capa

| Capa | Cambio |
|------|--------|
| Requests | `StartTripRequest` valida `bus_id`/`route_id` opcionales; `RateDriverRequest` autoriza solo pasajeros, exige conductor de destino y viaje `completed`, y vincula `trip_id` automáticamente para el formulario heredado. |
| Middleware | `EnsureUserHasRole` con alias `roles` en `bootstrap/app.php`; los tres endpoints JSON en tiempo real declaran `roles:admin,driver,passenger`. `/api/share/{token}` permanece público. |
| Controladores | `DriverController::map()` entrega autobuses propios/disponibles y sus rutas; `startTrip()` persiste la selección y rechaza combinaciones inválidas o unidades ocupadas; `updateLocation()` captura puntos del viaje activo; `endTrip()` calcula `distance_km`; `AdminController::assignBusToDriver()` rechaza destinatarios sin rol `driver`; `PassengerController::rateDriver()` usa el Form Request y guarda `trip_id`. |
| Servicios | `GeoDistanceService` centraliza Haversine y la suma de tramos; `RouteStopSyncService` y el cálculo de proximidad de `startTrip()` reutilizan la misma fórmula. |
| Vista | `driver/map.blade.php` agrega `#start-bus-id` y `#start-route-id` con filtro de rutas por `data-bus-ids`; el servidor sigue siendo la fuente de verdad. |
| Pruebas | `StartTripSelectionTest`, `EndTripComputesDistanceTest`, `RoleEndpointMatrixTest`, `BusAssignmentTest` y `RateDriverTripTest`. |

No hubo migraciones: `trips.distance_km`, `trips.path_taken` y `ratings.trip_id` ya existían. El trayecto se acota a 2000 lecturas para evitar JSON ilimitado.

### Decisión de diseño

Se extrajo `GeoDistanceService` durante el refactor porque la distancia aparecía en tres reglas distintas (estimación de minutos entre paradas, validación de proximidad al iniciar y medición al finalizar). Centralizar la fórmula evita que una corrección en un lugar cambie el resultado de otro. La compatibilidad se preservó con defaults y con la vinculación automática del último viaje completado en las calificaciones.

### Verificación

| Comando | Resultado |
|---------|-----------|
| Suite enfocada | 29 pruebas, 103 aserciones, 0 fallos. |
| `php artisan test` | 279 pruebas, 1,141 aserciones, 0 fallos. |
| `./vendor/bin/pint` | Aplicado a los archivos PHP tocados. |
| `git diff --check` | Sin errores de whitespace. |

No se ejecutaron PostgreSQL, Selenium, k6 ni revisión manual de navegador; no se afirma evidencia que no se produjo.

## 2026-11-25 — Frescura, ETA y descubrimiento del pasajero (RNF-08, HU-014, HU-30, HU-009, RF-30, HU-012)

Se cerró el paso 4 del backlog con TDD: primero se escribieron cinco suites nuevas y se confirmó la fase roja (**21 fallas / 1 prueba pasada / 24 aserciones**) por métodos, columnas y rutas inexistentes. Después se implementó la fase verde y se refactorizó con la suite en verde: se eliminaron los cálculos locales muertos (`calculateArrivalTime`, `calculateDistanceAlongRoute`, `calculateRouteDistance`, `isLocationStale`) y `showRouteInfo()` ahora usa las banderas del servidor. Durante el refactor se añadió `DriverMapClientWarningTest` y una prueba de autobús ajeno; ambas se ejecutaron primero y fallaron antes de ajustar el cliente y el endpoint.

### Cambios por capa

| Capa | Cambio |
|------|--------|
| Migraciones | Con aprobación del propietario: `buses.accuracy` nullable, `location_shares.bus_id` nullable con FK e índice, y `bus_stop_subscriptions` con unicidad por pasajero/autobús/parada. Probadas en SQLite en memoria; DDL portable para PostgreSQL, sin ejecución directa en ese motor ni en el SQLite de desarrollo. |
| Modelo | `Bus::LOCATION_FRESH_MINUTES = 5`, atributos derivados `fresh`/`fresh_until`/`sharing_passengers_count`, `updateLocation(..., ?int $accuracy)` y relación `locationShares()`. `LocationShare` suma `scopeForBus()` y `bus_id` opcional. Nuevo `BusStopSubscription`. |
| Servicio y controladores | `ArrivalEstimateService` calcula ETA con Haversine, 20 km/h y redondeo al alza, sin OSRM en consulta. `ArrivalEstimateController` expone `GET /api/stops/{stop}/eta`. `NotificationSubscriptionController` gestiona avisos in-app idempotentes. Los payloads `/api/*` y el broadcast comparten frescura, precisión y conteo agregado. |
| Vistas y JS | El mapa del pasajero consume el endpoint por parada, muestra `#eta-uncertainty-notice`, ETA por autobús, botón «Avísame» y datos de frescura/precisión/conteo en popups. `realtime.js` normaliza `fresh`, `fresh_until`, `accuracy`, `sharing_passengers_count` y `stale`. El mapa del conductor envía `accuracy`, usa Wake Lock cuando existe y advierte que no hay GPS en segundo plano. |
| Pruebas | `BusLocationFreshnessTest`, `ArrivalEstimateTest`, `LocationAccuracyTest`, `LocationShareAggregateTest`, `NotificationSubscriptionTest`, `DriverMapClientWarningTest`; se amplió `RoleEndpointMatrixTest` con el nuevo endpoint. |

### Decisiones y límites

La frescura se define una sola vez en servidor y se consume desde Blade, polling y WebSocket. La ETA es aproximada por diseño: sin tráfico, sin OSRM en caliente y sin hora precisa cuando la posición tiene más de cinco minutos o no hay servicio. `xd.md` afirmaba que ya existía una consulta ETA por parada, pero el código sólo tenía un cálculo en JavaScript; la discrepancia quedó registrada en `requirements-deviations.md`. «Avísame» guarda una preferencia in-app: no hay push externo ni worker de entrega. Ningún payload público o agregado expone nombre, correo, ID ni la identidad de quienes comparten ubicación.

### Verificación

| Comando | Resultado |
|---------|-----------|
| Suite enfocada | 54 pruebas, 266 aserciones, 0 fallos. |
| `php artisan test` | 303 pruebas, 1,280 aserciones, 0 fallos (antes del paso: 279/1,141). |
| Pint | Aplicado y reverificado en los archivos PHP tocados; el `pint --test` global sigue marcando archivos preexistentes no relacionados. |
| `php artisan view:cache` | Vistas compiladas correctamente. |
| `git diff --check` | Sin errores de whitespace. |

No se ejecutaron PostgreSQL, Selenium, k6 ni revisión manual de navegador; Wake Lock y la precisión real del GPS no se verificaron en un dispositivo, sólo se comprobó su presencia y contrato en la vista.

## 2026-11-25 — Emergencias, incidencias, favoritos y métricas (RF-19, HU-025, HU-027, HU-034)

Se cerró el paso 5 del backlog con TDD: primero se escribieron cuatro suites y se confirmó la fase roja (**19 fallas / 1 prueba pasada / 33 aserciones**) porque faltaban rutas, columnas, límite, validaciones y métrica. Después se implementó la fase verde y se refactorizó con la suite en verde.

### Cambios por capa

| Capa | Cambio |
|------|--------|
| Migraciones | Con aprobación del propietario: `incidents.route_id`/`bus_id` (FK nullable `nullOnDelete`), `latitude`, `longitude` e `is_public` con índice `(is_public, created_at)`; `favorite_routes.position` y `label` con índice `(user_id, position)` y backfill por orden histórico. Probadas en SQLite en memoria; DDL portable, sin ejecución directa en PostgreSQL ni en el SQLite de desarrollo. |
| Validación y servicios | `StoreEmergencyReportRequest` y `StoreIncidentRequest` centralizan reglas y mensajes en español. `EmergencyReportService` aplica 5 reportes/hora por usuario con `RateLimiter` y conserva el default heredado del conductor. |
| Controladores | Nuevo `Passenger\EmergencyController`; `DriverController` reutiliza el servicio; `IncidentController` agrega campos opcionales y listado público sin `user`; `FavoriteRouteController` limita a cinco, reordena/etiqueta y compacta al eliminar; `AdminController` ordena emergencias pendientes primero y calcula el promedio global; `IncidentManagementController` carga ruta/autobús para las insignias. |
| Modelos y vistas | `Incident` expone relaciones `route()`/`bus()` y cast de coordenadas; `FavoriteRoute` define `MAX_FAVORITES`/`MAX_LABEL_LENGTH`; `User`/`Route` exponen el pivote con orden. Nuevas vistas `passenger/emergency` e `incidents/public`; menú con etiqueta/reordenación; dashboard con promedio y estado vacío; emergencias con «Requiere atención». |
| Pruebas | `PassengerEmergencyTest`, `IncidentOptionalFieldsTest`, `FavoriteRoutePreferencesTest` y `AdminDashboardTest::test_dashboard_shows_platform_average_rating_with_empty_state`. |

### Decisiones y límites

El límite de emergencias y el tope de favoritos fueron aprobados por el propietario. La visibilidad pública de incidencias nunca incluye nombre, correo, `user_id` ni la relación `user`; la descripción obligatoria de HU-024 se conserva. HU-034 quedó parcial: se entregó la métrica definida, pero el widget de actividad comunitaria depende de HU-017/HU-018 sin criterios de aceptación y no se inventaron.

### Verificación

| Comando | Resultado |
|---------|-----------|
| Suite enfocada | 20 pruebas, 135 aserciones, 0 fallos. |
| `php artisan test` | 323 pruebas, 1,415 aserciones, 0 fallos (antes del paso: 303/1,280). |
| Pint | Aplicado a los archivos PHP tocados. |
| `php artisan view:cache` | Vistas compiladas correctamente. |
| `git diff --check` | Sin errores de whitespace. |

No se ejecutaron PostgreSQL, Selenium, k6 ni revisión manual de navegador; no se afirma evidencia que no se produjo.

## 2026-11-25 — Cuenta, verificación y administración (HU-001, HU-002, HU-005, HU-006, HU-007, HU-030, HU-031, HU-040, HU-041, HU-042)

Se cerró el paso 6 del backlog con TDD: primero se escribieron las cuatro suites nuevas y se confirmó la fase roja por controladores, migración, middleware y métodos de `User` inexistentes; después se implementó la fase verde y se refactorizó con la suite en verde.

### Cambios por capa

| Capa | Cambio |
|------|--------|
| Migración | Con aprobación del propietario, `2026_11_25_000007_add_onboarding_settings_to_users_table.php` agrega `approval_status` (default `approved`, índice con `role`), `license_number` único nullable, `license_photo_path`, `must_change_password` (default `false`), `locale` (default `es`) y `notification_preferences` JSON; los conductores existentes quedan `approved`. Ejercitada en SQLite en memoria, sin ejecución en PostgreSQL ni en el SQLite de desarrollo. |
| Modelo y factory | `User` implementa `MustVerifyEmail`, expone constantes de aprobación y `es`/`en`, y centraliza `isApproved()`, `requiresPasswordChange()` y `notificationPreference()`. `UserFactory` crea cuentas aprobadas por defecto y añade estados `driver`, `admin` y `unverified`. |
| Middleware | `DriverMiddleware` bloquea conductores pendientes/rechazados hacia `driver.pending`; `SetLocale` aplica el idioma persistido; `ForcePasswordChange` redirige a `profile.edit` hasta completar el cambio de contraseña. `verified` se añadió al grupo `passenger`. |
| Controladores | `RegisteredDriverController` crea solicitudes `pending` con foto en disco privado `local`; `DriverApprovalController` aprueba/rechaza y dispara reseteos; `UserController` genera contraseña temporal para conductores y notifica por correo; `PasswordController` limpia el flag; `AuthenticatedSessionController` finaliza el viaje activo del conductor y libera el autobús antes de cerrar sesión, además de revocar enlaces de ubicación. |
| Vistas y rutas | Login con tres entradas por perfil sin elevación, enlace público de registro de conductor, aviso `driver.pending`, preferencias y confirmación de logout en `profile/edit`, y rutas administrativas `admin.drivers.approve`, `admin.drivers.reject` y `admin.drivers.send-password-reset`. |
| Pruebas | `DriverOnboardingTest`, `EmailVerificationAndEntryPointsTest`, `UserPasswordLifecycleTest` y `SettingsAndLogoutTest`; se ajustó `RegistrationTest` a la nueva redirección de verificación. |

### Decisiones y límites

Los accesos por perfil son sólo una pista visual: el rol se valida en servidor. La verificación obligatoria se aplica únicamente a pasajeros; conductores y administradores creados por administración nacen verificados para no romper su flujo. Las traducciones se limitan al chrome de la interfaz. No se añadió 2FA. La contraseña temporal viaja por notificación y nunca se muestra en la respuesta ni se guarda en sesión. HU-034 continúa parcial por la dependencia HU-017/HU-018.

### Verificación

| Comando | Resultado |
|---------|-----------|
| `php artisan test --filter="DriverOnboardingTest|EmailVerificationAndEntryPointsTest|UserPasswordLifecycleTest|SettingsAndLogoutTest"` | 23 pruebas, 90 aserciones, 0 fallos. |
| `php artisan test` | 346 pruebas, 1,505 aserciones, 0 fallos (antes del paso: 323/1,415). |
| Pint | Aplicado y reverificado en los archivos PHP tocados; el `pint --test` global sigue marcando archivos preexistentes no relacionados. |
| `php artisan view:cache` | Vistas compiladas correctamente. |
| `git diff --check` | Sin errores de whitespace. |

No se ejecutaron PostgreSQL, Selenium, k6 ni revisión manual de navegador; no se afirma evidencia que no se produjo.

## 2026-11-25 — Mantenibilidad, respaldos y evidencia parcial (Step 7)

El paso 7 avanzó por capas con TDD y quedó **parcial**, no cerrado. La fase roja se confirmó al fallar la reflexión que esperaba Form Requests y al lanzar `CommandNotFoundException` para los comandos de respaldo; después se implementó y se refactorizó con la suite en verde. No se crearon migraciones.

### Cambios por capa

| Capa | Cambio |
|------|--------|
| Validación | `StoreUserRequest`/`UpdateUserRequest` y `StoreBusRequest`/`UpdateBusRequest` reemplazan el `$request->validate()` duplicado en los controladores administrativos; las reglas comunes viven en traits bajo `App\Http\Requests\Admin\Concerns`. Se conservan mensajes y claves de error. |
| Consola | `backup:database` crea snapshots con retención por conexión y falla ante SQLite en memoria; `backup:restore` sobrescribe con confirmación o `--force`. SQLite usa `VACUUM INTO` y copia del archivo; PostgreSQL usa `pg_dump`/`pg_restore` con `PGPASSWORD` desde configuración. |
| Evidencia | `KeyPagesAccessibilityTest` comprueba estructura accesible de acceso, mapa y perfil; `HotEndpointQueryTest` compara el conteo de consultas de `/api/buses/active` con 2 y 12 autobuses; `AdminValidationRefactorTest` y `DatabaseBackupTest` cubren los cambios anteriores. |
| Documentación | Se crearon `docs/runbooks/backup-restore.md`, `docs/accessibility.md`, `docs/performance.md` y `docs/maintainability.md`; se actualizaron `docs/09-comandos.md`, `docs/10-pruebas-y-estado.md`, trazabilidad y desviaciones. |

### Verificación

| Comando | Resultado |
|---------|-----------|
| `php artisan test --filter=AdminValidationRefactorTest` | 4 pruebas, 22 aserciones, 0 fallos. |
| `php artisan test --filter=DatabaseBackupTest` | 4 pruebas, 13 aserciones, 0 fallos (rojo previo: comandos inexistentes). |
| `php artisan test --filter=KeyPagesAccessibilityTest` | 3 pruebas, 18 aserciones, 0 fallos. |
| `php artisan test --filter=HotEndpointQueryTest` | 1 prueba, 5 aserciones, 0 fallos; el conteo no cambió al pasar de 2 a 12 autobuses. |
| `php artisan test` | 358 pruebas, 1,563 aserciones, 0 fallos (antes del paso: 346/1,505). |
| Pint | Aplicado en todos los archivos PHP tocados; el global sigue marcando archivos preexistentes. |
| `git diff --check` | Sin errores de whitespace. |

### Límites explícitos

RNF-11(b) y RNF-13 están completados en alcance local; RNF-11(a)/(c), RNF-15/HU-033 y RNF-14 son parciales; RNF-06 sigue pendiente de medición. No se extrajeron los 22 bloques JavaScript inline ni se eliminaron los 6 seeders inventariados. No se ejecutaron k6, Selenium, navegador, capturas a 375×667/1366 px, contraste, PostgreSQL, `pg_dump`/`pg_restore` ni restauración contra la base de desarrollo. Tampoco hay umbral p95 confirmado ni lista de navegadores acordada: no se inventaron valores. HU-034 continúa bloqueada por HU-017/HU-018.
