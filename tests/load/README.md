# Pruebas de carga y rendimiento — Iteración 2 (Ubicaditos)

Documentación exclusiva de las pruebas de carga y rendimiento de la Iteración 2
("Mapa y Rastreo en Tiempo Real"). No contiene información de otras áreas del proyecto.

---

## 1. Instalación de k6 (Windows)

k6 **no estaba instalado** en el equipo. Se instaló con WinGet desde la fuente oficial
de Grafana (paquete `GrafanaLabs.k6`):

```powershell
# WinGet no estaba en el PATH; se invocó por su ruta de aplicación:
& "$env:LOCALAPPDATA\Microsoft\WindowsApps\winget.exe" search k6
& "$env:LOCALAPPDATA\Microsoft\WindowsApps\winget.exe" install --id GrafanaLabs.k6 --source winget --accept-package-agreements --accept-source-agreements --disable-interactivity

# Verificación (tras recargar el PATH de la sesión):
$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path','User')
k6 version
```

Versión instalada y verificada: **k6 v2.2.0** (commit `00a9a1b7f5`, go1.26.5, windows/amd64).

> Nota: cada sesión de PowerShell nueva necesita recargar el PATH con la línea
> `$env:Path = ...` (arriba) o invocar `k6.exe` por su ruta completa.

## 2. Requisitos

- Windows con PowerShell.
- k6 v2.2.0 (instalación arriba).
- PHP 8.2.12 y el servidor de desarrollo local activo en `http://127.0.0.1:8000`
  (`php artisan serve` / `php -S 127.0.0.1:8000`). **Únicamente entorno local; nunca producción.**
- Base de datos SQLite del proyecto con los datos del seeder (55 autobuses activos,
  18 rutas, 50 paradas).
- Credenciales de un pasajero existente del seeder (entorno local de desarrollo).
  Se pasan por variables de entorno; no están incrustadas en resultados.

## 3. Configuración del script

Script: `tests/load/ubicaditos-load.js`

| Variable | Descripción | Por defecto |
|---|---|---|
| `K6_MODE` | `api` (endpoints JSON) o `web` (navegación del pasajero) | `api` |
| `K6_VUS` | usuarios virtuales | `10` |
| `K6_DURATION` | duración del escenario | `30s` |
| `K6_BASE_URL` | base del servidor local | `http://127.0.0.1:8000` |
| `K6_EMAIL` / `K6_PASSWORD` | credenciales del pasajero local | las del seeder |

### Endpoints evaluados (solo lectura)

| Modo | Endpoint | Método |
|---|---|---|
| api | `/api/buses/active` (70 % de las lecturas) | GET |
| api | `/api/buses/{id}/location` (20 %) | GET |
| api | `/api/routes/{id}/buses` (10 %) | GET |
| web | `/passenger` (50 %) | GET |
| web | `/passenger/buses` (30 %) | GET |
| web | `/passenger/routes` (20 %) | GET |

Los identificadores de autobús y ruta **no se inventan**: `setup()` los descubre del
sistema real (primer elemento de `/api/buses/active` y el atributo `route_id` de la
vista `/passenger/routes`) y aborta si no los encuentra.

### Autenticación y CSRF

Cada VU realiza su propio login real de Laravel/Breeze: `GET /login` (extrae el token
CSRF `_token` del formulario) y `POST /login` con `_token`, `email`, `password` y
`remember=0` (`application/x-www-form-urlencoded`, `redirects: 0`; éxito = HTTP 302
con `Location` distinto de `/login`). El login es imprescindible para medir los
endpoints autenticados y se contabiliza aparte (`kind:auth`).

**Decisión técnica verificada:** el script propaga la cookie de sesión
(`laravel-session`) de forma **explícita** en cada petición. Durante la preparación se
comprobó que la cookie jar automática de k6 v2.2.0 pierde la sesión cuando corren VUs
concurrentes (las mismas peticiones devolvieron 302 → `/login`), mientras que con la
cookie enviada manualmente todas las peticiones devolvieron 200/JSON. Se verificó en la
tabla `sessions` de la base de datos que el servidor conservaba correctamente ambas
sesiones: la incidencia era del cliente de prueba, no de la aplicación.

### Operaciones excluidas

Nunca se envió ninguna operación mutadora: no hay peticiones a
`driver/update-location`, `start-trip`, `end-trip`, `update-passengers`, `emergency`,
favoritos, calificaciones ni administración. Tampoco se ejecutó `buses:simulate`.
El único POST es `/login` (autenticación).

## 4. Comandos (PowerShell, desde la raíz del proyecto)

```powershell
$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path','User')

# Comprobación pequeña de autenticación y conectividad (smoke)
k6 run tests\load\ubicaditos-load.js -e K6_MODE=api -e K6_VUS=3 -e K6_DURATION=8s

# Escenarios de la iteración (results/ no sobrescribe: cada ejecución usa su marca de tiempo)
$ts = Get-Date -Format 'yyyyMMdd-HHmmss'
k6 run tests\load\ubicaditos-load.js -e K6_MODE=api -e K6_VUS=10 -e K6_DURATION=30s --summary-export="tests\load\results\$ts-api-vus10-summary.json" 2>&1 | Tee-Object -FilePath "tests\load\results\$ts-api-vus10-console.txt"
# Repetir con 25 y 50 VUs, y con K6_MODE=web
```

## 5. Escenarios ejecutados

Ejecutor `constant-vus` (VUs constantes durante la duración indicada) con pensamiento
(*think time*) de 0,5 s en modo `api` y 1 s en modo `web`. Umbrales declarados en el
script: `p95 < 800 ms`, tasa de errores `< 1 %`, checks `> 99 %`.

| Escenario | VUs | Duración planificada | Duración real (reloj) | Iteraciones completadas | Resultado |
|---|---|---|---|---|---|
| smoke api | 3 | 8 s | ~10 s | 10 | Completada (conectividad/auth) |
| api-vus10 | 10 | 30 s | 39,6 s | 48 | Completada |
| api-vus25 | 25 | 30 s | 48,6 s | 25 | Completada |
| api-vus50 | 50 | 30 s | 62,7 s | **0** | **Incompleta: 0 lecturas; solo logins** |
| api-vus50-90s | 50 | 90 s | 114,9 s | 106 | Completada (reintento de 50 VUs) |
| web-vus10 | 10 | 30 s | 40,0 s | 33 | Completada |
| web-vus25 | 25 | 60 s | 78,5 s | 70 | Completada |
| web-vus50 | 50 | 90 s | **~6 s (interrumpida)** | **0** | **Interrumpida por el operador: sin resumen** |

## 6. Métricas disponibles

Por escenario (en `*-summary.json` y `*-console.txt`):

- Usuarios virtuales y duración real.
- Cantidad de solicitudes (`http_reqs.count`) y solicitudes por segundo (`rate`).
- Tiempo de respuesta promedio, p95 y máximo (`http_req_duration`), en milisegundos.
- Tasa de errores HTTP (`http_req_failed`; 4xx/5xx o fallos de conexión).
- Comprobaciones HTTP y funcionales (`checks`: HTTP 200 + validación del contenido
  JSON/HTML de cada endpoint, y validación del login).
- Métricas propias: `custom_read_duration` (solo GET medidos, excluye login),
  `custom_logins` (sesiones creadas), `custom_auth_failures` (respuestas no autenticadas).

## 7. Resultados obtenidos (medidos)

Todas las cifras proceden de los archivos de `results/` (fuentes citadas en §9).
**Tiempos en milisegundos.**

### 7.1 Solicitudes y tiempos (todas las peticiones, incluido el login)

| Escenario | VUs | Solicitudes | Duración real | req/s | Promedio | p95 | Máximo | Errores | Checks |
|---|---|---|---|---|---|---|---|---|---|
| api-vus10 | 10 | 72 | 39,6 s | 1,98 | 4 161 | 7 783 | 8 720 | 0,00 % | 126/126 (100 %) |
| api-vus25 | 25 | 79 | 48,6 s | 1,74 | 11 847 | 20 838 | 21 765 | 0,00 % | 125/125 (100 %) |
| api-vus50 (30 s) | 50 | 100 | 62,7 s | 1,61 | 19 253 | 39 435 | 41 866 | 0,00 % | 142/142 (100 %) |
| api-vus50-90s | 50 | 210 | 114,9 s | 1,83 | 23 859 | 41 618 | 44 037 | 0,00 % | 362/362 (100 %) |
| web-vus10 | 10 | 57 | 40,0 s | 1,44 | 5 408 | 8 517 | 8 921 | 0,00 % | 96/96 (100 %) |
| web-vus25 | 25 | 124 | 78,5 s | 1,59 | 13 190 | 20 571 | 21 796 | 0,00 % | 215/215 (100 %) |
| web-vus50 | 50 | No disponible | No disponible | No disponible | No disponible | No disponible | No disponible | No disponible | No disponible |

### 7.2 Tiempo de respuesta de las lecturas (solo GET medidos, sin login)

| Escenario | VUs | Lecturas medidas | Promedio | p95 | Máximo | Umbral p95 < 800 ms |
|---|---|---|---|---|---|---|
| api-vus10 | 10 | 48 | 4 381 | 7 243 | 8 280 | No cumplido (✗) |
| api-vus25 | 25 | 25 | 16 252 | 20 850 | 21 330 | No cumplido (✗) |
| api-vus50 (30 s) | 50 | **0** | No disponible | No disponible | No disponible | No aplicable |
| api-vus50-90s | 50 | 106 | 27 092 | 41 230 | 43 600 | No cumplido (✗) |
| web-vus10 | 10 | 33 | 6 598 | 8 520 | 8 920 | No cumplido (✗) |
| web-vus25 | 25 | 70 | 15 768 | 20 560 | 21 420 | No cumplido (✗) |
| web-vus50 | 50 | No disponible | No disponible | No disponible | No disponible | No aplicable |

### 7.3 Autenticación

| Escenario | Logins completados | Fallos de autenticación |
|---|---|---|
| api-vus10 | 10/10 | 0 |
| api-vus25 | 25/25 | 0 |
| api-vus50 (30 s) | **46/50** | 0 |
| api-vus50-90s | 50/50 | 0 |
| web-vus10 | 10/10 | 0 |
| web-vus25 | 25/25 | 0 |
| web-vus50 | No disponible (interrumpida) | No disponible |

### 7.4 Casos incompletos o no ejecutados

- **api-vus50 (30 s):** ninguna iteración de lectura completó dentro de la ventana de
  30 s (las lecturas en cola tardaron más que la duración del escenario). Los registros
  solo contienen las peticiones de autenticación: 50 `GET /login` + 46 `POST /login`
  (46 logins completados, no 50) = 100 solicitudes, 142 checks de login. Las métricas de
  lectura de este escenario figuran como **No disponible**; no se interpretan como éxito.
  El umbral `p95 < 800 ms` aparece "cumplido" en ese archivo solo porque no hubo
  lecturas que medir; **no es un resultado válido**.
- **web-vus50:** la ejecución de 90 s fue **interrumpida por el operador** a los ~6 s
  (0 iteraciones completadas, 0 solicitudes medidas). Solo dejó el registro parcial de
  progreso (`*-web-vus50-console.txt`), **sin JSON de resumen**. No hay métricas.
  Queda pendiente de ejecución.
- **Umbrales de rendimiento:** el objetivo `p95 < 800 ms` **no se cumplió en ninguno de
  los cinco escenarios de lectura completados**. El objetivo `tasa de errores < 1 %` se
  cumplió en todos (0,00 %) y los checks funcionales fueron 100 % en todos.

## 8. Errores e incidencias detectados

1. **Pérdida de sesión con la cookie jar automática de k6 (incidencia del cliente de
   prueba, resuelta).** Con 2+ VUs concurrentes, las peticiones autenticadas devolvían
   302 → `/login`. Se descartó la causa server-side comprobando en la tabla `sessions`
   que ambas sesiones existían y estaban autenticadas. Se resolvió propagando la cookie
   `laravel-session` manualmente en cada petición (§3). Todas las ejecuciones de §7 usan
   esta versión del script.
2. **`php -S` atiende de forma secuencial.** Prueba directa: 5 peticiones `GET /login`
   lanzadas en paralelo se atendieron en serie (496 → 756 → 931 → 1 212 → 1 437 ms
   crecientes; total ≈ 4,8 s). El servidor de desarrollo no paraleliza peticiones.
3. **Ventana insuficiente a 50 VUs (api, 30 s).** El tiempo de cola superó la duración
   planificada (§7.4); se reintentó con 90 s para poder medir.
4. **`thresholds ... have been crossed`.** k6 devuelve código de salida distinto de cero
   cuando el umbral `p95 < 800 ms` se cruza. Es el comportamiento esperado del umbral,
   no un fallo de ejecución; los resultados se conservaron igualmente.
5. Sin excepciones nuevas en `storage/logs/laravel.log` durante ninguna ejecución
   (tamaño del log idéntico antes y después: 0 bytes nuevos). **No se observaron
   bloqueos ("database is locked") ni errores 5xx.**

## 9. Archivos de resultados

Directorio `tests/load/results/` (cada ejecución con su marca de tiempo; nada se
sobrescribió):

| Archivo | Escenario |
|---|---|
| `20261014-224553-api-vus10-summary.json` / `-console.txt` | api, 10 VUs, 30 s |
| `20261014-224653-api-vus25-summary.json` / `-console.txt` | api, 25 VUs, 30 s |
| `20261014-224757-api-vus50-summary.json` / `-console.txt` | api, 50 VUs, 30 s (incompleta) |
| `20261014-224932-api-vus50-90s-summary.json` / `-console.txt` | api, 50 VUs, 90 s |
| `20261014-225141-web-vus10-summary.json` / `-console.txt` | web, 10 VUs, 30 s |
| `20261014-225326-web-vus25-summary.json` / `-console.txt` | web, 25 VUs, 60 s |
| `20261014-225509-web-vus50-console.txt` | web, 50 VUs, 90 s (**interrumpida, sin JSON**) |

## 10. Limitaciones del entorno local (respaldadas por pruebas)

1. **Servidor de desarrollo single-thread.** `php -S` atiende las peticiones una a una
   (evidencia §8.2). Con 10 VUs la cola ya eleva el p95 por encima del objetivo. Este
   resultado **mide el servidor de desarrollo, no el comportamiento en producción**
   (php-fpm/Apache con múltiples procesos).
2. **SQLite y sesiones en base de datos.** `SESSION_DRIVER=database` sobre SQLite con
   `busy_timeout => null` (`config/database.php:40`). No se produjeron bloqueos durante
   estas pruebas (§8.5), pero la concurrencia de escritura de sesiones (50 logins) es un
   riesgo latente a mayor escala al no estar configurado el tiempo de espera.
3. **Datos y red locales.** Todo se ejecutó en la misma máquina (latencia de red ≈ 0) y
   con los datos del seeder. Los tiempos de red reales de una red corporativa no están
   representados.
4. **Duración y think time fijos.** Escenarios cortos (30–90 s) con un único patrón de
   pensamiento; no modelan picos diarios ni sesiones prolongadas.

## 11. Interpretación (separada de las mediciones)

Lo siguiente es análisis, no medición directa:

- El **throughput se estancó entre 1,44 y 1,98 req/s** en todos los escenarios, con VUs
  de 10 a 50. Que añadir VUs multiplique la latencia (p95 de ≈ 7,8 s a ≈ 41,6 s en api)
  sin aumentar las req/s es **un indicio de cuello de botella en el servidor** (capacity
  ceiling). Combinado con la prueba de paralelización (§8.2), la hipótesis más probable
  es el servidor de desarrollo de un solo hilo; **no se ha demostrado de forma definitiva**
  (no se perfiló PHP ni se comparó contra un servidor multi-proceso).
- No se observaron bloqueos de SQLite ni errores de sesión en el servidor (§8.5), por lo
  que **no hay evidencia** de que SQLite fuera el factor limitante en estas ejecuciones.
- Las comprobaciones funcionales (100 %) y la tasa de errores (0,00 %) indican que las
  respuestas fueron correctas **dentro de las cargas que llegaron a completarse**; con
  colas más largas que la duración del escenario (api-50/30 s) no se puede afirmar nada
  sobre el comportamiento de esas lecturas encoladas.

## 12. Recomendaciones futuras (sin modificar la aplicación)

1. **Repetir las pruebas contra un servidor multi-proceso** (por ejemplo
   `php artisan serve` con `PHP_CLI_SERVER_WORKERS`, o Apache/nginx + php-fpm en el mismo
   equipo) para separar el efecto del servidor de desarrollo del comportamiento real.
2. **Reejecutar los escenarios incompletos**: api-50 con la duración que sea necesaria y
   web-50 (quedó interrumpida), registrando duración y métricas completas.
3. **Aumentar la duración de los escenarios** (2–5 min) y usar un executor de tasa de
   llegada (*arrival-rate*) para modelar picos, además de `constant-vus`.
4. **Recolectar métricas del lado del servidor** durante la prueba (CPU/memoria de PHP,
   duración por endpoint con `k6` *tags*, y una ventana de observación del sistema) para
   confirmar o descartar el cuello de botella.
5. **Ejecutar la suite de pruebas automatizadas y la auditoría de seguridad** por separado
   (hay incidencias preexistentes conocidas en el entorno); estas pruebas de carga no las
   cubren.
6. **Registrar capturas de pantalla** de cada ejecución (encabezado de resultados de k6 en
   consola) como evidencia visual para el informe académico.

## 13. Script de la iteración 3 (búsqueda, tiempo real, estadísticas e incidencias)

Script: `tests/load/ubicaditos-iteration3-load.js`

La iteración 3 añade un segundo script de carga, **solo de lectura**, que sigue el mismo
patrón de autenticación verificado en la iteración 2 (login real con CSRF de formulario y
propagación explícita de la cookie de sesión; sin cookie jar automática).

### 13.1 Alcance

| Rol | Endpoints medidos (GET) |
|---|---|
| Pasajero | `/passenger/buses` base, `?q=oax`, `?status=active`, `?sort=occupancy`, `?route_id={id}` y `/api/buses/active` (polling en vivo del fallback de WebSocket) |
| Conductor | `/driver` (panel), `/driver/map`, `/driver/statistics` |
| Admin | `/admin`, `/admin/emergencies`, `/admin/emergencies?status=pending`, `/admin/buses`, `/admin/buses/{id}` (detalle) |

Excluido a propósito (nunca se envía): `driver/update-location`, `start-trip`,
`end-trip`, `update-passengers`, `emergency`, `admin/emergencies/{id}/resolve` y cualquier
otro POST/PUT/DELETE. Tampoco debe ejecutarse `buses:simulate` durante la prueba.

### 13.2 Configuración

| Variable | Descripción | Por defecto |
|---|---|---|
| `K6_VUS` | usuarios virtuales (se reparten por `__VU % 3` entre pasajero/conductor/admin) | `15` |
| `K6_DURATION` | duración del escenario | `30s` |
| `K6_BASE_URL` | base del servidor local | `http://127.0.0.1:8000` |
| `K6_ROLE` | fija todos los VUs a un rol (`passenger`, `driver` o `admin`) | vacío (reparto) |
| `K6_PASSENGER_EMAIL` / `K6_DRIVER_EMAIL` / `K6_ADMIN_EMAIL` | usuarios del seeder | `pasajero1@…` / `chofer1@…` / `admin@…` |
| `K6_PASSWORD` | contraseña común | `password` |

### 13.3 Ejecución

```bash
# suite completa (3 roles mezclados):
k6 run tests/load/ubicaditos-iteration3-load.js -e K6_VUS=15 -e K6_DURATION=30s

# un solo rol:
k6 run tests/load/ubicaditos-iteration3-load.js -e K6_ROLE=passenger -e K6_VUS=25
```

### 13.4 Umbrales y métricas

- `i3_read_duration` p(95) < 800 ms (heredado de la iteración 2).
- `http_req_failed{kind:read}` < 1 %; `checks` > 99 %.
- Métricas propias: `i3_logins`, `i3_auth_failures`, `i3_read_duration`.

El id del autobús del detalle se **descubre** del listado de administración en `setup()`
(nunca se inventan identificadores); el `route_id` se descubre de los chips de filtro de
la propia página del pasajero dentro del flujo.

### 13.5 Resultados

Sin ejecutar en la iteración 3: el entorno de desarrollo de esta rama no tiene k6
disponible. El script queda preparado para la rama de pruebas con el mismo procedimiento
documentado en las secciones 4–11 (servidor local, datos del seeder, resultados en
`tests/load/results/`).
