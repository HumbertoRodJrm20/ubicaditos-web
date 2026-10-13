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
| `tests/Feature/ExampleTest.php` | Respuesta de la ruta `/`. |
| `tests/Unit/ExampleTest.php` | Ejemplo unitario. |

Son las pruebas generadas por Breeze y Laravel. **Actualmente fallan 21 de 25** porque no se actualizaron tras los cambios del proyecto:

| Causa | Pruebas afectadas | Solución |
|-------|-------------------|----------|
| `UserFactory` genera el campo `name`, que ya no existe en `users`. | Todas las que crean usuarios. | Actualizar la factory con `first_name`, `last_name`, `maternal_last_name`, `phone` y `role`. |
| El registro exige campos nuevos (apellidos, teléfono, contraseña fuerte) y redirige a `passenger.menu`. | `RegistrationTest`. | Enviar los campos requeridos y ajustar la redirección esperada. |
| `/` redirige a `/login` (302) en lugar de responder 200. | `ExampleTest`. | Esperar `assertRedirect(route('login'))`. |
| `ProfileUpdateRequest` valida `name`. | `ProfileTest`. | Validar los campos de nombre separados. |

Ejemplo de factory actualizada:

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

No existen pruebas para la lógica propia del proyecto (roles, CRUD de administración, inicio de viajes, favoritos, reportes).

## Observaciones técnicas

Hallazgos del análisis del código, ordenados por impacto.

### Errores funcionales

| # | Ubicación | Descripción |
|---|-----------|-------------|
| 1 | `app/Models/Bus.php` | `current_passengers` no está en `$fillable`, así que `DriverController@updatePassengers` y `buses:simulate` no guardan el número de pasajeros. |
| 2 | `ProfileUpdateRequest` y `profile/partials/update-profile-information-form` | Validan y envían `name`, pero la tabla usa `first_name`, `last_name` y `maternal_last_name`; la actualización del perfil no puede funcionar correctamente. |
| 3 | `ReportController@index` | La consulta de ocupación por hora usa `strftime()`, exclusiva de SQLite; falla en MySQL o PostgreSQL. |
| 4 | `Admin\StopController` | Registrado como recurso pero sin implementar; no hay forma de gestionar paradas desde la interfaz. |
| 5 | `resources/views/admin/emergencies.blade.php` | Usa `route('admin.emergencies.resolve')`, que no existe; tampoco hay ruta ni controlador que muestre la vista. Las emergencias reportadas solo aparecen como conteos en los reportes. |
| 6 | `Route::resource` en `routes/web.php` | Se registran `show` (y `index` de usuarios) sin métodos en los controladores. Conviene usar `->except(['show'])` o `->only([...])`. |
| 7 | `DashboardController@getActiveBuses` | Devuelve `available_seats`, que no existe en el modelo (siempre `null`). |
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
| Alta | Corregir `$fillable` de `Bus`, el formulario de perfil y la factory; actualizar las pruebas para que la suite pase. |
| Alta | Implementar `StopController` y la gestión de emergencias (listado, cambio de estado y resolución). |
| Alta | Añadir validaciones de autorización (rol de destino, calificaciones solo tras un viaje). |
| Media | Escribir pruebas Feature para middleware de roles, `startTrip`, CRUD de administración y favoritos. |
| Media | Hacer portable la consulta por hora (`HOUR()` / `strftime()` según el driver). |
| Media | Usar el `path_coordinates` almacenado en el frontend para evitar llamar a OSRM en cada carga. |
| Baja | Tiempo real con Laravel Reverb o broadcasting en lugar de simulación en el cliente. |
| Baja | Extraer el JavaScript de los mapas a módulos reutilizables. |
| Baja | Implementar la mensajería ya modelada (`Message`). |
