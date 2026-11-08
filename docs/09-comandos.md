# Comandos de consola

Además de los comandos estándar de Laravel, el proyecto incluye tres comandos Artisan propios en `app/Console/Commands`. Laravel 12 los descubre automáticamente, sin necesidad de registrarlos.

## routes:generate-paths

```bash
php artisan routes:generate-paths
php artisan routes:generate-paths --force
php artisan routes:generate-paths --route=OAX-101 --force
```

**Clase:** `App\Console\Commands\GenerateRoutePaths`

Genera el trazo geográfico (`path_coordinates`) de cada ruta siguiendo las calles reales, usando el servicio público de OSRM.

| Opción | Por defecto | Descripción |
|---|---|---|
| `--force` | `false` | Regenera el trazado aunque la ruta ya tenga `path_coordinates`. |
| `--route=` | — | Procesa únicamente la ruta con ese código. |
| `--sleep=` | `1` | Segundos de espera entre peticiones a OSRM. |

Funcionamiento:

1. Carga las rutas y sus **paradas principales** (`route_stop.is_frequent = false`).
2. Omite las rutas con menos de 2 paradas principales.
3. **Conserva el trazado existente** salvo que se use `--force`.
4. Ordena las paradas principales por `pivot.order` y arma la lista de *waypoints* `lng,lat;lng,lat;...`. Las paradas de alta frecuencia no se envían a OSRM porque ya están muestreadas sobre ese trazado.
5. Consulta `https://router.project-osrm.org/route/v1/driving/{waypoints}?overview=full&geometries=geojson` con un *timeout* de 30 segundos.
6. Convierte las coordenadas GeoJSON `[lng, lat]` al formato `{lat, lng}` y las guarda en la ruta.
7. Espera `--sleep` segundos entre rutas para no saturar el servicio.

Se recomienda ejecutarlo **una sola vez** después de `php artisan db:seed` o cuando se modifiquen las paradas de una ruta. Requiere conexión a internet. A partir de ese momento el navegador dibuja las rutas desde la base de datos y ya no consulta OSRM.

El seeder `FrequentRouteStopsSeeder` agrega paradas aproximadamente cada 500 m sobre el trazado guardado y las marca con `is_frequent = true`. Es idempotente y se ejecuta automáticamente al final de `DatabaseSeeder`; en una base existente puede ejecutarse con `php artisan db:seed --class=FrequentRouteStopsSeeder`.

> Los seeders ya **no inventan** trazados: dejan `path_coordinates` vacío para
> no dibujar líneas rectas sobre edificios. Después de poblar la base ejecuta:
>
> ```bash
> php artisan routes:generate-paths --force
> ```
>
> Sin ese paso verás líneas rectas entre paradas (o rutas sin dibujar) hasta
> que se regeneren los trazados.

## routes:diagnose

```bash
php artisan routes:diagnose
php artisan routes:diagnose --threshold=0.8
```

**Clase:** `App\Console\Commands\DiagnoseRoutes`

Reporta el estado de las rutas antes de optimizar o depurar el mapa:

- rutas sin `path_coordinates` o con menos de 2 paradas;
- autobuses asignados y puntos de trazado por ruta;
- pares de rutas con paradas en común (similitud Jaccard, configurable con `--threshold`);
- tramos compartidos por varias rutas y cuántos dibujos se evitan al deduplicarlos.

Ejemplo de salida:

```text
🧩 Tramos compartidos
  Instancias de tramo (suma de todas las rutas): 257
  Tramos únicos: 90
  Tramos compartidos por 2+ rutas: 49
  Dibujos que se evitan al deduplicar: 167
```

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
