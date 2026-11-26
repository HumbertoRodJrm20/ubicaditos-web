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
./vendor/bin/pint --test
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

Resultado esperado: tests y Pint pasan, Compose valida, el servicio PostgreSQL queda saludable, Laravel conecta y muestra migraciones aplicadas, y `/up` responde HTTP 200. No ejecutar `db:seed`: el seeder no es idempotente. No usar `docker compose down -v`, porque borraría el volumen `staging_pgdata`.

### Configuración de Railway
- Crear/vincular un servicio PostgreSQL y el servicio web del repositorio; usar el `Dockerfile` raíz y no reemplazar el comando de inicio del contenedor.
- Configurar en el servicio web `APP_ENV=staging`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, `DB_URL=${{Postgres.DATABASE_URL}}`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync` y `BROADCAST_CONNECTION=log`. Reemplazar `Postgres` por el nombre real del servicio; guardar `APP_KEY` y la URL de base como variables secretas/referenciadas, nunca en el repositorio.
- Dejar que Railway inyecte `PORT`; configurar el health check como `/up`.
- Solo después de validar los checks y revisar `git status`, subir la rama de staging conectada a Railway, por ejemplo `git push origin <rama-staging>`.

### Discrepancia confirmada
`bootstrap/app.php` registra la ruta de salud `/up`, pero `docs/railway-staging.md` todavía dice `/healthz`. Usar `/up` en Railway; corregir la guía en un cambio separado si se desea mantenerla alineada.

### Referencias
- [Docker Docs: `docker compose config`](https://docs.docker.com/reference/cli/docker/compose/config/)
- [Docker Docs: `docker compose build`](https://docs.docker.com/reference/cli/docker/compose/build/)
- [Railway Docs: PostgreSQL](https://docs.railway.com/databases/postgresql)
- [Railway Docs: Healthchecks](https://docs.railway.com/deployments/healthchecks)

Nota: en una comprobación anterior, el build no pudo ejecutarse por falta de permisos al socket del daemon Docker. Si `docker info` falla por ese motivo, resolver el acceso a Docker antes de considerar validada la imagen.

# Criterios de listo

### Antes de iniciar el deploy
- `php artisan test`, `./vendor/bin/pint --test`, `docker compose ... config --quiet` y el build de la etapa `railway` terminan correctamente.
- El stack local conecta a PostgreSQL; el entrypoint completa `php artisan migrate --force`; `db:show` y `migrate:status` funcionan.
- El endpoint HTTP `http://localhost:8080/up` responde 200.
- En Railway, la referencia a la base apunta al servicio correcto, `DB_SSLMODE=require`, `APP_DEBUG=false`, `APP_KEY` y `APP_URL` están configurados, y el health check apunta a `/up`.
- Si alguna comprobación falla, detener el deploy y usar los logs de `docker compose` o Railway para resolverla; no borrar volúmenes ni sembrar datos como método de diagnóstico.

# Delivery Steps

### ! Step 1: Ejecutar preflight local con PostgreSQL
La aplicación y la imagen Railway quedan validadas localmente contra PostgreSQL antes de subir la rama.

- Revisar `git status --short` y `git diff --check`; ejecutar `php artisan test` y `./vendor/bin/pint --test`.
- Confirmar acceso con `docker info` y validar las versiones con `docker compose version`.
- Exportar `APP_KEY` y `STAGING_DB_PASSWORD` solo en la shell local; validar `compose.staging.yaml` con `docker compose -f compose.staging.yaml config --quiet`.
- Construir y levantar el servicio `app` con `docker compose -f compose.staging.yaml build --pull app` y `docker compose -f compose.staging.yaml up -d`.
- Confirmar PostgreSQL, migraciones y HTTP 200 de `/up` mediante `ps`, logs, `db:show`, `migrate:status` y `curl`; no correr `db:seed` ni borrar volúmenes.

### ! Step 2: Preparar Railway y habilitar el deploy
Railway queda configurado para iniciar la imagen con PostgreSQL y validar la ruta de salud real antes de recibir tráfico.

- Crear/vincular PostgreSQL y el servicio web; conservar el entrypoint del `Dockerfile` para ejecutar migraciones al arranque.
- Definir variables de staging y la referencia `DB_URL=${{Postgres.DATABASE_URL}}`, usando el nombre real del servicio; mantener las credenciales fuera del repositorio.
- Configurar el health check como `/up` y dejar que Railway inyecte `PORT`; no usar `/healthz`, que no está registrado en `bootstrap/app.php`.
- Solo si todas las comprobaciones pasan y la rama está revisada, hacer push de la rama staging conectada a Railway; revisar logs del build, migraciones y disponibilidad.