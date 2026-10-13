# Ubicaditos Web — Documentación Técnica

**Ubicaditos** es una plataforma web para el monitoreo del transporte público urbano en Oaxaca, México. Permite a los pasajeros ver en un mapa interactivo las rutas, paradas y autobuses, a los conductores registrar sus viajes y compartir su ubicación GPS en tiempo real, y a los administradores gestionar la flota, las rutas, los usuarios y generar reportes.

El proyecto está construido sobre **Laravel 12** siguiendo el patrón **MVC (Modelo–Vista–Controlador)**, con vistas renderizadas en servidor mediante **Blade**, estilos con **Tailwind CSS**, interactividad con **Alpine.js** y mapas con **Leaflet + OpenStreetMap + OSRM**.

## Inicio rápido

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> ubicaditos-web
cd ubicaditos-web

# 2. Instalar dependencias de PHP y JavaScript
composer install
npm install            # o: bun install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 4. Crear la base de datos SQLite, migrar y poblar con datos de prueba
touch database/database.sqlite
php artisan migrate --seed

# 5. Levantar el entorno de desarrollo (servidor + cola + Vite)
composer run dev
```

Abre `http://localhost:8000` e inicia sesión con alguna de las cuentas de prueba:

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | `admin@ubicaditos.com` | `password` |
| Conductor | `chofer1@ubicaditos.com` … `chofer100@ubicaditos.com` | `password` |
| Pasajero | `pasajero1@ubicaditos.com` … `pasajero600@ubicaditos.com` | `password` |

> La guía completa de instalación, variables de entorno y despliegue está en [Instalación y configuración](01-instalacion.md).

## Contenido

| # | Sección | Descripción |
|---|---------|-------------|
| 1 | [Instalación y configuración](01-instalacion.md) | Requisitos, comandos, variables de entorno, scripts y despliegue. |
| 2 | [Tecnologías](02-tecnologias.md) | Stack de backend, frontend, servicios externos y herramientas. |
| 3 | [Arquitectura](03-arquitectura.md) | Patrón MVC, ciclo de vida de una petición, estructura de carpetas y roles. |
| 4 | [Modelos](04-modelos.md) | Modelos Eloquent, atributos, relaciones y métodos auxiliares. |
| 5 | [Base de datos](05-base-de-datos.md) | Esquema, diagrama entidad-relación, migraciones y seeders. |
| 6 | [Controladores](06-controladores.md) | Lógica de cada controlador, validaciones y reglas de negocio. |
| 7 | [Vistas y frontend](07-vistas.md) | Blade, layouts, componentes, mapas, gráficas y JavaScript. |
| 8 | [Rutas y API](08-rutas.md) | Referencia completa de rutas web, middleware y endpoints JSON. |
| 9 | [Comandos de consola](09-comandos.md) | Comandos Artisan personalizados y utilidades. |
| 10 | [Pruebas y estado del proyecto](10-pruebas-y-estado.md) | Suite de pruebas, observaciones técnicas y mejoras sugeridas. |
| 11 | [Diagramas y diccionario de datos](11-diagramas.md) | Diagramas de clases, de objetos, E-R y relacional, y diccionario de datos. |

## Resumen funcional

| Rol | Funcionalidades principales |
|-----|-----------------------------|
| **Pasajero** | Mapa en tiempo real con rutas, paradas y autobuses; rutas más cercanas a su ubicación; tiempos estimados de llegada; paradas favoritas; listado de rutas y autobuses; calificación de conductores. |
| **Conductor** | Panel con su autobús asignado y viajes recientes; mapa de su ruta con GPS; inicio y fin de viaje (validando turno y cercanía a una parada); actualización de pasajeros a bordo; reporte de emergencias. |
| **Administrador** | Dashboard con estadísticas y mapa en vivo; CRUD de autobuses, rutas y usuarios; asignación de autobuses a conductores; gestión de pasajeros; estadísticas y reportes con gráficas; exportación a PDF y CSV. |
