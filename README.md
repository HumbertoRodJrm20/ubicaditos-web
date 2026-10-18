# Ubicaditos Web

**Ubicaditos** es una plataforma web para el monitoreo del transporte público urbano en Oaxaca, México. Permite a los pasajeros ver en un mapa interactivo las rutas, paradas y autobuses, a los conductores registrar sus viajes y compartir su ubicación GPS en tiempo real, y a los administradores gestionar la flota, las rutas, los usuarios y generar reportes.

El proyecto está construido sobre **Laravel 12** siguiendo el patrón **MVC (Modelo–Vista–Controlador)**, con vistas renderizadas en servidor mediante **Blade**, estilos con **Tailwind CSS**, interactividad con **Alpine.js**, mapas con **Leaflet + OpenStreetMap + OSRM** y tiempo real con **Laravel Reverb** (WebSockets, con fallback de sondeo). El tooling de JavaScript usa **bun**.

## Inicio rápido

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> ubicaditos-web
cd ubicaditos-web

# 2. Instalar dependencias de PHP y JavaScript
composer install
bun install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 4. Crear la base de datos SQLite, migrar y poblar con datos de prueba
touch database/database.sqlite
php artisan migrate --seed

# 5. Levantar el entorno de desarrollo (servidor + cola + Vite)
composer run dev

# 6. (Opcional) Servidor WebSockets para el tiempo real
php artisan reverb:start
```

Abre `http://localhost:8000` e inicia sesión con alguna de las cuentas de prueba:

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | `admin@ubicaditos.com` | `password` |
| Conductor | `chofer1@ubicaditos.com` … `chofer100@ubicaditos.com` | `password` |
| Pasajero | `pasajero1@ubicaditos.com` … `pasajero600@ubicaditos.com` | `password` |

> La guía completa de instalación, variables de entorno y despliegue está en [docs/01-instalacion.md](docs/01-instalacion.md). Si Reverb no está corriendo, los mapas usan automáticamente un sondeo cada 5 segundos.

## Pruebas

```bash
php artisan test          # suite Feature/Unit (SQLite en memoria)
cd tests/selenium && pip install -r requirements.txt && python3 -m pytest   # E2E con Chrome headless (rama de pruebas)
k6 run tests/load/ubicaditos-iteration3-load.js -e K6_VUS=15   # carga, solo lectura (rama de pruebas)
```

## Documentación

La documentación técnica completa vive en [`docs/`](docs/README.md):

| # | Sección | Descripción |
|---|---------|-------------|
| 1 | [Instalación y configuración](docs/01-instalacion.md) | Requisitos, comandos, variables de entorno, scripts y despliegue. |
| 2 | [Tecnologías](docs/02-tecnologias.md) | Stack de backend, frontend, servicios externos y herramientas. |
| 3 | [Arquitectura](docs/03-arquitectura.md) | Patrón MVC, ciclo de vida de una petición, estructura de carpetas y roles. |
| 4 | [Modelos](docs/04-modelos.md) | Modelos Eloquent, atributos, relaciones y métodos auxiliares. |
| 5 | [Base de datos](docs/05-base-de-datos.md) | Esquema, diagrama entidad-relación, migraciones y seeders. |
| 6 | [Controladores](docs/06-controladores.md) | Lógica de cada controlador, validaciones y reglas de negocio. |
| 7 | [Vistas y frontend](docs/07-vistas.md) | Blade, layouts, componentes, mapas, gráficas y JavaScript. |
| 8 | [Rutas y API](docs/08-rutas.md) | Referencia completa de rutas web, middleware y endpoints JSON. |
| 9 | [Comandos de consola](docs/09-comandos.md) | Comandos Artisan personalizados y utilidades. |
| 10 | [Pruebas y estado del proyecto](docs/10-pruebas-y-estado.md) | Suite de pruebas, observaciones técnicas y mejoras sugeridas. |
| 11 | [Diagramas y diccionario de datos](docs/11-diagramas.md) | Diagramas de clases, de objetos, E-R y relacional, y diccionario de datos. |
| 12 | [API Swagger](docs/12-swagger-api.md) | Especificación OpenAPI (`docs/swagger.yaml`), modelos y seguridad. |
| 13 | [Iteración 3](docs/13-iteracion-3.md) | Tiempo real con Reverb, búsqueda y filtros, estadísticas de conductor e incidencias. |
| 14 | [Registro técnico (_devlog)](docs/_devlog.md) | Bitácora técnica de lo realizado en cada iteración. |

## Resumen funcional

| Rol | Funcionalidades principales |
|-----|-----------------------------|
| **Pasajero** | Mapa en tiempo real (WebSocket con fallback de sondeo) con rutas, paradas y autobuses; búsqueda y filtros de autobuses (estado, ruta, ocupación); rutas más cercanas a su ubicación; paradas favoritas; calificación de conductores. |
| **Conductor** | Panel con su autobús asignado y viajes recientes; mapa de su ruta con GPS (interruptor de compartir ubicación); inicio y fin de viaje (validando turno y cercanía a una parada); pasajeros a bordo; estadísticas propias; reporte de incidencias con 5 tipos. |
| **Administrador** | Dashboard con estadísticas y mapa en vivo; detalle de autobús (KPIs, viajes, incidencias); gestión de incidencias con filtros y resolución (la unidad vuelve a servicio); CRUD de autobuses, rutas y usuarios; reportes con gráficas; exportación a PDF y CSV. |
