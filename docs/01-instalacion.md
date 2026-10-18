# Instalación y configuración

Esta sección describe cómo preparar un entorno local de Ubicaditos, las variables de entorno relevantes, los scripts disponibles y las consideraciones para producción.

## Requisitos

| Herramienta | Versión mínima | Uso |
|-------------|----------------|-----|
| PHP | 8.2 | Ejecución de Laravel 12 |
| Composer | 2.x | Dependencias de PHP |
| Node.js | 20.19+ (requerido por Vite 7) | Compilación de assets |
| Bun | 1.x | Gestor de paquetes JS. El repositorio incluye `bun.lock`; bun reemplaza a npm en todos los scripts. |
| SQLite | 3.x | Base de datos por defecto |

Extensiones de PHP necesarias: `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `dom` y `gd` (esta última la usa DomPDF para imágenes en los reportes).

## Comandos de instalación

### Instalación paso a paso

```bash
# Dependencias
composer install
bun install

# Archivo de entorno y clave de la aplicación
cp .env.example .env
php artisan key:generate

# Base de datos SQLite
touch database/database.sqlite
php artisan migrate

# Datos de prueba (rutas reales de Oaxaca, 54 autobuses, 700 usuarios, viajes y calificaciones)
php artisan db:seed

# Compilar assets para producción (o usar "bun run dev" en desarrollo)
bun run build
```

### Instalación automática

El `composer.json` define un script que ejecuta la instalación completa (sin seeders):

```bash
composer run setup
```

Equivale a: `composer install` → copiar `.env` → `key:generate` → `migrate --force` → `bun install` → `bun run build`.

### Reiniciar la base de datos

```bash
php artisan migrate:fresh --seed
```

Borra todas las tablas, vuelve a ejecutar las migraciones y repuebla con `DatabaseSeeder`.

## Ejecución en desarrollo

```bash
composer run dev
```

Lanza en paralelo, mediante `concurrently`, tres procesos:

| Proceso  | Comando                              | Descripción                                  |
| -------- | ------------------------------------ | -------------------------------------------- |
| `server` | `php artisan serve`                  | Servidor HTTP en `http://localhost:8000`     |
| `queue`  | `php artisan queue:listen --tries=1` | Procesa trabajos en cola (driver `database`) |
| `vite`   | `bun run dev`                        | Servidor de Vite con recarga en caliente     |

Si prefieres levantarlos por separado:

```bash
php artisan serve
bun run dev
```

### Tiempo real (WebSockets con Reverb)

La comunicación en tiempo real usa **Laravel Reverb**. Para que los mapas reciban posiciones por WebSocket en desarrollo hay que levantarlo en una terminal adicional:

```bash
php artisan reverb:start
```

Si Reverb no está corriendo, el frontend detecta que el WebSocket no conecta y usa automáticamente un **fallback de sondeo** a `GET /api/buses/active` cada 5 segundos, por lo que la aplicación sigue mostrando posiciones actualizadas sin configuración extra.

### Simular movimiento de autobuses

Para ver autobuses moviéndose en el mapa sin conductores reales:

```bash
php artisan buses:simulate --interval=5
```

### Generar trazos de rutas sobre calles reales

```bash
php artisan routes:generate-paths
```

Consulta el servicio público OSRM y guarda la geometría de cada ruta en `routes.path_coordinates`. Ver [Comandos de consola](09-comandos.md).

## Scripts disponibles

| Script | Comando | Descripción |
|--------|---------|-------------|
| Composer | `composer run setup` | Instalación completa del proyecto |
| Composer | `composer run dev` | Servidor, cola y Vite en paralelo |
| Composer | `composer run test` | Limpia la caché de configuración y ejecuta las pruebas |
| bun | `bun run dev` | Servidor de desarrollo de Vite |
| bun | `bun run build` | Compila y minifica assets en `public/build` |

## Variables de entorno

El archivo `.env.example` contiene la configuración base de Laravel. Las variables más relevantes para este proyecto son:

| Variable | Valor recomendado | Descripción |
|----------|-------------------|-------------|
| `APP_NAME` | `Ubicaditos` | Nombre mostrado en el título de las páginas (los layouts usan `Ubicaditos` como respaldo). |
| `APP_ENV` | `local` / `production` | Entorno de ejecución. |
| `APP_DEBUG` | `true` en local, `false` en producción | Muestra trazas de error detalladas. |
| `APP_URL` | `http://localhost:8000` | URL base de la aplicación. |
| `APP_LOCALE` | `es` | Idioma. Existe `lang/es.json` con traducciones de las vistas de perfil. |
| `APP_FAKER_LOCALE` | `es_MX` | Idioma de los datos generados por Faker. |
| `DB_CONNECTION` | `sqlite` | Motor de base de datos en desarrollo. En producción y staging se usa PostgreSQL (ver abajo). |
| `SESSION_DRIVER` | `database` | Las sesiones se guardan en la tabla `sessions`. |
| `QUEUE_CONNECTION` | `database` | Las colas usan la tabla `jobs`. |
| `CACHE_STORE` | `database` | La caché usa la tabla `cache`. |
| `MAIL_MAILER` | `log` | Los correos (recuperación de contraseña, verificación) se escriben en `storage/logs/laravel.log`. |
| `BROADCAST_CONNECTION` | `reverb` | Driver de broadcasting para el tiempo real (iteración 3). En los entornos de prueba (`phpunit.xml`) se fija a `null`. |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | credenciales propias | Credenciales de la aplicación Reverb (copiadas al crearla con `php artisan reverb:app`). |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `127.0.0.1` / `8080` / `http` | Endpoint del servidor Reverb para el backend. En producción: dominio con TLS y puerto 443. |
| `VITE_REVERB_APP_KEY` / `VITE_REVERB_HOST` / `VITE_REVERB_PORT` / `VITE_REVERB_WSS_PORT` / `VITE_REVERB_SCHEME` | igual que las anteriores | Expuestas al navegador por Vite; las usa `resources/js/realtime.js` para conectar Echo con Reverb. |

### PostgreSQL en producción y staging (no en desarrollo)

**Política de la iteración 1:** en desarrollo (rama `develop`, `APP_ENV=local`) siempre se usa **SQLite**; las ramas `main` (producción) y `staging` usan **PostgreSQL**. El repositorio incluye archivos de ejemplo listos para copiar a `.env` en esos ambientes:

| Ambiente | Rama | Archivo a copiar a `.env` | Motor |
|----------|------|---------------------------|-------|
| Producción | `main` | `.env.production.example` | PostgreSQL |
| Staging | `staging` | `.env.staging.example` | PostgreSQL |
| Desarrollo | `develop` | `.env.example` | SQLite |

El bloque relevante de `.env.production.example` / `.env.staging.example`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ubicaditos
DB_USERNAME=ubicaditos
DB_PASSWORD=secret
```

El conector `pgsql` ya viene registrado en `config/database.php` con el puerto estándar 5432, y el entorno actual dispone de la extensión `pdo_pgsql`. `.env.example` conserva `DB_CONNECTION=sqlite` y solo documenta el bloque de PostgreSQL como referencia comentada.

### Tiempo real con Laravel Reverb (iteración 3)

`.env.example`, `.env.production.example` y `.env.staging.example` incluyen el bloque de Reverb (con comentarios y valores de ejemplo). Tras crear la aplicación con `php artisan reverb:app`, el bloque en desarrollo es:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=xxxxxx
REVERB_APP_KEY=xxxxxxxxxxxxxxxx
REVERB_APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_WSS_PORT=443
VITE_REVERB_SCHEME=http
```

En producción/staging el host debe ser el dominio con TLS y el scheme `wss`/`https` (puerto 443).

> **Nota:** el reporte de "ocupación promedio por hora" en `ReportController@index` usa la función `strftime()`, exclusiva de SQLite. Al migrar a PostgreSQL debe sustituirse por `EXTRACT(HOUR FROM started_at)` en la consulta nativa. Ver [Pruebas y estado del proyecto](10-pruebas-y-estado.md).

## Servicios externos

La aplicación no requiere claves de API. Los servicios externos se consumen directamente desde el navegador (y desde un comando Artisan):

| Servicio | URL | Uso |
|----------|-----|-----|
| OpenStreetMap | `https://{a,b,c}.tile.openstreetmap.org` | Teselas del mapa |
| OSRM (demo pública) | `https://router.project-osrm.org` | Trazado de rutas siguiendo calles |
| unpkg | `https://unpkg.com/leaflet@1.9.4` | Librería Leaflet |
| jsDelivr | `https://cdn.jsdelivr.net/npm/chart.js` | Librería Chart.js |
| Bunny Fonts | `https://fonts.bunny.net` | Tipografía Figtree |

Se necesita conexión a internet para que los mapas y las gráficas funcionen.

La geolocalización del navegador (`navigator.geolocation`) solo funciona en **contextos seguros** (HTTPS o `localhost`). Si el usuario niega el permiso o el navegador no lo soporta, las vistas de mapa usan una ubicación simulada en el centro de Oaxaca.

## Despliegue en producción

```bash
composer install --no-dev --optimize-autoloader
bun install && bun run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Recomendaciones:

- Copiar `.env.production.example` a `.env` y establecer `APP_ENV=production`, `APP_DEBUG=false` y las credenciales reales de PostgreSQL.
- Servir la aplicación desde el directorio `public/` (incluye `.htaccess` para Apache).
- Asegurar permisos de escritura para el servidor web en `storage/` y `bootstrap/cache/`.
- Usar HTTPS, indispensable para la geolocalización en dispositivos de conductores y pasajeros.
- Mantener un worker de cola activo (`php artisan queue:work`) mediante Supervisor o systemd.
- Configurar un proveedor de correo real (`MAIL_MAILER=smtp`) para la recuperación de contraseñas.
- Para tráfico real, considerar una instancia propia de OSRM: el servidor de demostración tiene límites de uso.
- Si se usa Laravel Sail (incluido como dependencia de desarrollo), puede generarse el entorno Docker con `php artisan sail:install`.  