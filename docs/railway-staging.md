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
4. Configura el health check HTTP del servicio como `/up`, ruta registrada por Laravel en `bootstrap/app.php`. Railway proporciona `PORT`; la imagen escucha ese puerto y no requiere fijarlo en el panel.

## Resultado de la configuración en Railway

Comprobación de staging realizada el 26 de noviembre de 2026; no incluye secretos:

- Los servicios `ubicaditos` y `Postgres` aparecen `SUCCESS`. El servicio web usa el builder `DOCKERFILE` y `/Dockerfile`; no tiene un comando de inicio personalizado, por lo que conserva el entrypoint que aplica migraciones. Railway inyecta `PORT`.
- En la app están configurados `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://ubicaditos-staging.up.railway.app`, `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync` y `BROADCAST_CONNECTION=log`. `APP_KEY` está presente; no copiar su valor a esta guía.
- `DB_URL` conecta al `DATABASE_URL` del servicio `Postgres` mediante una referencia privada de Railway. No publicar ni copiar su valor.
- El health check está en `/up`. El despliegue `6c3b4c04-6a84-4916-9b91-004f3c764fd7` quedó `SUCCESS`; sus logs muestran migraciones completadas y PHP-FPM listo. `https://ubicaditos-staging.up.railway.app/up` respondió HTTP 200.
- Al inicio, Laravel intentó conectar a `127.0.0.1:5432` y PostgreSQL respondió `Connection refused`: esa dirección era el contenedor web, no el servicio Postgres. La referencia `DB_URL` corrigió la conexión; la base local del equipo no debe usarse como host de Railway.
- Los avisos PHP-FPM `user`/`group` ignorados son esperados porque el proceso corre como `www-data`, sin privilegios root; no indican una caída del servicio.

## Problema de assets estáticos

En la página `/login` de Railway, Laravel generaba URLs `http://.../build/assets/...` aunque `APP_URL` fuera HTTPS; el HTML publicado todavía muestra esas URLs en el último despliegue. `GET /` también redirigía a `http://.../login`. La petición HTTP de un asset era redirigida por Railway, mientras que la petición directa del CSS por HTTPS respondía HTTP 200. El origen era que el proxy termina TLS antes de llegar al contenedor y la configuración FastCGI no pasaba `X-Forwarded-Proto` a Laravel; `/up` por sí solo no detecta este problema.

La corrección local:

- `bootstrap/app.php` configura Laravel para confiar en el proxy y reconocer el esquema original.
- `docker/nginx/ubicaditos-railway.conf` reenvía `X-Forwarded-Proto` al proceso PHP-FPM.
- El build `railway` terminó correctamente, generó y copió los assets al runtime; el stack local confirmó que `/login` genera enlaces HTTPS y Nginx sirvió CSS y JS con HTTP 200.
- La build mostró avisos no bloqueantes: la base de datos de Browserslist tiene 11 meses y Docker señaló `VITE_REVERB_APP_KEY` por declararse como `ARG`/`ENV`. Esa variable Vite es pública para el navegador; nunca pases secretos como `REVERB_APP_SECRET` por variables de build.
- Las pruebas completas pasaron (375 pruebas y 1,612 aserciones), así como Pint sobre los archivos PHP modificados. La comprobación global de Pint aún falla por 32 problemas de estilo existentes en otros archivos.

**Esta corrección todavía no está desplegada en Railway.** El último despliegue `SUCCESS` es anterior al cambio de proxy; se confirmó que es `6c3b4c04-6a84-4916-9b91-004f3c764fd7`, no se hizo push ni se lanzó otro despliegue. Antes de publicar, resuelve los 32 problemas globales de Pint de forma revisada y repite el preflight; luego confirma que el HTML servido por `/login` contiene URLs `https://` y que las peticiones de ambos assets responden HTTP 200.

La configuración predeterminada usa dos servicios (web y PostgreSQL), tareas síncronas y el polling existente para actualizaciones de ubicación. No se necesita un worker ni un servicio Reverb adicionales. Para habilitar WebSockets, agrega Reverb como tercer servicio y configura las variables `REVERB_*` en runtime y `VITE_REVERB_*` como build variables; esos valores Vite quedan embebidos al compilar los assets.

Railway Free ofrece actualmente hasta tres servicios por proyecto (máximo 1 vCPU y 0.5 GB RAM por servicio), un volumen de 0.5 GB y USD 1 de crédito mensual; los servicios duermen tras 5–10 minutos sin tráfico saliente. El crédito puede agotarse antes de fin de mes y los archivos locales de la app no son persistentes; usa almacenamiento de objetos si staging debe conservar archivos subidos. Revisa los [límites vigentes del plan](https://docs.railway.com/pricing/free-plan). Railway documenta la detección del `Dockerfile` y las variables de build en [Dockerfiles](https://docs.railway.com/builds/dockerfiles).