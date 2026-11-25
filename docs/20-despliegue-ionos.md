# 20. Despliegue de producción en IONOS

Esta guía describe el despliegue mínimo del estudio de rutas y del resto de Ubicaditos en un VPS de IONOS con Docker Compose. No introduce dependencias nuevas: reutiliza el `Dockerfile` multi-etapa, Bun para los assets, PostgreSQL y Laravel Reverb.

## 1. Requisitos

- VPS IONOS con Debian 12 o Ubuntu 22.04+, 2 vCPU / 4 GB RAM recomendados.
- Docker Engine y `docker compose` plugin instalados.
- Dominio con registro A (y AAAA si aplica) apuntando al VPS.
- Puertos abiertos: `22` (SSH), `80` (HTTP) y `443` (TLS). El contenedor `app` publica sólo `80`; TLS se termina en IONOS/Plesk o en un proxy superior.
- PostgreSQL 16 se levanta como servicio `postgres` dentro de Compose. Si se usa PostgreSQL administrado de IONOS, cambiar `DB_HOST` en `.env`.

## 2. Preparación

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-plugin git
sudo usermod -aG docker "$USER"   # cerrar sesión y volver a entrar

git clone git@github.com:HumbertoRodJrm20/ubicaditos-web.git ubicaditos-web
cd ubicaditos-web

cp .env.production.example .env
php artisan key:generate          # si PHP no está en el host: docker compose run --rm app php artisan key:generate
php artisan reverb:generate       # o docker compose run --rm reverb php artisan reverb:generate
```

Revisar en `.env`:

- `APP_URL=https://tu-dominio.com`
- `DB_*` (el host del servicio es `postgres` con Compose)
- `REVERB_HOST=tu-dominio.com`, `REVERB_PORT=443`, `REVERB_SCHEME=https`
- `OSRM_URL` y `OSRM_TIMEOUT` (ver sección OSRM)
- `SESSION_SECURE_COOKIE=true`

> Nunca subir `.env` al repositorio. `.dockerignore` ya lo excluye de la imagen.

## 3. Levantar servicios

```bash
docker compose -f compose.production.yaml up -d --build
docker compose -f compose.production.yaml exec app php artisan migrate --force
docker compose -f compose.production.yaml exec app php artisan config:cache
docker compose -f compose.production.yaml exec app php artisan route:cache
docker compose -f compose.production.yaml exec app php artisan view:cache
```

Servicios resultantes:

| Servicio | Función |
|---|---|
| `app` | Nginx + PHP-FPM, sirve Blade y `public/build`. Expone `80`. |
| `postgres` | Base de datos PostgreSQL 16 con volumen `pgdata`. |
| `reverb` | WebSockets en `8080`, detrás de `location /app` de Nginx. |
| `queue` | Worker de la cola `database`. |

Comprobación rápida:

```bash
curl -fsS http://127.0.0.1/healthz
docker compose -f compose.production.yaml logs -f app
```

## 4. TLS y Reverb

El `nginx` del contenedor escucha HTTP. En IONOS se puede terminar TLS de tres formas:

1. **Balanceador / Plesk proxy (recomendado):** el proxy público usa certificado y reenvía `:80` al contenedor. Debe reenviar las cabeceras `Upgrade`/`Connection` para `/app`.
2. **Certbot en el host:** instalar certificado y levantar un nginx/certbot en el host que haga proxy a `127.0.0.1:80`; la configuración del contenedor ya incluye `location /app`.
3. **Certificados montados en el contenedor:** añadir un bloque `listen 443 ssl` en `docker/nginx/ubicaditos.conf` y montar los certificados como volumen de sólo lectura.

El cliente de Reverb usa `wss://tu-dominio.com:443/app/{REVERB_APP_KEY}`. Nginx proxyúa ese prefijo al servicio `reverb:8080`. Si el balanceador no soporta WebSocket, el frontend continúa con el fallback de sondeo de `/api/buses/active`.

## 5. Estudio de rutas y paradas

- Acceso: `/admin/routes/manage`, protegido por `auth` + `admin`.
- El mapa lee `path_coordinates` ya persistidos: **no consulta OSRM en cada carga**.
- "Regenerar trazado" llama al endpoint firmado del servidor y sólo ahí se consulta OSRM. Si falla, se conserva el trazado anterior.
- Guardar orden usa una transacción y respeta las paradas frecuentes ancladas.
- Eliminar una ruta/parada con historial la desactiva en vez de borrarla.

## 6. Límites de OSRM

El endpoint público `https://router.project-osrm.org` es gratuito, no garantiza disponibilidad ni cuota y está pensado para desarrollo o volumen muy bajo. En producción:

- No programar regeneraciones periódicas ni llamadas por usuario.
- Usar el estudio sólo cuando cambie la secuencia de paradas.
- Aumentar `OSRM_TIMEOUT` si la red es lenta, pero evitar valores altos que bloqueen workers.
- Para tráfico real, desplegar OSRM propio o un proveedor comercial y cambiar `OSRM_URL`.

## 7. Backups

### Base de datos

```bash
# Backup lógico (comprimido)
docker compose -f compose.production.yaml exec -T postgres \
  pg_dump -U "${DB_USERNAME:-ubicaditos}" -d "${DB_DATABASE:-ubicaditos}" -Fc \
  > "ubicaditos-$(date +%F).dump"

# Restauración
docker compose -f compose.production.yaml exec -T postgres \
  pg_restore -U "${DB_USERNAME:-ubicaditos}" -d "${DB_DATABASE:-ubicaditos}" --clean --if-exists \
  < "ubicaditos-YYYY-MM-DD.dump"
```

### Volumen de storage

```bash
docker run --rm \
  -v ubicaditos_storage:/data:ro \
  -v "$PWD":/backup \
  alpine tar -czf "/backup/ubicaditos-storage-$(date +%F).tar.gz" -C /data .
```

Guardar los dumps fuera del VPS (por ejemplo IONOS Cloud Storage o S3) y probar la restauración periódicamente. Las sesiones y la caché están en PostgreSQL, por lo que el dump cubre casi todo el estado; el volumen `storage` contiene logs y vistas compiladas.

## 8. Actualizaciones y operación

```bash
git pull
docker compose -f compose.production.yaml up -d --build
docker compose -f compose.production.yaml exec app php artisan migrate --force
docker compose -f compose.production.yaml exec app php artisan config:clear
docker compose -f compose.production.yaml exec app php artisan view:cache
```

Comandos útiles:

```bash
docker compose -f compose.production.yaml ps
docker compose -f compose.production.yaml logs -f app reverb queue
docker compose -f compose.production.yaml restart app reverb queue
```

Antes de desplegar, en local se debe verificar:

```bash
php artisan test
php artisan view:cache
bun run build
php artisan route:list --path=admin
```
