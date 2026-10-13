# Comandos de consola

Además de los comandos estándar de Laravel, el proyecto incluye dos comandos Artisan propios en `app/Console/Commands`. Laravel 12 los descubre automáticamente, sin necesidad de registrarlos.

## routes:generate-paths

```bash
php artisan routes:generate-paths
```

**Clase:** `App\Console\Commands\GenerateRoutePaths`

Genera el trazo geográfico (`path_coordinates`) de cada ruta siguiendo las calles reales, usando el servicio público de OSRM.

Funcionamiento:

1. Carga todas las rutas con sus paradas.
2. Omite las rutas con menos de 2 paradas.
3. Ordena las paradas por `pivot.order` y arma la lista de *waypoints* `lng,lat;lng,lat;...`.
4. Consulta `https://router.project-osrm.org/route/v1/driving/{waypoints}?overview=full&geometries=geojson` con un *timeout* de 30 segundos.
5. Convierte las coordenadas GeoJSON `[lng, lat]` al formato `{lat, lng}` y las guarda en la ruta.
6. Espera 1 segundo entre rutas para no saturar el servicio.

Salida de ejemplo:

```text
Generando rutas que siguen las calles...
Procesando: Ruta 1 - Centro - Tule
✅ Ruta 1 - Centro - Tule: 812 puntos generados
...
🎉 Proceso completado!
```

Se recomienda ejecutarlo después de `php artisan db:seed` o cada vez que se modifiquen las paradas de una ruta. Requiere conexión a internet.

## buses:simulate

```bash
php artisan buses:simulate
php artisan buses:simulate --interval=2
```

**Clase:** `App\Console\Commands\SimulateBusMovement`

| Opción | Valor por defecto | Descripción |
|--------|-------------------|-------------|
| `--interval` | `5` | Segundos entre cada actualización. |

Simula el movimiento de los autobuses activos actualizando su posición en la base de datos, lo que permite probar los endpoints `/api/buses/*` y las vistas sin conductores reales.

Funcionamiento:

1. Obtiene los autobuses con `status = active` y su primera ruta con paradas ordenadas.
2. En un bucle infinito, avanza cada autobús un 10 % del tramo entre su parada actual y la siguiente, interpolando latitud y longitud linealmente.
3. Al llegar a una parada, lo informa en consola (`🚏 Bus OAX-001-A llegó a: Santo Domingo`) y continúa hacia la siguiente; al terminar la ruta vuelve a empezar.
4. Guarda cada posición con `Bus::updateLocation()`.
5. Ocasionalmente intenta variar el número de pasajeros a bordo.

Se detiene con `Ctrl + C`.

> El movimiento es en línea recta entre paradas, no sobre el trazo de calles. Las vistas de pasajero y administrador tienen su propia simulación visual, en el navegador, sobre el trazo OSRM.

## Comandos de Laravel más usados

| Comando | Descripción |
|---------|-------------|
| `php artisan serve` | Servidor de desarrollo. |
| `php artisan migrate` | Ejecuta migraciones pendientes. |
| `php artisan migrate:fresh --seed` | Recrea la base de datos y la puebla. |
| `php artisan db:seed --class=NombreSeeder` | Ejecuta un seeder específico. |
| `php artisan route:list --except-vendor` | Lista las rutas de la aplicación. |
| `php artisan tinker` | Consola interactiva para consultar modelos. |
| `php artisan queue:listen` | Procesa trabajos en cola. |
| `php artisan pail` | Muestra los logs en tiempo real. |
| `php artisan optimize:clear` | Limpia cachés de configuración, rutas y vistas. |
| `./vendor/bin/pint` | Formatea el código PHP según el estilo de Laravel. |

### Ejemplos con Tinker

```php
// Autobuses activos con su conductor
App\Models\Bus::with('driver')->where('status', 'active')->get();

// Paradas ordenadas de una ruta
App\Models\Route::where('code', 'OAX-101')->first()->getOrderedStops()->pluck('name');

// Calificación promedio de un conductor
App\Models\User::where('email', 'chofer1@ubicaditos.com')->first()->average_rating;

// Asignar turno vespertino a un conductor
App\Models\User::where('email', 'chofer1@ubicaditos.com')->update(['work_shift' => 'afternoon']);
```
