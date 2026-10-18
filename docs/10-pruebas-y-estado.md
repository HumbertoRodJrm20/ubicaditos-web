# Pruebas y estado del proyecto

Esta sección describe la suite de pruebas, recoge las observaciones técnicas encontradas durante el análisis del código y propone mejoras.

## Ejecutar las pruebas

```bash
php artisan test
# o
composer run test
```

`phpunit.xml` configura un entorno aislado: base de datos SQLite en memoria (`:memory:`), caché y sesiones en arreglo, colas síncronas, correo en arreglo y `BCRYPT_ROUNDS=4` para acelerar el cifrado.

## Suite actual

| Archivo | Cubre |
|---------|-------|
| `tests/Feature/Auth/AuthenticationTest.php` | Pantalla de login, inicio y cierre de sesión. |
| `tests/Feature/Auth/RegistrationTest.php` | Registro de usuarios. |
| `tests/Feature/Auth/PasswordResetTest.php` | Recuperación de contraseña. |
| `tests/Feature/Auth/PasswordUpdateTest.php` | Cambio de contraseña. |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Confirmación de contraseña. |
| `tests/Feature/Auth/EmailVerificationTest.php` | Verificación de correo. |
| `tests/Feature/ProfileTest.php` | Edición y eliminación del perfil. |
| `tests/Feature/RoleAccessTest.php` | Acceso por rol (admin/driver/passenger), redirección del dashboard y redirección de invitados. |
| `tests/Feature/EndpointProtectionTest.php` | Protección con sesión de Breeze de los endpoints web y JSON, y restricción de acciones de conductor. |
| `tests/Feature/DatabaseConfigurationTest.php` | SQLite en desarrollo/pruebas y PostgreSQL configurado solo para producción y staging. |
| `tests/Feature/ExampleTest.php` | Respuesta de la ruta `/`. |
| `tests/Feature/Bus/OccupancyTest.php` | Cálculo de ocupación y asientos disponibles de un autobús (iteración 3). |
| `tests/Feature/Passenger/BusSearchFilterTest.php` | Búsqueda y filtros de autobuses del pasajero: `q`, `status`, `route_id`, `sort` y datos del modal de detalles (iteración 3). |
| `tests/Feature/Driver/TripLifecycleTest.php` | Ciclo de viaje del conductor: iniciar, finalizar, validaciones de turno y ubicación, pasajeros a bordo (iteración 3). |
| `tests/Feature/Driver/DriverStatisticsTest.php` | Estadísticas del conductor: KPIs, promedios y desglose por ruta (iteración 3). |
| `tests/Feature/Admin/EmergencyManagementTest.php` | Incidencias: listado, filtros, resolución solo de pendientes y vuelta de la unidad a servicio (iteración 3). |
| `tests/Feature/Admin/BusShowTest.php` | Detalle de autobús del administrador: KPIs, viajes y emergencias (iteración 3). |
| `tests/Feature/RealTime/BusLocationUpdatedTest.php` | Evento `BusLocationUpdated`: payload, canal privado `buses`, autorización de canal con el driver reverb y `ShouldBroadcastNow` (iteración 3). |
| `tests/Unit/ExampleTest.php` | Ejemplo unitario. |

**Estado actual:** la suite completa está **en verde (86 pruebas, 289 aserciones)** tras la iteración 3. El detalle de las pruebas de la iteración 1 está en [API Swagger — Iteración 1](12-swagger-api.md); las de la iteración 3, en [Iteración 3](13-iteracion-3.md) y en el [_devlog](_devlog.md).

Además de `php artisan test`, la iteración 3 incorpora scripts de prueba **no ejecutados en esta rama** (previstos para la rama de pruebas):

| Tipo | Ubicación | Herramienta |
|------|-----------|-------------|
| E2E de navegador | `tests/selenium/` | Selenium WebDriver + pytest + Chrome headless (dependencias con `pip install -r requirements.txt`; ejecución con `python3 -m pytest`). |
| Carga/rendimiento | `tests/load/ubicaditos-iteration3-load.js` | k6 (solo lectura; patrón de sesión documentado en `tests/load/README.md`). |

Las pruebas generadas por Breeze habían quedado desincronizadas con el esquema real de `users` en la iteración 0; se corrigieron en la iteración 1:

| Corrección | Detalle |
|------------|---------|
| `UserFactory` | Ahora genera `first_name`, `last_name`, `maternal_last_name`, `phone` y `role` en lugar del campo eliminado `name`. |
| Registro | Envía los campos requeridos por `RegisteredUserController` (nombre, apellidos, teléfono, contraseña fuerte), verifica que el usuario nuevo nace con rol `passenger` y que redirige a `passenger.menu`; además valida datos inválidos. |
| `/` | `ExampleTest` espera la redirección 302 a `route('login')`. |
| Perfil | `ProfileUpdateRequest` valida los tres campos de nombre (regex de letras y espacios) y la vista del perfil los envía junto con el teléfono. |

```php
public function definition(): array
{
    return [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'maternal_last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->numerify('951#######'),
        'role' => 'passenger',
        'is_active' => true,
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
    ];
}
```

La lógica propia del proyecto (roles, protección de endpoints, configuración de base de datos por ambiente) ya tiene cobertura propia en `RoleAccessTest`, `EndpointProtectionTest` y `DatabaseConfigurationTest`; el resto (CRUD de administración, inicio de viajes, favoritos, reportes) aún no.

## Observaciones técnicas

Hallazgos del análisis del código, ordenados por impacto.

### Errores funcionales

| # | Ubicación | Descripción |
|---|-----------|-------------|
| 1 | `app/Models/Bus.php` | **Resuelto en la iteración 3:** `current_passengers` está en `$fillable` (además de los accessors `available_seats` y `occupancy_percentage`). |
| 2 | `ProfileUpdateRequest` y `profile/partials/update-profile-information-form` | **Resuelto en la iteración 1:** ya validan y envían `first_name`, `last_name`, `maternal_last_name` y `phone`. |
| 3 | `ReportController@index` | La consulta de ocupación por hora usa `strftime()`, exclusiva de SQLite; falla en PostgreSQL (producción/staging). Sustituir por `EXTRACT(HOUR FROM started_at)`. |
| 4 | `Admin\StopController` | Registrado como recurso pero sin implementar; no hay forma de gestionar paradas desde la interfaz. |
| 5 | `resources/views/admin/emergencies.blade.php` | **Resuelto en la iteración 3:** existen `admin.emergencies` (listado con filtros) y `admin.emergencies.resolve` (solo pendientes; al resolverse la unidad vuelve a servicio). |
| 6 | `Route::resource` en `routes/web.php` | Parcialmente resuelto: `admin.buses.show` sí está implementado (`Admin\BusController@show`, iteración 3); siguen sin implementar `show`/`index` de usuarios y el de paradas. Conviene usar `->except([...])` o `->only([...])`. |
| 7 | `DashboardController@getActiveBuses` | **Resuelto en la iteración 3:** `available_seats` es un accessor real de `Bus` (capacidad menos pasajeros a bordo, mínimo 0). |
| 8 | `DriverController@startTrip` | Contempla el turno `night`, pero el enum `work_shift` solo admite `morning` y `afternoon`. |

### Inconsistencias de datos

| # | Ubicación | Descripción |
|---|-----------|-------------|
| 9 | `DatabaseSeeder` | Las placas generadas (`OAX-001-A`) no cumplen la validación del formulario (`ABC123`), y la capacidad (38–55) y los modelos difieren de los permitidos; editar esos autobuses obliga a corregir los datos. |
| 10 | `DatabaseSeeder` | Los conductores no reciben `work_shift`, por lo que la validación de horario se omite para ellos. |
| 11 | `TransportSystemSeeder`, `TestDataSeeder` | Usan el campo `name` eliminado y fallan al ejecutarse. |
| 12 | `OaxacaRoutesSeeder`, `ExtendedRoutesSeeder` | Reutilizan códigos (`OAX-106` a `OAX-110`) ya creados por `DatabaseSeeder` y pueden violar la restricción única. |

### Seguridad y autorización

| # | Ubicación | Descripción |
|---|-----------|-------------|
| 13 | `PassengerController@rateDriver` | No verifica que `{driver}` sea realmente un conductor ni que el pasajero haya viajado con él; permite calificaciones ilimitadas. |
| 14 | `AdminController@assignBusToDriver` | No comprueba que el usuario destino tenga rol `driver`. |
| 15 | `BusController` (`driver_id`) | Valida que el usuario exista, pero no que sea conductor ni que esté libre. |
| 16 | `DriverController@updateLocation` | Acepta cualquier coordenada numérica; conviene acotar con `between:-90,90` y `between:-180,180`. |
| 17 | Endpoints `/api/*` | Accesibles para cualquier rol autenticado; si se planea una app móvil, convendría moverlos a `routes/api.php` con Laravel Sanctum. |

### Mantenimiento

| # | Descripción |
|---|-------------|
| 18 | Livewire está instalado pero no se usa. |
| 19 | `package.json` incluye `@tailwindcss/vite` (v4) junto a `tailwindcss` (v3) sin usar el primero. |
| 20 | El repositorio tiene `bun.lock` sin versionar y `package-lock.json` eliminado; conviene decidir un único gestor de paquetes. |
| 21 | Las vistas de mapa (`passenger/dashboard`, `admin/dashboard`, `driver/map`) contienen cientos de líneas de JavaScript en línea, con lógica duplicada (OSRM, simulación, Haversine). Podrían extraerse a módulos en `resources/js` compilados por Vite. |
| 22 | Las reglas de validación de `UserController` y `BusController` están duplicadas entre `store` y `update`; podrían moverse a clases Form Request. |
| 23 | El `README.md` raíz es el genérico de Laravel; puede reemplazarse por un resumen que apunte a esta carpeta `docs/`. |
| 24 | `.env.example` mantiene `APP_NAME=Laravel` y `APP_LOCALE=en`; se sugiere `Ubicaditos` y `es`. |

## Mejoras sugeridas

| Prioridad | Mejora |
|-----------|--------|
| Alta | Corregir `$fillable` de `Bus`, el formulario de perfil y la factory; actualizar las pruebas para que la suite pase. **Hecho (iteraciones 1 y 3).** |
| Alta | Implementar `StopController` y la gestión de emergencias (listado, cambio de estado y resolución). **Gestión de emergencias hecha en la iteración 3; `StopController` sigue pendiente.** |
| Alta | Añadir validaciones de autorización (rol de destino, calificaciones solo tras un viaje). |
| Media | Escribir pruebas Feature para middleware de roles, `startTrip`, CRUD de administración y favoritos. **Parcialmente hecho: roles (`RoleAccessTest`), viajes (`TripLifecycleTest`) y detalle de autobús (`BusShowTest`) en la iteración 3.** |
| Media | Hacer portable la consulta por hora (`HOUR()` / `strftime()` según el driver). |
| Media | Usar el `path_coordinates` almacenado en el frontend para evitar llamar a OSRM en cada carga. |
| Baja | Tiempo real con Laravel Reverb o broadcasting en lugar de simulación en el cliente. **Hecho en la iteración 3: Reverb + canal privado `buses` con fallback de sondeo.** |
| Baja | Extraer el JavaScript de los mapas a módulos reutilizables. **Parcialmente hecho en la iteración 3: `resources/js/realtime.js` (Echo + sondeo) compilado con Vite/bun.** |
| Baja | Implementar la mensajería ya modelada (`Message`). |
