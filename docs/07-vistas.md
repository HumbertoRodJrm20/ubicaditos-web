# Vistas y frontend

La capa de presentación se construye con **Blade** (renderizado en servidor), **Tailwind CSS** para los estilos y **Alpine.js** para la interactividad. Las vistas que requieren mapas o gráficas cargan **Leaflet** y **Chart.js** desde CDN e incluyen su JavaScript dentro de la propia plantilla.

## Organización

```text
resources/views/
├── layouts/          # app, guest, navigation
├── components/       # Componentes Blade reutilizables de Breeze
├── auth/             # Login, registro, recuperación y verificación
├── profile/          # Edición de perfil y parciales
├── admin/            # Panel del administrador
│   ├── buses/        # index, create, edit
│   ├── routes/       # index, create, edit
│   └── users/        # create, edit
├── driver/           # dashboard, map
├── passenger/        # dashboard (mapa), menu, routes, buses
├── dashboard.blade.php
└── welcome.blade.php
```

## Assets y compilación

Vite compila dos puntos de entrada declarados en `vite.config.js` y referenciados desde los layouts con `@vite([...])`:

| Archivo | Contenido |
|---------|-----------|
| `resources/css/app.css` | Directivas `@tailwind base`, `components` y `utilities`. |
| `resources/js/app.js` | Importa `bootstrap.js`, registra Alpine en `window.Alpine` y lo inicia. |
| `resources/js/bootstrap.js` | Expone Axios en `window.axios` con la cabecera `X-Requested-With: XMLHttpRequest`. |

Tailwind escanea `resources/views/**/*.blade.php`, las vistas compiladas y las de paginación de Laravel para generar solo las clases usadas.

Las imágenes del proyecto están en `public/images` (`autobus.svg`, `autobusfblanco.svg`, `bus-stop-logo.svg`, `parada-de-autobus.svg`) y se usan como logotipo y marcadores del mapa.

## Layouts

| Layout | Uso | Características |
|--------|-----|-----------------|
| `layouts/app.blade.php` | Páginas autenticadas, usado como `<x-app-layout>`. | Incluye el token CSRF en `<meta>`, la fuente Figtree, *preconnect* a unpkg, OSRM y OpenStreetMap, el CSS de Leaflet, la navegación, un `$header` opcional y el `$slot` principal sobre un fondo con degradado (con variante oscura). |
| `layouts/guest.blade.php` | Pantallas de autenticación, usado como `<x-guest-layout>`. | Tarjeta centrada con el logotipo. |
| `layouts/navigation.blade.php` | Barra superior. | Enlaces según el rol del usuario, menú desplegable de perfil y cierre de sesión, menú responsive para móvil y **conmutador de modo oscuro** persistido en `localStorage`. |

Las clases `App\View\Components\AppLayout` y `GuestLayout` simplemente devuelven estas plantillas.

## Componentes Blade

Componentes anónimos en `resources/views/components`, provenientes de Breeze y adaptados a la paleta del proyecto:

| Componente | Uso |
|------------|-----|
| `<x-application-logo>` | Logotipo de la aplicación. |
| `<x-auth-session-status>` | Mensaje de estado de sesión (por ejemplo, "enlace enviado"). |
| `<x-input-label>`, `<x-text-input>`, `<x-input-error>` | Etiqueta, campo de texto y errores de validación. |
| `<x-primary-button>`, `<x-secondary-button>`, `<x-danger-button>` | Botones con estilos predefinidos. |
| `<x-dropdown>`, `<x-dropdown-link>` | Menú desplegable con Alpine. |
| `<x-nav-link>`, `<x-responsive-nav-link>` | Enlaces de navegación con estado activo. |
| `<x-modal>` | Modal accesible controlado por eventos de Alpine. |

## Vistas de autenticación

| Vista | Descripción |
|-------|-------------|
| `auth/login` | Inicio de sesión con validación en cliente mediante Alpine (`loginForm()`). |
| `auth/register` | Registro de pasajeros con validación en vivo (`registerForm()`): nombre y apellidos solo letras, teléfono de 10 dígitos, fortaleza de contraseña y comprobación asíncrona de correo disponible contra `POST /check-email`. |
| `auth/forgot-password`, `auth/reset-password` | Recuperación de contraseña. |
| `auth/verify-email`, `auth/confirm-password` | Verificación de correo y confirmación de contraseña. |

## Vistas del administrador

| Vista | Controlador | Contenido |
|-------|-------------|-----------|
| `admin/dashboard` | `AdminController@index` | Tarjetas de estadísticas, accesos rápidos (crear usuario, conductores, pasajeros, autobuses, rutas, reportes), **mapa en tiempo real** con todas las rutas activas, paradas y autobuses animados, listado de autobuses activos y viajes recientes. |
| `admin/drivers` | `AdminController@drivers` | Tabla paginada con búsqueda, autobús asignado, viajes y calificación; modal para asignar o desasignar autobús. |
| `admin/passengers` | `AdminController@passengers` | Tabla paginada con búsqueda, calificaciones emitidas y favoritos. |
| `admin/buses/index` | `BusController@index` | Tabla paginada de autobuses con búsqueda y acciones. |
| `admin/buses/create`, `edit` | `BusController` | Formulario con validación en Alpine (`busForm()`): placa, modelo, capacidad, conductor, estado y rutas. |
| `admin/routes/index` | `RouteController@index` | Tabla paginada de rutas con color, código y conteos. |
| `admin/routes/create`, `edit` | `RouteController` | Formulario (`routeForm()`) con selección y orden de paradas. |
| `admin/users/create`, `edit` | `UserController` | Formulario (`userForm()`) con datos personales, rol y turno (visible solo para conductores). |
| `admin/statistics` | `AdminController@statistics` | Tablas de viajes por día, uso de autobuses y top 10 conductores. |
| `admin/reports` | `ReportController@index` | Tarjetas de indicadores, gráfica de línea de viajes por día y gráfica de rutas más utilizadas (Chart.js), tablas de autobuses y conductores, y botones de exportación a PDF y CSV. |
| `admin/reports-pdf` | `ReportController@exportPdf` | Plantilla con estilos en línea, optimizada para DomPDF. |
| `admin/emergencies` | — | Listado de emergencias con botón para resolver. **No tiene ruta ni controlador asociado** actualmente. |

## Vistas del conductor

| Vista | Controlador | Contenido |
|-------|-------------|-----------|
| `driver/dashboard` | `DriverController@index` | Datos del autobús asignado, tarjeta de viaje en curso, acceso al mapa, calificación promedio y viajes recientes. |
| `driver/map` | `DriverController@map` | Mapa de la ruta asignada con su trazo y paradas numeradas, marcador del autobús, panel de estado GPS, velocidad, última actualización, botones para iniciar o finalizar viaje, actualizar pasajeros y reportar emergencia. |

Comportamiento del mapa del conductor:

1. Dibuja la ruta solicitando a OSRM la geometría entre las paradas ordenadas, con *timeout* y una línea recta como respaldo (`fallbackStraightLine`).
2. Observa la posición con `navigator.geolocation.watchPosition()` y, en cada cambio, mueve el marcador, calcula la velocidad entre lecturas y envía la posición a `POST /driver/update-location` con el token CSRF.
3. Detecta la proximidad a una parada (umbral aproximado de 100 m) para resaltarla.
4. Si el GPS no está disponible o se deniega el permiso, usa una **ubicación simulada** (`useSimulatedLocationDriver`).

## Vistas del pasajero

| Vista | Controlador | Contenido |
|-------|-------------|-----------|
| `passenger/dashboard` | `PassengerController@map` | Página principal: mapa interactivo a pantalla completa. |
| `passenger/menu` | `PassengerController@index` | Accesos a mapa, rutas y autobuses; lista de paradas favoritas con opción de eliminarlas. |
| `passenger/routes` | `PassengerController@routes` | Tarjetas de rutas disponibles con paradas, autobuses y filtro. |
| `passenger/buses` | `PassengerController@buses` | Tarjetas de autobuses en servicio con filtro por ruta y modal para **calificar al conductor** con estrellas. |

Funcionalidades del mapa del pasajero (`passenger/dashboard`):

| Función | Descripción |
|---------|-------------|
| Rutas y paradas | Dibuja las rutas activas con su color (trazo OSRM cargado de forma diferida) y las paradas con un ícono propio. |
| Autobuses animados | Simula el desplazamiento de los autobuses activos a lo largo del trazo de su ruta, interpolando posiciones cada 200 ms. |
| Ubicación del usuario | Obtiene la posición del pasajero con la API de geolocalización, o una ubicación simulada si no está disponible. |
| Rutas más cercanas | `showNearestRoutes()` calcula, con la fórmula de Haversine, la parada más cercana de cada ruta y muestra hasta 3 rutas dentro de un radio de 5 km. |
| Tiempos de llegada | Al seleccionar una parada, estima para cada autobús que pasa por ella el tiempo de llegada a partir de la distancia sobre la ruta, una velocidad promedio (20 km/h por defecto) y 30 segundos por parada intermedia. |
| Favoritos | `addToFavorites()` y `removeFromFavorites()` llaman con `fetch` a `/passenger/favorites/{stop}` y actualizan el popup de la parada. |

## Patrones de JavaScript

- **Datos del servidor al cliente:** los controladores pasan colecciones a la vista y estas se serializan con `@json(...)` para que el script de Leaflet las consuma sin peticiones adicionales.
- **Formularios reactivos:** cada formulario complejo define una función Alpine (`busForm()`, `routeForm()`, `userForm()`, `loginForm()`, `registerForm()`) con estado, validación por campo y mensajes de error antes del envío.
- **Peticiones AJAX:** se usa `fetch` con la cabecera `X-CSRF-TOKEN` tomada de `csrf_token()`.
- **Resiliencia:** las llamadas a OSRM se hacen con `AbortController` y *timeout*, con reintentos (en el dashboard del administrador) o un trazo de respaldo.

## Internacionalización

La interfaz está escrita directamente en español. El archivo `lang/es.json` traduce las cadenas en inglés que aún usan las vistas de perfil heredadas de Breeze (por ejemplo, *"Update Password"* → *"Actualizar Contraseña"*). Para que esas traducciones se apliquen, `APP_LOCALE` debe ser `es`.

## Vistas heredadas

| Vista | Estado |
|-------|--------|
| `welcome.blade.php` | Página de bienvenida por defecto de Laravel; no se usa porque `/` redirige a `/login`. |
| `dashboard.blade.php` | Dashboard genérico de Breeze; no se usa porque `/dashboard` redirige según el rol. |
