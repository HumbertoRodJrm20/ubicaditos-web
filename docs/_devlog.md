# _devlog — Bitácora técnica (Iteración 1)

Registro técnico de lo realizado en la iteración 1 de Ubicaditos Web. Formato interno de desarrollo, complementario a la documentación numerada `01–12`.

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