# Staging en Railway

La configuración de staging usa PostgreSQL y mantiene el entorno de desarrollo y pruebas en SQLite. El servicio web parte de la etapa `railway` del `Dockerfile`; el entrypoint aplica las migraciones con `--force` antes de iniciar Nginx y PHP-FPM.

## Prueba local de la imagen

Genera una clave local sin modificar `.env`, establece una contraseña de prueba y levanta el stack:

```bash
export APP_KEY="$(php artisan key:generate --show)"
export STAGING_DB_PASSWORD='una-clave-local-de-prueba'
docker compose -f compose.staging.yaml up --build
```

La app queda disponible en `http://localhost:8080` y PostgreSQL sólo es accesible dentro de la red de Compose. El volumen `staging_pgdata` persiste entre reinicios. No uses `docker compose down -v` salvo que quieras borrar expresamente los datos locales de staging.

## Despliegue en Railway

1. Crea un servicio PostgreSQL y un servicio de aplicación desde el repositorio. Railway detecta el `Dockerfile` de la raíz; no configures un comando de inicio que reemplace `ubicaditos-railway-entrypoint`.
2. En las variables del servicio web define `APP_ENV=staging`, `APP_DEBUG=false`, `APP_KEY` (una clave generada con `php artisan key:generate --show`), `APP_URL=https://<dominio>.up.railway.app`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync` y `BROADCAST_CONNECTION=log`.
3. Conecta el PostgreSQL de Railway con la referencia `DB_URL=${{Postgres.DATABASE_URL}}` (ajusta `Postgres` si el servicio tiene otro nombre). Laravel acepta `DB_URL`; Railway muestra esta referencia en su [guía de Laravel](https://railway.com/guides/laravel).
4. Configura el health check HTTP del servicio como `/healthz`. Railway proporciona `PORT`; la imagen escucha ese puerto y no requiere fijar uno en el panel.

La configuración predeterminada usa dos servicios (web y PostgreSQL), tareas síncronas y el polling existente para actualizaciones de ubicación. No se necesita un worker ni un servicio Reverb adicionales. Para habilitar WebSockets, agrega Reverb como tercer servicio y configura las variables `REVERB_*` en runtime y `VITE_REVERB_*` como build variables; esos valores Vite quedan embebidos al compilar los assets.

Railway Free ofrece actualmente hasta tres servicios por proyecto (máximo 1 vCPU y 0.5 GB RAM por servicio), un volumen de 0.5 GB y USD 1 de crédito mensual; los servicios duermen tras 5–10 minutos sin tráfico saliente. El crédito puede agotarse antes de fin de mes y los archivos locales de la app no son persistentes; usa almacenamiento de objetos si staging debe conservar archivos subidos. Revisa los [límites vigentes del plan](https://docs.railway.com/pricing/free-plan). Railway documenta la detección del `Dockerfile` y las variables de build en [Dockerfiles](https://docs.railway.com/builds/dockerfiles).