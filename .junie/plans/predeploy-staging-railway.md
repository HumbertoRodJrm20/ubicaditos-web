---
sessionId: session-261126-004254-zk3z
---

# Runbook previo al deploy

### Objetivo
Validar la rama de staging y probar localmente la misma imagen Docker que se desplegará, conectada a PostgreSQL, antes de activar un deploy en Railway.

### Comandos locales
Desde la raíz del proyecto, en Bash:

```bash
cd /home/mizu/dev/ubicaditos-web

git status --short
git diff --check
php artisan test
./vendor/bin/pint --test bootstrap/app.php tests/Feature/RailwayConfigurationTest.php
docker info
docker compose version

export APP_KEY="$(php artisan key:generate --show)"
export STAGING_DB_PASSWORD="$(openssl rand -hex 32)"
test -n "$APP_KEY" && test -n "$STAGING_DB_PASSWORD" && echo "Variables locales listas"

docker compose -f compose.staging.yaml config --quiet
docker compose -f compose.staging.yaml build --pull app
docker compose -f compose.staging.yaml up -d
docker compose -f compose.staging.yaml ps
docker compose -f compose.staging.yaml logs --tail=100 app

docker compose -f compose.staging.yaml exec -T app php artisan db:show
docker compose -f compose.staging.yaml exec -T app php artisan migrate:status
curl -fsS -o /dev/null -w 'HTTP %{http_code}\n' http://localhost:8080/up
```

Resultado esperado: tests y Pint dirigido a los archivos PHP modificados pasan, Compose valida, el servicio PostgreSQL queda saludable, Laravel conecta y muestra migraciones aplicadas, y `/up` responde HTTP 200. La comprobación global de Pint reportó 32 hallazgos de estilo en otros archivos; no aplicar formato masivo como parte de este deploy. No ejecutar `db:seed`: el seeder no es idempotente. No usar `docker compose down -v`, porque borraría el volumen `staging_pgdata`.

### Configuración de Railway
- Crear/vincular un servicio PostgreSQL y el servicio web del repositorio; usar el `Dockerfile` raíz y no reemplazar el comando de inicio del contenedor.
- Configurar en el servicio web `APP_ENV=staging`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, `DB_URL=${{Postgres.DATABASE_URL}}`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync` y `BROADCAST_CONNECTION=log`. Reemplazar `Postgres` por el nombre real del servicio; guardar `APP_KEY` y la URL de base como variables secretas/referenciadas, nunca en el repositorio.
- Dejar que Railway inyecte `PORT`; configurar el health check como `/up`.
- Solo después de validar los checks y revisar `git status`, subir la rama de staging conectada a Railway, por ejemplo `git push origin <rama-staging>`.

### Estado actual de los assets y del proxy
El último deploy reportado terminó `SUCCESS` el 26 de noviembre de 2026 a las 16:45, pero `/login` aún publica URLs HTTP para CSS y JS; pedir `/up` o ver `SUCCESS` no demuestra que los assets usen HTTPS. La corrección local está en `bootstrap/app.php` (`trustProxies(at: '*')`) y `docker/nginx/ubicaditos-railway.conf` (`HTTP_X_FORWARDED_PROTO` hacia PHP-FPM); requiere construir y desplegar una imagen nueva, no agregar variables a Railway. Mantener `APP_URL=https://ubicaditos-staging.up.railway.app`, PostgreSQL y el health check `/up`.

### Referencias
- [Docker Docs: `docker compose config`](https://docs.docker.com/reference/cli/docker/compose/config/)
- [Docker Docs: `docker compose build`](https://docs.docker.com/reference/cli/docker/compose/build/)
- [Railway Docs: PostgreSQL](https://docs.railway.com/databases/postgresql)
- [Railway Docs: Healthchecks](https://docs.railway.com/deployments/healthchecks)

La ejecución reciente de Docker sin `sudo` permitió construir la imagen Railway. Si el acceso al daemon deja de estar disponible, detener el preflight y resolverlo antes de validar el build.

# Criterios de listo

### Antes de iniciar el deploy
- `php artisan test`, Pint dirigido a `bootstrap/app.php` y `tests/Feature/RailwayConfigurationTest.php`, `docker compose ... config --quiet` y el build de la etapa `railway` terminan correctamente; la comprobación global de Pint mantiene 32 hallazgos previos en otros archivos.
- El stack local conecta a PostgreSQL; el entrypoint completa `php artisan migrate --force`; `db:show` y `migrate:status` funcionan.
- El endpoint HTTP `http://localhost:8080/up` responde 200.
- En Railway, la referencia a la base apunta al servicio correcto, `DB_SSLMODE=require`, `APP_DEBUG=false`, `APP_KEY` y `APP_URL` están configurados, y el health check apunta a `/up`.
- Si alguna comprobación falla, detener el deploy y usar los logs de `docker compose` o Railway para resolverla; no borrar volúmenes ni sembrar datos como método de diagnóstico.

# Delivery Steps

### * Step 1: Validar el cambio de confianza del proxy en la imagen Railway
Los cambios locales de proxy pasan pruebas y se compilan en la etapa `railway` antes de publicar.

- Confirmar `git status --short`, revisar el diff de `bootstrap/app.php`, `docker/nginx/ubicaditos-railway.conf` y `tests/Feature/RailwayConfigurationTest.php`, y ejecutar `git diff --check`.
- Ejecutar `php artisan test` y `./vendor/bin/pint --test bootstrap/app.php tests/Feature/RailwayConfigurationTest.php`; no aplicar formato automático a archivos ajenos al cambio.
- Construir con `docker build --target railway -t ubicaditos:staging .` y detenerse ante cualquier error.

### * Step 2: Publicar una nueva imagen en el entorno staging
El servicio `ubicaditos` recibe una imagen construida con la confianza del proxy y el reenvío del esquema HTTPS.

- Preferir Git: confirmar la rama conectada a staging, ejecutar `git add bootstrap/app.php docker/nginx/ubicaditos-railway.conf tests/Feature/RailwayConfigurationTest.php`, revisar con `git diff --cached --check`, crear el commit `Fix HTTPS assets behind Railway proxy` y hacer `git push origin <rama-configurada-para-staging>`.
- Como alternativa, subir el directorio con `railway up --project 684a2df5-c5ee-46f6-bc74-6f653ff23ada --environment staging --service 0538a10c-c995-4db7-b238-072ea518f88d --ci -m "Fix HTTPS assets behind Railway proxy"`; no combinarlo con el push para evitar despliegues duplicados.
- Conservar el builder `DOCKERFILE`, `/Dockerfile`, el entrypoint, `APP_URL` HTTPS, la conexión PostgreSQL y el health check `/up`; no hacer solo redeploy de la imagen vieja.

### * Step 3: Confirmar el resultado del deploy y los assets públicos
El despliegue nuevo queda confirmado únicamente cuando Railway lo marca `SUCCESS` y `/login` genera URLs HTTPS cuyos assets responden 200.

- Comprobar el estado del despliegue específico y revisar los logs de inicio, migraciones y PHP-FPM.
- Verificar que `https://ubicaditos-staging.up.railway.app/up` responde HTTP 200.
- Inspeccionar las URLs de CSS y JS en `/login`; confirmar que empiezan con `https://` y que ambos recursos responden HTTP 200 con su tipo de contenido.
- Si el HTML aún genera `http://`, confirmar que el build nuevo contiene ambos cambios del proxy y que se está inspeccionando el servicio, entorno y dominio correctos.