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
│   ├── buses/        # index, create, edit, show
│   ├── routes/       # index, create, edit
│   ├── users/        # create, edit
│   └── emergencies.blade.php
├── driver/           # dashboard, map, statistics
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
| `resources/js/realtime.js` | Módulo de tiempo real (global `window.UbicaditosRealtime`): Laravel Echo + Reverb sobre el canal privado `buses`, con fallback de polling a `/api/buses/active` cada 5 s; importado desde `app.js`. |

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
| `<x-passenger-layout>` | Layout propio de las vistas del pasajero (fondo con degradado, barra de navegación y ranura de contenido). |
| `<x-passenger-nav>` | Navegación por rol usada por ese layout: para conductores incluye **"Estadísticas"** (`driver.statistics`) y para administradores **"Emergencias"** (`admin.emergencies`). |

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
| `admin/dashboard` | `AdminController@index` | Tarjetas de estadísticas, accesos rápidos (crear usuario, conductores, pasajeros, autobuses, rutas, reportes), **mapa en tiempo real** con todas las rutas activas, paradas y autobuses animados, listado de autobuses activos y viajes recientes. El script se protege con una guarda `typeof L !== 'undefined'` antes de usar Leaflet, se integra con el módulo de tiempo real (posiciones reales detienen la simulación local de ese autobús) y los popups muestran la ocupación actual (`pasajeros/capacidad`). |
| `admin/drivers` | `AdminController@drivers` | Tabla paginada con búsqueda, autobús asignado, viajes y calificación; modal para asignar o desasignar autobús. |
| `admin/passengers` | `AdminController@passengers` | Tabla paginada con búsqueda, calificaciones emitidas y favoritos. |
| `admin/buses/index` | `BusController@index` | Tabla paginada de autobuses con búsqueda, acciones y enlace **Detalles** hacia `admin.buses.show`. |
| `admin/buses/create`, `edit` | `BusController` | Formulario con validación en Alpine (`busForm()`): placa, modelo, capacidad, conductor, estado y rutas. |
| `admin/buses/show` | `BusController@show` | Detalle del autobús: tarjetas KPI de ocupación (capacidad, pasajeros, asientos libres), ficha de datos, conductor y rutas asignadas, últimas 5 incidencias (con enlace al listado de emergencias) y tabla de los últimos 10 viajes. |
| `admin/routes/index` | `RouteController@index` | Tabla paginada de rutas con color, código y conteos. |
| `admin/routes/create`, `edit` | `RouteController` | Formulario (`routeForm()`) con selección y orden de paradas. |
| `admin/users/create`, `edit` | `UserController` | Formulario (`userForm()`) con datos personales, rol y turno (visible solo para conductores). |
| `admin/statistics` | `AdminController@statistics` | Tablas de viajes por día, uso de autobuses y top 10 conductores. |
| `admin/reports` | `ReportController@index` | Tarjetas de indicadores, gráfica de línea de viajes por día y gráfica de rutas más utilizadas (Chart.js), tablas de autobuses y conductores, y botones de exportación a PDF y CSV. |
| `admin/reports-pdf` | `ReportController@exportPdf` | Plantilla con estilos en línea, optimizada para DomPDF. |
| `admin/emergencies` | `AdminController@emergencies`, `@resolveEmergency` | Listado de incidencias reportadas por los conductores con contadores por estado (pendientes, en atención, resueltas), filtros de estado y tipo que se autoenvían al cambiar, insignias de tipo/estado, fecha de resolución y acción **Resolver** (visible solo para incidencias pendientes, vía `POST admin.emergencies.resolve`). |

Estilo de las vistas de administración: las pantallas `admin/buses/index|create|edit`, `admin/routes/index|create|edit`, `admin/passengers` y `admin/users/create|edit` fueron **restiladas al estilo *glass*** *mobile-first* (diseño pensado para 375×667 con página de degradado a pantalla completa, paneles translúcidos con `backdrop-blur-xl` y bordes redondeados grandes, insumos y botones tipo píldora, tablas envueltas en `overflow-x-auto` y variantes de modo oscuro). La receta de estilo se define localmente en cada vista (arreglos de clases PHP en la sección `@php` superior).

## Vistas del conductor

| Vista | Controlador | Contenido |
|-------|-------------|-----------|
| `driver/dashboard` | `DriverController@index` | Datos del autobús asignado, tarjeta de viaje en curso, acceso al mapa, calificación promedio y viajes recientes. El botón **"Reportar Emergencia"** abre un modal real (`#emergency-modal`) con un formulario que envía a `driver.emergency`: selector de tipo (accidente, avería mecánica, emergencia médica, incidente de seguridad u otra), descripción con contador de 500 caracteres y aviso de que la unidad cambiará a estado *emergencia*. El botón **"Mis estadísticas"** lleva a `driver.statistics`. |
| `driver/map` | `DriverController@map` | Mapa de la ruta asignada con su trazo y paradas numeradas, marcador del autobús, panel de estado GPS, velocidad, última actualización, botones para iniciar o finalizar viaje, actualizar pasajeros, reportar emergencia y el interruptor **"Compartir ubicación"** (`#share-location-toggle`). |
| `driver/statistics` | `DriverController@statistics` | Tarjetas KPI (viajes totales/completados, pasajeros, ocupación promedio, calificación, distancia, tiempo al volante e incidencias reportadas), gráfica de barras **en CSS puro** con los viajes de los últimos 7 días, top de rutas por viajes completados y las últimas calificaciones recibidas con comentario. |

Comportamiento del mapa del conductor:

1. Dibuja la ruta solicitando a OSRM la geometría entre las paradas ordenadas, con *timeout* y una línea recta como respaldo (`fallbackStraightLine`).
2. Observa la posición con `navigator.geolocation.watchPosition()` y, en cada cambio, mueve el marcador, calcula la velocidad entre lecturas y envía la posición a `POST /driver/update-location` con el token CSRF.
3. Detecta la proximidad a una parada (umbral aproximado de 100 m) para resaltarla.
4. Si el GPS no está disponible o se deniega el permiso, usa una **ubicación simulada** (`useSimulatedLocationDriver`); en modo simulado la posición se reenvía sola cada 5 s con una deriva mínima para que los pasajeros vean la unidad "viva".
5. El interruptor **"Compartir ubicación"** pausa o reanuda el envío de posiciones al servidor; su estado se persiste en `localStorage['ubicaditos_share_location']` y se respeta al recargar (arranque con `applySharingState()`).
6. Hacer clic en el mapa —solo en modo simulado y sin viaje activo— fija una posición simulada personalizada: si compartir está activado se envía al servidor, en caso contrario solo se mueve el marcador local.

## Vistas del pasajero

| Vista | Controlador | Contenido |
|-------|-------------|-----------|
| `passenger/dashboard` | `PassengerController@map` | Página principal: mapa interactivo a pantalla completa, integrado con el módulo de tiempo real (Echo/WebSocket con *fallback* de polling) y con un buscador que filtra paradas y autobuses. Incluye contador de flota (`#fleet-counter`) e indicador de estado de conexión (`#realtime-status`). |
| `passenger/menu` | `PassengerController@index` | Accesos a mapa, rutas y autobuses; lista de paradas favoritas con opción de eliminarlas. |
| `passenger/routes` | `PassengerController@routes` | Tarjetas de rutas disponibles con paradas, autobuses y filtro. |
| `passenger/buses` | `PassengerController@buses` | Lista de autobuses con filtros de servidor (`q`, ruta, estado y orden) más chips de estado y ruta, select de orden (placa u ocupación), insignias de estado, barras de ocupación, asientos libres, modal de **detalles** (`#bus-details-modal`, con asientos en `#bd-seats`) y modal para **calificar al conductor** con estrellas. El buscador filtra al instante en cliente con `filterBuses()` (sobre atributos `data-*` de cada tarjeta) y `#no-buses-message` muestra el estado vacío. |

Funcionalidades del mapa del pasajero (`passenger/dashboard`):

| Función | Descripción |
|---------|-------------|
| Rutas y paradas | Dibuja las rutas activas con su color (trazo OSRM cargado de forma diferida) y las paradas con un ícono propio. |
| Autobuses animados | Simula el desplazamiento de los autobuses activos a lo largo del trazo de su ruta, interpolando posiciones cada 200 ms. |
| Ubicación del usuario | Obtiene la posición del pasajero con la API de geolocalización, o una ubicación simulada si no está disponible. |
| Rutas más cercanas | `showNearestRoutes()` calcula, con la fórmula de Haversine, la parada más cercana de cada ruta y muestra hasta 3 rutas dentro de un radio de 5 km. |
| Tiempos de llegada | Al seleccionar una parada, estima para cada autobús que pasa por ella el tiempo de llegada a partir de la distancia sobre la ruta, una velocidad promedio (20 km/h por defecto) y 30 segundos por parada intermedia. |
| Favoritos | `addToFavorites()` y `removeFromFavorites()` llaman con `fetch` a `/passenger/favorites/{stop}` y actualizan el popup de la parada. |
| Tiempo real | Arranca `UbicaditosRealtime` (`resources/js/realtime.js`) con polling cada 5 s: los autobuses con posición real dejan de moverse por simulación local y sus marcadores siguen los eventos; `#fleet-counter` refleja la flota (o los resultados del buscador) y `#realtime-status` muestra "en vivo", "actualizando…" o "sin conexión en vivo". |
| Buscador del mapa | El campo "Buscar parada o ruta" filtra los marcadores por nombre de parada, dirección o placa/ruta del autobús, y actualiza el contador de flota. |

## Patrones de JavaScript

- **Datos del servidor al cliente:** los controladores pasan colecciones a la vista y estas se serializan con `@json(...)` para que el script de Leaflet las consuma sin peticiones adicionales.
- **Formularios reactivos:** cada formulario complejo define una función Alpine (`busForm()`, `routeForm()`, `userForm()`, `loginForm()`, `registerForm()`) con estado, validación por campo y mensajes de error antes del envío.
- **Peticiones AJAX:** se usa `fetch` con la cabecera `X-CSRF-TOKEN` tomada de `csrf_token()`.
- **Tiempo real:** `resources/js/realtime.js` (importado desde `app.js`) expone `window.UbicaditosRealtime`, que combina Laravel Echo + Reverb sobre el canal privado `buses` con un *fallback* de polling a `/api/buses/active` cada 5 s; normaliza ambos orígenes a un mismo payload y notifica a suscriptores con `onBusLocation()` y `onStatus()`. Lo consumen los dashboards del pasajero y del administrador, que degradan a simulación local si el módulo no está disponible.
- **Resiliencia:** las llamadas a OSRM se hacen con `AbortController` y *timeout*, con reintentos (en el dashboard del administrador) o un trazo de respaldo. Los mapas se protegen con una guarda `typeof L !== 'undefined'` para no romper si Leaflet no carga.

## Internacionalización

La interfaz está escrita directamente en español. El archivo `lang/es.json` traduce las cadenas en inglés que aún usan las vistas de perfil heredadas de Breeze (por ejemplo, *"Update Password"* → *"Actualizar Contraseña"*). Para que esas traducciones se apliquen, `APP_LOCALE` debe ser `es`.

## Vistas heredadas

| Vista | Estado |
|-------|--------|
| `welcome.blade.php` | Página de bienvenida por defecto de Laravel; no se usa porque `/` redirige a `/login`. |
| `dashboard.blade.php` | Dashboard genérico de Breeze; no se usa porque `/dashboard` redirige según el rol. |
| `profile/partials/*` | Parciales heredados de Breeze (`update-profile-information-form`, `update-password-form`, `delete-user-form`); ya no se incluyen en ninguna vista (código muerto) y no se restilaron. En cambio, `profile/edit.blade.php` —que antes los incluía— ya está escrito con el estilo glass propio del proyecto (tarjetas translúcidas, botones píldora, `backdrop-blur-xl` y modo oscuro). |
