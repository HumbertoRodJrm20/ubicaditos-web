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
| `tests/Feature/Auth/AuthenticationTest.php` | Pantalla de login, inicio, rechazo de cuentas inactivas, cierre de sesión con limpieza de sesión y revocación de enlaces de ubicación activos. |
| `tests/Feature/Auth/RegistrationTest.php` | Registro de usuarios. |
| `tests/Feature/Auth/PasswordResetTest.php` | Recuperación de contraseña. |
| `tests/Feature/Auth/PasswordUpdateTest.php` | Cambio de contraseña. |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Confirmación de contraseña. |
| `tests/Feature/Auth/EmailVerificationTest.php` | Verificación de correo. |
| `tests/Feature/ProfileTest.php` | Edición del perfil, validaciones, eliminación de pasajeros y desactivación segura de conductores con preservación del historial. |
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
| `tests/Feature/Admin/BusManagementTest.php` | Listado y gestión de autobuses: acceso por rol, resumen de flota, filtro por estado, búsqueda, paginación validada, alta con rutas, actualización, eliminación segura y rechazo de estado `emergency` en el CRUD. |
| `tests/Feature/Admin/DriverLifecycleTest.php` | Desactivación de conductores, preservación de viajes/calificaciones, bloqueo con viajes activos y exclusión de la lista activa. |
| `tests/Feature/Admin/AdminDashboardTest.php` | Contadores del dashboard administrativo: flota por estado, incidencias y emergencias pendientes, tarjeta de estado, promedio global de calificaciones con estado vacío y render vacío (iteración 7). |
| `tests/Feature/Admin/AdminReportsTest.php` | Vista única de reportes y estadísticas: acceso por rol, redirección de `admin.statistics`, datos de viajes, autobuses, conductores y accesos de exportación (iteración 7). |
| `tests/Feature/ThemeTest.php` | Modo oscuro: inicialización sin destello y toggle compartido en login, layout del pasajero y layout administrativo (iteración 7). |
| `tests/Feature/RealTime/BusLocationUpdatedTest.php` | Evento `BusLocationUpdated`: payload, canal privado `buses`, autorización de canal con el driver reverb y `ShouldBroadcastNow` (iteración 3). |
| `tests/Feature/Likes/LikeTest.php` | Me gusta polimórfico en rutas y paradas: alternancia, contador público, unicidad por usuario y estado personal (iteración 4). |
| `tests/Feature/Passenger/FavoriteRouteTest.php` | Rutas favoritas: alta/baja, duplicados, acceso por rol y exposición en rutas, menú y perfil (iteración 4). |
| `tests/Feature/Passenger/LocationShareTest.php` | Ubicación compartida: creación con token y caducidad, revocación del enlace anterior, actualización solo del dueño, endpoints públicos y caducidad (iteración 4). |
| `tests/Feature/Admin/IncidentManagementTest.php` | Gestión administrativa de reportes ciudadanos: listado, filtros, búsqueda, transiciones de estado y `resolved_at` (iteración 4). |
| `tests/Feature/Incidents/IncidentTest.php` | Flujo de reportes del usuario: acceso desde menú y navegación, disponibilidad por rol, validaciones, persistencia, aislamiento por usuario y protección contra campos manipulados (iteración 5). |
| `tests/Feature/Passenger/RouteListingTest.php` | Listado completo de rutas activas, recorrido completo de paradas, exclusión de rutas inactivas y marcado de favoritas (iteración 6). |
| `tests/Feature/Admin/StopManagementTest.php` | CRUD de paradas del Estudio, validación de coordenadas, autorización, estado y eliminación segura con favoritos/likes preservados. |
| `tests/Feature/Admin/StopCatalogTest.php` | Búsqueda/paginación del catálogo, autorización administrativa y límite de paradas principales iniciales en el mapa. |
| `tests/Feature/Admin/RouteCrudCharacterizationTest.php` | CRUD de rutas, historial seguro, formularios heredados sin consultas al catálogo y lectura estable de la secuencia combinada. |
| `tests/Feature/Admin/RouteStudioManagementTest.php` | Datos y escrituras del Estudio, sincronización ordenada, estado de cambios sin guardar, ausencia de OSRM al cargar y preservación de geometría ante error. |
| `tests/Feature/Emergency/PassengerEmergencyTest.php` | Emergencias del pasajero: acceso por rol, unidad/coordenadas opcionales, límite de cinco por hora, orden administrativo y default heredado del conductor. |
| `tests/Feature/Incidents/IncidentOptionalFieldsTest.php` | Incidencias con referencias y coordenadas opcionales, visibilidad pública sin identidad del reportante e insignias en administración. |
| `tests/Feature/Passenger/FavoriteRoutePreferencesTest.php` | Tope de cinco favoritos, orden y etiqueta persistentes, compactación al eliminar y aislamiento por usuario. |
| `tests/Feature/Auth/DriverOnboardingTest.php` | Registro público de conductores, licencia y foto privada, estados pendiente/rechazado/aprobado y aprobación administrativa. |
| `tests/Feature/Auth/EmailVerificationAndEntryPointsTest.php` | Entradas por perfil sin elevación de roles, verificación de pasajeros, reenvío limitado y acceso condicionado. |
| `tests/Feature/Admin/UserPasswordLifecycleTest.php` | Alta de conductores con contraseña temporal notificada, obligatoriedad de cambio y restablecimiento disparado por administración. |
| `tests/Feature/Profile/SettingsAndLogoutTest.php` | Idioma y preferencias in-app, aplicación por middleware, confirmación de logout y cierre de viaje activo del conductor. |
| `tests/Feature/Admin/AdminValidationRefactorTest.php` | Uso de Form Requests en altas/ediciones de usuarios y autobuses; claves de error y mensajes heredados. |
| `tests/Feature/Console/DatabaseBackupTest.php` | Creación, retención, restauración y fallo explícito del respaldo local con SQLite temporal. |
| `tests/Feature/Accessibility/KeyPagesAccessibilityTest.php` | Idioma, etiquetas, `aria-label` y clases `dark:` en acceso, mapa del pasajero y perfil. |
| `tests/Feature/Performance/HotEndpointQueryTest.php` | Guarda de consultas en `GET /api/buses/active`: el conteo no crece con el número de autobuses. |
| `tests/Unit/ExampleTest.php` | Ejemplo unitario. |

**Estado verificado el 2026-11-25:** la suite completa está en verde (**358 pruebas, 1,563 aserciones**). El detalle de las pruebas de la iteración 1 está en [API Swagger — Iteración 1](12-swagger-api.md); las de la iteración 3, en [Iteración 3](13-iteracion-3.md); las de la iteración 4, en [Iteración 4](16-iteracion-4.md); las de las iteraciones 5 y 6, en [Iteración 5 y 6](17-iteracion-5-6.md); las de la iteración 7, en [Iteración 7](18-iteracion-7.md); y el histórico, en el [_devlog](_devlog.md).

Además de `php artisan test`, el proyecto incorpora scripts de prueba **no ejecutados en este entorno** (previstos para la rama de pruebas):

| Tipo | Ubicación | Herramienta |
|------|-----------|-------------|
| E2E de navegador | `tests/selenium/` | Selenium WebDriver + pytest + Chrome headless (dependencias con `pip install -r requirements.txt`; ejecución con `python3 -m pytest`). Incluye flujos de likes, rutas favoritas, ubicación compartida y gestión de reportes ciudadanos. |
| Carga/rendimiento | `tests/load/ubicaditos-iteration4-load.js` | k6 (solo lectura; patrón de sesión documentado en `tests/load/README.md`). |

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

La lógica propia del proyecto (roles, protección de endpoints, configuración de base de datos por ambiente) tiene cobertura en `RoleAccessTest`, `EndpointProtectionTest` y `DatabaseConfigurationTest`. A partir de la iteración 3 se añadieron pruebas de búsqueda/filtros, ciclo de viaje, estadísticas, incidencias y tiempo real; la iteración 4 incorpora likes, rutas favoritas, ubicación compartida y gestión administrativa de reportes ciudadanos. Las iteraciones 5 y 6 refuerzan el flujo de reportes del usuario, el listado completo de rutas, el formulario de perfil con teléfono y apellido materno, y el cierre de sesión seguro. La iteración 7 añade cobertura del listado y CRUD de autobuses, de los contadores del dashboard administrativo y del tema oscuro compartido. El Estudio cuenta con cobertura dedicada para rutas, sincronización y paradas; el paso 5 del backlog añadió emergencias del pasajero, incidencias públicas sin identidad, preferencias de favoritos y la métrica de calificación. El contador vigente, verificado el 2026-11-25 tras Pint en los archivos tocados, es de **358 pruebas / 1,563 aserciones** sin fallos. La fase 6 añadió 23 pruebas / 90 aserciones enfocadas y la fase 7 añadió 12 pruebas / 58 aserciones (validación, respaldos, accesibilidad estructural y guarda de consultas). La migración de `users` y el respaldo/restauración se ejercitaron en SQLite en memoria o archivo temporal; no se aplicaron a PostgreSQL ni al SQLite de desarrollo. No se ejecutaron Selenium, k6 ni revisión manual de navegador; los límites están en `docs/accessibility.md` y `docs/performance.md`.

## Observaciones técnicas

Hallazgos del análisis del código, ordenados por impacto.

### Errores funcionales

| # | Ubicación | Descripción |
|---|-----------|-------------|
| 1 | `app/Models/Bus.php` | **Resuelto en la iteración 3:** `current_passengers` está en `$fillable` (además de los accessors `available_seats` y `occupancy_percentage`). |
| 2 | `ProfileUpdateRequest` y `profile/partials/update-profile-information-form` | **Resuelto en la iteración 1:** ya validan y envían `first_name`, `last_name`, `maternal_last_name` y `phone`. |
| 3 | `ReportController@index` | La consulta de ocupación por hora usa `strftime()`, exclusiva de SQLite; falla en PostgreSQL (producción/staging). Sustituir por `EXTRACT(HOUR FROM started_at)`. |
| 4 | `RouteManagementController@payload` / `StopController@data` / `RouteController@create/edit` | **Brecha del catálogo de rutas cerrada en 2026-11-25:** el mapa usa hasta 200 paradas principales, la búsqueda/paginación devuelve hasta 50 resultados y los formularios heredados dejaron de consultar/renderizar todo el catálogo. RNF-05 permanece parcial por consultas de otros listados aún no medidas. |
| 5 | `resources/views/admin/emergencies.blade.php` | **Resuelto en la iteración 3:** existen `admin.emergencies` (listado con filtros) y `admin.emergencies.resolve` (solo pendientes; al resolverse la unidad vuelve a servicio). |
| 6 | `Route::resource` en `routes/web.php` | Parcialmente resuelto: `admin.buses.show` sí está implementado (`Admin\BusController@show`, iteración 3); siguen sin vista propia `show` de rutas/usuarios e `index` de usuarios. Las acciones de lectura del recurso de paradas redirigen al Estudio. Conviene usar `->except([...])` o `->only([...])` para rutas no soportadas. |
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
| Alta | Completar cobertura de `StopController` y la gestión de emergencias. **CRUD de paradas y gestión de emergencias implementados; ver `StopManagementTest` y `EmergencyManagementTest`.** |
| Alta | Añadir validaciones de autorización (rol de destino, calificaciones solo tras un viaje). |
| Media | Escribir pruebas Feature para middleware de roles, `startTrip`, CRUD de administración y favoritos. **Parcialmente hecho: roles (`RoleAccessTest`), viajes (`TripLifecycleTest`) y detalle de autobús (`BusShowTest`) en la iteración 3.** |
| Media | Hacer portable la consulta por hora (`HOUR()` / `strftime()` según el driver). |
| Media | Usar el `path_coordinates` almacenado en el frontend para evitar llamar a OSRM en cada carga. |
| Baja | Tiempo real con Laravel Reverb o broadcasting en lugar de simulación en el cliente. **Hecho en la iteración 3: Reverb + canal privado `buses` con fallback de sondeo.** |
| Baja | Extraer el JavaScript de los mapas a módulos reutilizables. **Parcialmente hecho en la iteración 3: `resources/js/realtime.js` (Echo + sondeo) compilado con Vite/bun.** |
| Baja | Implementar la mensajería ya modelada (`Message`). |
