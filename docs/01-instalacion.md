# Instalación y configuración

Esta sección describe cómo preparar un entorno local de Ubicaditos, las variables de entorno relevantes, los scripts disponibles y las consideraciones para producción.

## Requisitos

| Herramienta | Versión mínima | Uso |
|-------------|----------------|-----|
| PHP | 8.2 | Ejecución de Laravel 12 |
| Composer | 2.x | Dependencias de PHP |
| Node.js | 20.19+ (requerido por Vite 7) | Compilación de assets |
| npm o Bun | npm 10+ / Bun 1.x | Gestor de paquetes JS (el repositorio incluye `bun.lock`) |
| SQLite | 3.x | Base de datos por defecto |

Extensiones de PHP necesarias: `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `dom` y `gd` (esta última la usa DomPDF para imágenes en los reportes).

## Comandos de instalación

### Instalación paso a paso

```bash
# Dependencias
composer install
npm install                 # alternativa: bun install

# Archivo de entorno y clave de la aplicación
cp .env.example .env
php artisan key:generate

# Base de datos SQLite
touch database/database.sqlite
php artisan migrate

# Datos de prueba (rutas reales de Oaxaca, 54 autobuses, 700 usuarios, viajes y calificaciones)
php artisan db:seed

# Compilar assets para producción (o usar "npm run dev" en desarrollo)
bun run build
```

### Instalación automática

El `composer.json` define un script que ejecuta la instalación completa (sin seeders):

```bash
composer run setup
```

Equivale a: `composer install` → copiar `.env` → `key:generate` → `migrate --force` → `npm install` → `npm run build`.

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
| `vite`   | `npm run dev`                        | Servidor de Vite con recarga en caliente     |

Si prefieres levantarlos por separado:

```bash
php artisan serve
npm run dev
```

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
| npm | `npm run dev` | Servidor de desarrollo de Vite |
| npm | `npm run build` | Compila y minifica assets en `public/build` |

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
| `DB_CONNECTION` | `sqlite` | Motor de base de datos. Ver nota sobre MySQL más abajo. |
| `SESSION_DRIVER` | `database` | Las sesiones se guardan en la tabla `sessions`. |
| `QUEUE_CONNECTION` | `database` | Las colas usan la tabla `jobs`. |
| `CACHE_STORE` | `database` | La caché usa la tabla `cache`. |
| `MAIL_MAILER` | `log` | Los correos (recuperación de contraseña, verificación) se escriben en `storage/logs/laravel.log`. |

### Usar MySQL o MariaDB

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ubicaditos
DB_USERNAME=root
DB_PASSWORD=secret
```

> **Importante:** el reporte de "ocupación promedio por hora" en `ReportController@index` usa la función `strftime()`, exclusiva de SQLite. Al migrar a MySQL debe sustituirse por `HOUR(started_at)`. Ver [Pruebas y estado del proyecto](10-pruebas-y-estado.md).

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
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Recomendaciones:

- Establecer `APP_ENV=production` y `APP_DEBUG=false`.
- Servir la aplicación desde el directorio `public/` (incluye `.htaccess` para Apache).
- Asegurar permisos de escritura para el servidor web en `storage/` y `bootstrap/cache/`.
- Usar HTTPS, indispensable para la geolocalización en dispositivos de conductores y pasajeros.
- Mantener un worker de cola activo (`php artisan queue:work`) mediante Supervisor o systemd.
- Configurar un proveedor de correo real (`MAIL_MAILER=smtp`) para la recuperación de contraseñas.
- Para tráfico real, considerar una instancia propia de OSRM: el servidor de demostración tiene límites de uso.
- Si se usa Laravel Sail (incluido como dependencia de desarrollo), puede generarse el entorno Docker con `php artisan sail:install`.  