# Diagnóstico y optimización del dibujo de rutas

**Fecha:** 2 de noviembre de 2026
**Alcance:** mapas de pasajero, administrador y conductor.
**Estado:** diagnóstico + optimización implementada.

---

## 1. Resumen ejecutivo

El problema no era que existieran “rutas duplicadas” con el mismo código en la
base de datos (los 18 códigos actuales son únicos). El problema real era doble:

1. **Cada mapa volvía a pedir los trazados a OSRM en el navegador**, ignorando
   `routes.path_coordinates`, que ya estaba guardado en la base de datos.
2. **Cada ruta se dibujaba completa, aunque compartiera tramos con otras.** Las
   18 rutas comparten paradas del centro (el Zócalo aparece en las 18) y, al
   pintarse una encima de otra, el mapa se satura y las líneas se “sobrescriben”.

Con los datos actuales:

| Métrica | Antes | Después |
|---|---:|---:|
| Peticiones a OSRM al abrir el mapa | 18 (hasta 54 con reintentos) | 0 |
| Polilíneas dibujadas | 257 (una por tramo de cada ruta) | 90 (tramos únicos) |
| Dibujos evitados por deduplicación | 0 | 167 |
| Tramos compartidos por 2+ rutas | — | 49 |
| Rutas con el Zócalo en común | 18 | 18 (dato de la red) |
| Puntos de trazado en la BD | 1,298 (rectas con ruido) | 25,292 (OSRM) |
| Tramos con geometría de calle | 0 | 257 de 257 |

La solución implementada **no borra rutas ni viajes**. Lo que hace es dibujar la
red una sola vez por tramo y resaltar la ruta seleccionada cuando el usuario la
filtra.

---

## 2. Cómo se dibujaban las rutas (flujo anterior)

### 2.1 Origen de los datos

```
routes (tabla)
  ├─ path_coordinates  → JSON con puntos {lat,lng} del trazado
  └─ route_stop        → paradas ordenadas por pivot.order
        ↓
Route::with(['stops', 'buses'])->where('is_active', true)->get()
        ↓
Blade: const routes = @json($routes)
        ↓
Leaflet: L.polyline(...)
```

Archivos implicados:

| Archivo | Responsabilidad |
|---|---|
| `app/Models/Route.php` | `path_coordinates` como `array`, relación `stops()` con `order`. |
| `app/Http/Controllers/Passenger/PassengerController.php::map()` | Carga rutas, paradas y buses activos. |
| `app/Http/Controllers/Admin/AdminController.php::index()` | Carga los mismos datos para el panel admin. |
| `app/Http/Controllers/Driver/DriverController.php::map()` | Carga la ruta y paradas del conductor. |
| `resources/views/passenger/dashboard.blade.php` | `drawRoute()` + `loadVisibleRoutes()` + filtros. |
| `resources/views/admin/dashboard.blade.php` | `drawRoute()` + `loadVisibleRoutes()`. |
| `resources/views/driver/map.blade.php` | `generateRoute()` + `fallbackStraightLine()`. |
| `app/Console/Commands/GenerateRoutePaths.php` | Genera `path_coordinates` con OSRM (uso offline). |

### 2.2 Flujo anterior del pasajero y del administrador

1. Al cargar la vista se ejecutaba `loadVisibleRoutes()`.
2. Se obtenía el conjunto de rutas de todos los buses activos.
3. Para **cada ruta** se llamaba a `drawRoute(route)`.
4. `drawRoute()` construía los waypoints con las paradas y hacía:

   ```
   GET https://router.project-osrm.org/route/v1/driving/{waypoints}
       ?overview=full&geometries=geojson
   ```

   con hasta **3 intentos** y 2 segundos de espera entre intentos.
5. Si OSRM respondía, se creaba una `L.polyline` por ruta.
6. Si fallaban los 3 intentos, la ruta **no se mostraba** (pasajero y admin).

Con 18 rutas, esto podía significar hasta **54 peticiones** a un servidor público
(OSRM demo) en la carga inicial, ejecutadas en serie.

### 2.3 Flujo anterior del conductor

`generateRoute()` hacía lo mismo, pero sólo para la ruta del bus. Si OSRM fallaba,
usaba `fallbackStraightLine()`: una línea recta entre paradas.

### 2.4 Por qué se “sobrescribían” las rutas

- `loadVisibleRoutes()` dibujaba **todas** las rutas de los buses activos, sin
  importar si compartían recorrido.
- Todas las polilíneas usaban el mismo `weight: 5` y `opacity: 0.9`.
- La mayoría de rutas pasan por el centro. En los datos actuales:
  - 107 de 153 pares de rutas comparten 3 o más paradas.
  - El Zócalo lo usan las 18 rutas.
  - 49 de los 90 tramos únicos son compartidos por 2 o más rutas.
- Los tramos compartidos se dibujaban una vez por ruta, no una vez por tramo.

Además había un detalle de concurrencia: `routesLoaded[route.id]` se marcaba
sólo **después** de que OSRM respondía. Si dos acciones pedían la misma ruta
antes de que terminara la petición (por ejemplo, el filtro de ruta y el botón de
rutas cercanas), se hacían dos peticiones y se añadían dos polilíneas. La segunda
sobrescribía la referencia en `routePolylines[route.id]`, dejando la primera
huérfana en el mapa.

---

## 3. Problemas detectados en los seeders

Estos seeders explican la sensación de “rutas repetidas y sobreescritas” cuando
se vuelven a ejecutar:

| Seeder | Problema | Corrección aplicada |
|---|---|---|
| `DatabaseSeeder` | Generaba `path_coordinates` interpolando entre paradas con un desplazamiento aleatorio. Eran líneas rectas que cruzaban casas. | Ya no inventa el trazado: lo deja en `null` e indica ejecutar `routes:generate-paths --force`. |
| `ExtendedRoutesSeeder` | Sobrescribía `path_coordinates` de rutas existentes con líneas rectas entre paradas. | Ya no escribe ningún trazado; conserva el que exista. |
| `UpdateRoutePathsSeeder` | Escribía coordenadas aproximadas a mano para OAX-106, OAX-107 y OAX-108. | Ya no escribe coordenadas: sólo avisa qué rutas no tienen trazado. |
| `FixRoutesSeeder` | Definía `path_coordinates` aproximados a mano para OAX-201..204. | Ya no los escribe; deja el trazado vacío para generarlo con OSRM. |
| `OaxacaRoutesSeeder` | Creaba 2 autobuses nuevos por ruta en cada ejecución. | Sólo crea autobuses si la ruta no tiene ninguno. |
| `ExtendedRoutesSeeder` | Creaba 2-3 autobuses nuevos por ruta en cada ejecución. | Sólo crea autobuses si la ruta no tiene ninguno. |
| `FixRoutesSeeder` | Borraba por código OAX-109..114, que también usa `DatabaseSeeder` para rutas distintas. | Ahora borra por nombre exacto de las rutas largas. |
| `FixRoutesSeeder` | `Route::create()` y `attach()` sin comprobar existencia. | Usa `firstOrCreate` y sólo crea buses para rutas nuevas. |
| `TransportSystemSeeder` | Crea rutas de CDMX (R1-R5), ajenas a Oaxaca. | Pendiente: moverlo a un seeder de demo o eliminarlo. |
| `DatabaseSeeder` | No es idempotente: crea usuarios, rutas y viajes en cada ejecución. | Recomendación: usarlo sólo con `migrate:fresh --seed`. |

> **Importante:** después de un `migrate:fresh --seed` hay que ejecutar
> `php artisan routes:generate-paths --force`. Los seeders dejan las rutas sin
> trazado a propósito para no volver a dibujar líneas rectas sobre edificios.

---

## 4. Optimización implementada

### 4.1 Nueva capa compartida: `resources/js/route-layer.js`

Se importa desde `resources/js/app.js` y queda disponible como
`window.UbicaditosRouteLayer`.

Responsabilidades:

1. **Una sola fuente de verdad:** `pathForRoute(route)` usa
   `route.path_coordinates`, que ahora contiene la geometría de OSRM sobre
   calles. Si una ruta no tiene trazado, cae a líneas rectas entre paradas sólo
   como último recurso; `routes:diagnose` la reporta para regenerarla.
2. **División por tramos:** `segmentsForRoute(route)` corta el trazado en los
   puntos más cercanos a cada parada y genera un tramo por par consecutivo.
3. **Clave estable de tramo:** el par de paradas (`idA|idB`, sin importar el
   sentido) identifica el tramo.
4. **Deduplicación:** `createMapRoutes()` dibuja **una sola polilínea por tramo
   único**. Si varias rutas comparten el tramo, se pinta en gris y el popup
   indica qué rutas lo usan.
5. **Sin duplicados concurrentes:** las líneas individuales se cachean por
   `route.id`; `ensureRouteLine()` devuelve siempre la misma instancia.
6. **Red + ruta seleccionada:**
   - `showNetwork(routeIds)`: dibuja la red deduplicada (opcionalmente filtrada).
   - `showOnlyRoute(routeId)`: oculta la red y resalta una ruta completa en su
     color.
   - `showAllRoutes()`: vuelve a la red.
   - `fitRoute(routeId)`: encuadra la ruta seleccionada.

### 4.2 Integración en las vistas

**Pasajero (`resources/views/passenger/dashboard.blade.php`)**

- `loadVisibleRoutes()` ya no llama a OSRM ni dibuja ruta por ruta: construye la
  red deduplicada.
- `drawRoute()` sólo crea la línea individual desde el trazado guardado.
- El filtro de ruta usa `showOnlyRoute()`.
- “Mostrar solo rutas cercanas” filtra los tramos de la red por las rutas
  cercanas, sin volver a dibujar.
- El buscador y los popups siguen funcionando.

**Administrador (`resources/views/admin/dashboard.blade.php`)**

- `loadVisibleRoutes()` usa la red deduplicada.
- `routePathsOSRM[route.id]` se llena desde `path_coordinates`, que es lo que usa
  la simulación de autobuses del panel.
- Se espera de forma acotada a que Vite cargue el módulo (`waitForRouteLayer`)
  para evitar una condición de carrera con el script inline.

**Conductor (`resources/views/driver/map.blade.php`)**

- `generateRoute()` dibuja `route.path_coordinates` (geometría OSRM).
- Si no existe, usa líneas rectas entre paradas como último recurso.
- Ya no consulta OSRM desde el navegador.

### 4.3 Comandos de mantenimiento

**Diagnóstico:**

```bash
php artisan routes:diagnose
php artisan routes:diagnose --threshold=0.8
```

Reporta:

- rutas sin trazado o con menos de 2 paradas;
- pares de rutas con paradas en común (Jaccard);
- tramos compartidos y cuántos dibujos se evitan;
- rutas más repetidas.

**Generación de trazados reales (una sola vez):**

```bash
php artisan routes:generate-paths
```

- Conserva los trazados existentes.
- Procesa sólo rutas sin `path_coordinates`.
- Para regenerar todo:

```bash
php artisan routes:generate-paths --force
```

- Para una sola ruta:

```bash
php artisan routes:generate-paths --route=OAX-101 --force
```

Los trazados quedan guardados en la base de datos; el navegador ya no depende de
OSRM para pintar el mapa. Ejecución recomendada después de poblar la base:

```bash
php artisan migrate:fresh --seed
php artisan routes:generate-paths --force
```

---

## 5. Verificación realizada

1. `npm run build` → compila correctamente (Vite).
2. `php artisan view:cache` → todas las vistas Blade compilan.
3. Prueba del módulo con los datos reales de `database/database.sqlite`:

   ```
   routes: 18
   route segment instances: 257
   unique segments: 90
   shared segments: 49
   draws avoided: 167
   bad routes: 0
   ```

4. `php artisan routes:diagnose` → reporta correctamente las 18 rutas y los
   tramos compartidos.
5. `php artisan routes:generate-paths --force` → regeneró las 18 rutas con
   **25,292 puntos** de OSRM (antes 1,298 puntos de líneas rectas).
6. Prueba de los 257 tramos con los datos regenerados:

   ```
   routes: 18
   segments: 257
   straightFallbacks: 0
   minSegmentPoints: 6
   maxSegmentPoints: 447
   ```

   Ningún tramo quedó como línea recta de dos puntos: todos usan la geometría
   de calles guardada.

---

## 6. Recomendaciones siguientes

1. **Consolidar los seeders.** Hoy conviven seeders de Oaxaca y de CDMX, y varios
   se solapan. Lo ideal es dejar un único seeder de demo idempotente y mover los
   demás a `database/seeders/demo/`.
2. **Decidir si se fusionan rutas en la base de datos.** La optimización actual
   resuelve el mapa sin borrar datos. Si se quiere reducir el catálogo, los
   candidatos con más solapamiento son:
   - OAX-103 / OAX-106 (82% de similitud)
   - OAX-112 / OAX-115 (76%)
   - OAX-111 / OAX-114 (75%)
   - OAX-102 / OAX-116 (74%)
   - OAX-110 / OAX-113 (71%)
3. **Agregar una prueba automatizada** del módulo de tramos. La prueba actual se
   hizo con Node sobre los datos reales; conviene convertirla en test del
   proyecto.
4. **Opcional:** añadir `path_updated_at` a `routes` para saber cuándo se generó
   el trazado y evitar regeneraciones innecesarias.
5. **Para producción:** alojar una instancia propia de OSRM en lugar de depender
   del servidor público de demostración.
