# Despliegue de staging en Railway

Esta guía describe la configuración preparada en la rama `staging` para desplegar la aplicación con **PostgreSQL administrado por Railway**. No se creó el proyecto, no se conectó a una base real ni se ejecutó un despliegue en esta sesión: los pasos siguientes son instrucciones para el propietario.

## Cambios incluidos en el repositorio

| Archivo | Cambio |
|---------|--------|
| `config/database.php` | La conexión `pgsql` usa `DB_URL` y, si no existe, el `DATABASE_URL` que inyecta Railway. `DB_SSLMODE` y `DB_SCHEMA` son configurables; el default de SSL sigue siendo `prefer` para no romper instalaciones locales. |
| `.env.staging.example` | Plantilla sin secretos: `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, comentario con el formato de `DATABASE_URL` y `EMAIL_VERIFICATION_ENABLED=false`. |
| `Dockerfile` | Añade el stage final `railway`. Railway construye ese stage de forma automática porque es el último del `Dockerfile` de la raíz; no se usa `railway.json` (Config as Code está en deprecación). |
| `docker/nginx/ubicaditos-railway.conf` | Plantilla de Nginx que escucha en `${PORT}` y permite apuntar `/app` a `${REVERB_UPSTREAM}`. |
| `docker/entrypoint-railway.sh` | Sustituye los marcadores por las variables del entorno, ejecuta `php artisan migrate --force`, inicia PHP-FPM y deja Nginx en primer plano. |

La rama `develop` mantiene SQLite para desarrollo y pruebas; sólo `staging` usa PostgreSQL.

## Creación del servicio en Railway

1. Crear un proyecto y añadir un servicio **Webhook/Repo** apuntando a este repositorio, rama `staging`.
2. Añadir el servicio **PostgreSQL** dentro del mismo proyecto y usar **Connect** desde el servicio web para que Railway inyecte `DATABASE_URL`.
3. Configurar las variables de entorno del servicio web. No copiar valores reales al repositorio:

   | Variable | Valor de referencia |
   |----------|---------------------|
   | `APP_NAME` | `Ubicaditos` |
   | `APP_ENV` | `staging` |
   | `APP_KEY` | Generar con `php artisan key:generate` y guardarlo sólo en Railway |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | URL pública asignada por Railway |
   | `DB_CONNECTION` | `pgsql` |
   | `DATABASE_URL` | La inyecta Railway al conectar Postgres; no escribirla a mano |
   | `DB_SSLMODE` | `require` |
   | `DB_SCHEMA` | `public` |
   | `SESSION_DRIVER` | `database` |
   | `CACHE_STORE` | `database` |
   | `QUEUE_CONNECTION` | `database` |
   | `EMAIL_VERIFICATION_ENABLED` | `false` |
   | `MAIL_*` | Valores del proveedor de correo de staging |
   | `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Configuración del servicio WebSocket; generar secretos con `php artisan reverb:generate` |
   | `VITE_REVERB_*` | Los mismos valores públicos que `REVERB_*` |
   | `PORT` | Lo inyecta Railway; no definirlo manualmente |
   | `REVERB_UPSTREAM` | Opcional; host:puerto interno del servicio Reverb si se despliega por separado (default `reverb:8080`) |

4. Configurar el healthcheck con la ruta `/up` (respuesta de Laravel cuando la aplicación está levantada). El contenedor también expone `/healthz` en Nginx.
5. Desplegar. Durante el arranque se aplican las migraciones pendientes con `php artisan migrate --force`.

Si Reverb se despliega como servicio separado, crearlo desde el mismo `Dockerfile` con el comando `php artisan reverb:start --host=0.0.0.0 --port=8080` y ajustar `REVERB_UPSTREAM`. Si no está disponible, el navegador usa el polling de `/api/buses/active` documentado en los archivos de entorno.

## Verificación pendiente en Railway

Una vez desplegado, el propietario debe ejecutar y registrar:

```bash
railway run php artisan migrate:status
railway run php artisan db:show
railway run php artisan about
```

También conviene comprobar que `/up` devuelve 200, que el registro de un pasajero con correo ficticio no envía verificación y que las rutas protegidas responden con la sesión del usuario. **Ninguna de estas comprobaciones se ejecutó en esta sesión.**

## Población de datos

Las migraciones crean el esquema vacío. Para generar el escenario de Oaxaca en staging, ejecutar de forma explícita y motivada:

```bash
railway run php artisan db:seed --class=DatabaseSeeder --force
```

No se hizo en esta sesión. Tampoco se exportó ni importó la base SQLite de desarrollo: los seeders son la vía prevista para staging.
