# Tecnologías

Ubicaditos es un monolito Laravel con renderizado en servidor. La lógica de negocio vive en PHP y el frontend se enriquece con JavaScript ligero para mapas, gráficas y formularios reactivos.

## Backend

| Tecnología | Versión | Rol en el proyecto |
|------------|---------|--------------------|
| **PHP** | ^8.2 | Lenguaje del servidor. |
| **Laravel Framework** | ^12.0 | Framework MVC: enrutamiento, ORM Eloquent, validación, autenticación, sesiones, colas y vistas Blade. |
| **Laravel Breeze** | ^2.3 (dev) | Scaffolding de autenticación: login, registro, recuperación de contraseña, verificación de correo y perfil. |
| **barryvdh/laravel-dompdf** | ^3.1 | Generación del reporte en PDF desde una vista Blade (`admin/reports-pdf`). |
| **Livewire** | ^3.7 | Instalado como dependencia pero **no se usa** actualmente en ninguna vista. |
| **Laravel Tinker** | ^2.10 | Consola interactiva (`php artisan tinker`). |
| **SQLite** | 3.x | Base de datos por defecto (`database/database.sqlite`). |

## Frontend

| Tecnología | Versión | Rol en el proyecto |
|------------|---------|--------------------|
| **Blade** | Laravel 12 | Motor de plantillas del lado del servidor; layouts y componentes. |
| **Tailwind CSS** | ^3.1 | Framework de utilidades CSS, con modo oscuro por clase y paleta personalizada. |
| **@tailwindcss/forms** | ^0.5 | Estilos base para formularios. |
| **Alpine.js** | ^3.4 | Interactividad declarativa: menús, modales, validación de formularios en vivo, modo oscuro. |
| **Axios** | ^1.11 | Cliente HTTP global (`window.axios`) configurado en `resources/js/bootstrap.js`. |
| **Leaflet** | 1.9.4 (CDN) | Mapas interactivos en los dashboards de pasajero, conductor y administrador. |
| **Chart.js** | última (CDN) | Gráficas de viajes por día y rutas más usadas en la vista de reportes. |
| **Figtree** | Bunny Fonts | Tipografía principal. |

## Herramientas de compilación

| Herramienta | Versión | Rol |
|-------------|---------|-----|
| **Vite** | ^7.0 | Bundler y servidor de desarrollo con HMR. |
| **laravel-vite-plugin** | ^2.0 | Integración de Vite con la directiva `@vite` de Blade. Entradas: `resources/css/app.css` y `resources/js/app.js`. |
| **PostCSS + Autoprefixer** | ^8.4 / ^10.4 | Procesamiento de Tailwind y prefijos de navegador. |
| **concurrently** | ^9.0 | Ejecuta servidor, cola y Vite en paralelo (`composer run dev`). |

> `package.json` también lista `@tailwindcss/vite` ^4.0, pero la configuración activa usa Tailwind 3 vía PostCSS (`postcss.config.js` y `tailwind.config.js`); el plugin de Vite para Tailwind 4 no está registrado en `vite.config.js`.

## Servicios de mapas

| Servicio | Uso |
|----------|-----|
| **OpenStreetMap** | Proveedor de teselas del mapa base. |
| **OSRM** (Open Source Routing Machine) | Calcula la geometría de las rutas siguiendo las calles a partir de las coordenadas de las paradas. Se consulta desde el navegador y desde el comando `routes:generate-paths`. |
| **Geolocation API** del navegador | Obtiene la posición del conductor (para enviarla al servidor) y del pasajero (para calcular rutas cercanas). |

## Desarrollo y calidad

| Herramienta | Versión | Uso |
|-------------|---------|-----|
| **PHPUnit** | ^11.5 | Pruebas unitarias y de integración. |
| **Mockery** | ^1.6 | Dobles de prueba. |
| **Faker** | ^1.23 | Datos ficticios en factories. |
| **Laravel Pint** | ^1.24 | Formateador de código PHP (`./vendor/bin/pint`). |
| **Laravel Pail** | ^1.2 | Visualización de logs en tiempo real (`php artisan pail`). |
| **Laravel Sail** | ^1.41 | Entorno Docker opcional. |
| **Collision** | ^8.6 | Reporte de errores en consola. |

## Sistema de diseño

La paleta de colores se define en `tailwind.config.js` y se usa de forma consistente en todas las vistas:

| Token | Color base (500) | Uso |
|-------|------------------|-----|
| `primary` | `#06b6d4` (cian) | Color principal, encabezados, botones primarios. |
| `accent` | `#a855f7` (violeta) | Acentos y degradados. |
| `success` | `#22c55e` (verde) | Estados activos y confirmaciones. |
| `warning` | `#f59e0b` (ámbar) | Advertencias y mantenimiento. |
| `danger` | `#ef4444` (rojo) | Errores, emergencias y acciones destructivas. |

El **modo oscuro** se activa con la estrategia `darkMode: 'class'`: el componente de navegación guarda la preferencia en `localStorage` (`darkMode`) y agrega o quita la clase `dark` en `<html>`.
