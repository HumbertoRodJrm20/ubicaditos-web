# Respaldo y restauración local

Procedimiento reproducible para el entorno local. La implementación es un comando Artisan propio, sin paquetes externos, para no acoplar el backlog a una dependencia nueva.

## Alcance verificado

El 2026-11-25 se ejecutaron pruebas automáticas con SQLite en un archivo temporal (`tests/Feature/Console/DatabaseBackupTest.php`): creación del snapshot, poda por retención, restauración tras un cambio posterior y fallo explícito con SQLite en memoria. La suite completa quedó en verde. **No se ejecutó `pg_dump`/`pg_restore`, ni se respaldó o restauró la base de desarrollo real, ni se probó PostgreSQL.**

## Crear un respaldo

```bash
php artisan backup:database
```

Opciones:

| Opción | Default | Uso |
|--------|---------|-----|
| `--connection=` | `database.default` | Conexión configurada en `config/database.php`. |
| `--disk=local` | `local` | Disco de Laravel donde se guarda el archivo. |
| `--path=backups` | `backups` | Directorio dentro del disco. |
| `--keep=7` | `7` | Número máximo de respaldos de la misma conexión; se eliminan los más antiguos por nombre. |
| `--name=` | — | Etiqueta opcional para identificar el respaldo. |

Ejemplo etiquetado:

```bash
php artisan backup:database --name=antes-de-migrar --keep=10
```

El nombre resultante sigue el patrón `conexión-[etiqueta]-YYYYMMDD-HHMMSS-uuuuuu.sqlite|dump`. Para SQLite se usa `VACUUM INTO`, por lo que el snapshot es consistente sin copiar una base a medias. Para PostgreSQL se invoca `pg_dump --format=custom` con `PGPASSWORD` tomado de la configuración; la contraseña no se imprime ni se guarda en el nombre del archivo.

Si la conexión es SQLite `:memory:`, el comando termina con error y no crea archivos.

## Restaurar

```bash
php artisan backup:restore backups/sqlite-20261125-120000-000000.sqlite --force
```

`file` es la ruta relativa dentro del disco indicado con `--disk`. Sin `--force`, el comando pide confirmación porque sobrescribe la base actual.

Para PostgreSQL se usa `pg_restore --clean --if-exists --no-owner`. Se requieren los binarios `pg_dump` y `pg_restore` instalados y accesibles en el `PATH`; no están verificados en este entorno.

## Retención y límites

La poda conserva los N archivos más recientes cuyo nombre empieza por `conexión-` y termina con la extensión del driver. No elimina archivos de otras conexiones ni de otros discos. No hay cifrado, firma ni copia fuera del equipo: esto es una herramienta local, no una estrategia de recuperación ante desastres.

Antes de restaurar una base real, se debe crear un respaldo nuevo, detener a los usuarios del sistema y confirmar la ruta de destino. La integración con Docker/IONOS y la programación periódica quedan fuera de este paso; se documentarán sólo cuando se implementen y verifiquen.
