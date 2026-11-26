# Rutas y API

Las rutas web se definen en `routes/web.php` y las de autenticación en `routes/auth.php` (incluido al final de `web.php`). Laravel registra además la ruta de salud `GET /up`, configurada en `bootstrap/app.php`.

Para listar todas las rutas registradas:

```bash
php artisan route:list --except-vendor
```

## Grupos de middleware

| Middleware | Efecto |
|------------|--------|
| `guest` | Solo usuarios no autenticados (login, registro, recuperación). |
| `auth` | Requiere sesión iniciada; si no, redirige a `/login`. |
| `verified` | Requiere correo verificado cuando `EMAIL_VERIFICATION_ENABLED=true`. Con el valor actual (`false`) el middleware deja pasar la petición; `User` implementa `MustVerifyEmail`, pero no se envían avisos ni se bloquea a cuentas con correos ficticios. |
| `admin` / `driver` / `passenger` | Requiere el rol correspondiente; si no, responde 403. El middleware `driver` además redirige a `driver.pending` cuando la aprobación está `pending` o `rejected`. |
| `SetLocale`, `ForcePasswordChange` | Middleware global del grupo `web`: aplican `locale` persistido (`es`/`en`) y fuerzan `profile.edit` mientras `must_change_password` esté activo. |
| `roles:admin,driver,passenger` | Middleware `EnsureUserHasRole`: declara explícitamente los roles permitidos por endpoint (RNF-02); cualquier otro rol recibe 403. |
| `signed`, `throttle:6,1` | Firma de URL y límite de 6 peticiones por minuto (verificación de correo). |

## Rutas generales

| Método | URI | Nombre | Acción | Middleware |
|--------|-----|--------|--------|------------|
| GET | `/` | — | Redirige a `login` | — |
| GET | `/dashboard` | `dashboard` | `DashboardController@index` | `auth`, `verified` |
| GET | `/profile` | `profile.edit` | `ProfileController@edit` | `auth` |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` | `auth` |
| DELETE | `/profile` | `profile.destroy` | `ProfileController@destroy` | `auth` |
| GET | `/up` | — | Health check de Laravel | — |

## Rutas de reportes ciudadanos

El prefijo `/incidents` usa middleware `auth` (cualquier rol autenticado) salvo `/incidents/public`, que es público. Cada usuario ve y crea únicamente sus propios reportes privados.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/incidents` | `incidents.index` | `IncidentController@index` | — |
| GET | `/incidents/create` | `incidents.create` | `IncidentController@create` | — |
| POST | `/incidents` | `incidents.store` | `IncidentController@store` | `category` (route, bus, service, driver, stop, other), `description` (máx. 1000), `route_id`, `bus_id`, `latitude`, `longitude`, `is_public` (todos opcionales salvo los dos primeros) |
| GET | `/incidents/public` | `incidents.public` | `IncidentController@publicIndex` | — |

El estado inicial siempre es `pending` y el dueño se toma de la sesión; el formulario no puede enviar `status`, `user_id` ni `resolved_at`. La vista pública sólo muestra reportes con `is_public = true` y nunca incluye la identidad del autor.

## Rutas del administrador

Prefijo `/admin`, nombre `admin.*`, middleware `auth` + `admin`.

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/admin` | `admin.dashboard` | `AdminController@index` |
| GET | `/admin/drivers` | `admin.drivers` | `AdminController@drivers` |
| POST | `/admin/drivers/{driver}/assign-bus` | `admin.drivers.assign-bus` | `AdminController@assignBusToDriver` |
| DELETE | `/admin/drivers/{driver}/unassign-bus` | `admin.drivers.unassign-bus` | `AdminController@unassignBusFromDriver` |
| POST | `/admin/drivers/{driver}/approve` | `admin.drivers.approve` | `DriverApprovalController@approve` |
| POST | `/admin/drivers/{driver}/reject` | `admin.drivers.reject` | `DriverApprovalController@reject` |
| POST | `/admin/drivers/{driver}/send-password-reset` | `admin.drivers.send-password-reset` | `DriverApprovalController@sendPasswordReset` |
| GET | `/admin/passengers` | `admin.passengers` | `AdminController@passengers` |
| GET | `/admin/emergencies` | `admin.emergencies` | `AdminController@emergencies` |
| POST | `/admin/emergencies/{emergency}/resolve` | `admin.emergencies.resolve` | `AdminController@resolveEmergency` |
| GET | `/admin/incidents` | `admin.incidents.index` | `IncidentManagementController@index` |
| PATCH | `/admin/incidents/{incident}` | `admin.incidents.update` | `IncidentManagementController@update` |
| GET | `/admin/reports` | `admin.reports` | `ReportController@index` |
| GET | `/admin/reports/export-pdf` | `admin.reports.export-pdf` | `ReportController@exportPdf` |
| GET | `/admin/reports/export-excel` | `admin.reports.export-excel` | `ReportController@exportExcel` |
| GET | `/admin/statistics` | `admin.statistics` | Redirección a `admin.reports` (vista única de reportes y estadísticas) |

Las rutas de incidencias aceptan los parámetros de consulta `status` (`pending` | `in_progress` | `resolved`) y `type` (`accident` | `breakdown` | `medical` | `security` | `other`). La acción `resolve` solo aplica a incidencias **pendientes**; al resolverla, si el autobús asociado estaba en estado `emergency`, vuelve a `active`.

Los **reportes ciudadanos** (`admin.incidents.*`) aceptan `status` (`pending` | `in_review` | `resolved`), `category` (`route` | `bus` | `service` | `driver` | `stop` | `other`) y la búsqueda `q` (descripción o datos del autor). La acción `update` recibe `status`; al pasar a `resolved` fija `resolved_at`, y al reabrir el reporte lo limpia.

### Recursos CRUD

Cada `Route::resource()` registra las siete rutas REST estándar:

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/admin/{recurso}` | `admin.{recurso}.index` | `index` |
| GET | `/admin/{recurso}/create` | `admin.{recurso}.create` | `create` |
| POST | `/admin/{recurso}` | `admin.{recurso}.store` | `store` |
| GET | `/admin/{recurso}/{id}` | `admin.{recurso}.show` | `show` |
| GET | `/admin/{recurso}/{id}/edit` | `admin.{recurso}.edit` | `edit` |
| PUT/PATCH | `/admin/{recurso}/{id}` | `admin.{recurso}.update` | `update` |
| DELETE | `/admin/{recurso}/{id}` | `admin.{recurso}.destroy` | `destroy` |

| Recurso | Controlador | Acciones implementadas |
|---------|-------------|------------------------|
| `buses` | `Admin\BusController` | `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` |
| `routes` | `Admin\RouteController` | `index`, `create`, `store`, `edit`, `update`, `destroy` |
| `users` | `Admin\UserController` | `create`, `store`, `edit`, `update`, `destroy` |
| `stops` | `Admin\StopController` | `index`, `create`, `show`, `edit` redirigen al Estudio; `store`, `update`, `destroy`, `toggleStatus`, `data` |

`admin.buses.show` (`GET /admin/buses/{bus}`) es la única vista `show` de recurso; el índice de autobuses incluye un enlace "Detalles" hacia él. Las acciones `show` de `routes` y `users`, e `index` de `users`, siguen sin vista propia. Las acciones de lectura del recurso `stops` redirigen al Estudio de rutas y paradas.

Las eliminaciones administrativas respetan historial: una ruta con viajes, autobuses asignados, likes o favoritos se desactiva; un autobús con viaje activo o conductor asignado no se elimina, y si tiene historial se desactiva; un conductor con viaje activo no se desactiva y, al retirarlo, se preservan sus viajes/calificaciones y se libera su autobús. Solo las entidades sin referencias protegidas se eliminan físicamente. El borrado propio de un pasajero mantiene el flujo de cuenta de Breeze; un conductor se desactiva y cierra su sesión.

### Datos del Estudio de rutas y paradas

El Estudio y sus endpoints JSON pertenecen al grupo `auth` + `admin`; no usan `/api`.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/admin/routes/manage` | `admin.routes.manage` | `RouteManagementController@index` | — |
| GET | `/admin/routes/manage/data` | `admin.routes.manage.data` | `RouteManagementController@data` | — |
| GET | `/admin/routes/{route}/stops` | `admin.routes.stops.data` | `RouteManagementController@stops` | Ruta seleccionada |
| GET | `/admin/stops/data` | `admin.stops.data` | `StopController@data` | `q` (nombre/dirección, máx. 100), `stop_id` (opcional), `page` (≥ 1), `per_page` (1–50; predeterminado 50) |

`GET /admin/stops/data` responde con un paginador JSON de Laravel; cada elemento incluye los datos de la parada, `route_ids` y URLs administrativas. `stop_id` permite resolver un enlace directo a una parada sin cargar el catálogo completo. La búsqueda se solicita desde el campo del Estudio y el botón «Cargar más paradas» consulta páginas adicionales. El valor máximo de `per_page` es 50 y un valor superior devuelve 422.

El payload inicial `GET /admin/routes/manage/data` contiene como máximo **200 paradas principales distintas**, es decir, con al menos una asociación `route_stop.is_frequent = false`. Cada resumen de ruta solo incluye sus paradas principales y un `stop_count`; al seleccionar una ruta, `GET /admin/routes/{route}/stops` carga su secuencia completa, incluidas las paradas frecuentes y sus URLs de edición. Así, la capa inicial del mapa nunca coloca más de 200 marcadores, y las frecuentes solo aparecen al seleccionar una ruta. Esta mejora no requiere cambios de esquema.

Los formularios heredados `admin/routes/create` y `admin/routes/edit` se conservan como fallback de metadatos de ruta, pero ya no consultan ni renderizan el catálogo de paradas. Indican que las asignaciones y el orden se gestionan en el Estudio; al editar una ruta, el enlace conserva su ID seleccionado. Las acciones `store/update` siguen aceptando `stops[]` para compatibilidad, aunque los formularios no lo envían; omitirlo en una actualización conserva las asignaciones actuales.

Las secuencias detalladas se cargan en el orden combinado de `route_stop.order`, con `route_stop.id` como desempate estable. El Estudio muestra los minutos desde la parada principal anterior y alerta de cambios sin guardar; al abandonar la ruta o recargar, confirma antes de descartarlos. Los movimientos de marcadores siguen siendo locales hasta guardar. La generación de geometría ocurre solo al presionar «Regenerar trazado» y conserva la geometría anterior si OSRM falla.

La indicación de revisión de paradas frecuentes aparece tras guardar un cambio que las afecta, pero no se persiste entre recargas: persistirla requiere una migración aún pendiente de aprobación del propietario. RNF-05 sigue **parcial** porque faltan mediciones/guardas de consultas en otros listados principales; no se atribuye a las vistas heredadas un límite de catálogo ya corregido.

Cobertura: `tests/Feature/Admin/StopCatalogTest.php` (autorización, búsqueda, paginación, lookup por ID, límites y datos bajo demanda); `tests/Feature/Admin/RouteCrudCharacterizationTest.php` (formularios sin consultas al catálogo y orden combinado); `tests/Feature/Admin/RouteStudioManagementTest.php` (OSRM solo en acción explícita y preservación ante error); `tests/Feature/Admin/StopManagementTest.php` cubre el CRUD relacionado.

## Rutas del conductor

Prefijo `/driver`, nombre `driver.*`, middleware `auth` + `driver`. La ruta pública de aviso `GET /driver/pending` (`driver.pending`, middleware `auth`) sectoriza las solicitudes `pending`/`rejected` antes de permitir el área operativa.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/driver` | `driver.dashboard` | `DriverController@index` | — |
| GET | `/driver/map` | `driver.map` | `DriverController@map` | — |
| GET | `/driver/statistics` | `driver.statistics` | `DriverController@statistics` | — |
| POST | `/driver/start-trip` | `driver.start-trip` | `DriverController@startTrip` | `bus_id`, `route_id` (opcionales; enteros existentes) |
| POST | `/driver/end-trip` | `driver.end-trip` | `DriverController@endTrip` | — |
| POST | `/driver/update-location` | `driver.update-location` | `DriverController@updateLocation` | `latitude` (−90…90), `longitude` (−180…180) |
| POST | `/driver/update-passengers` | `driver.update-passengers` | `DriverController@updatePassengers` | `passengers` (entero ≥ 0) |
| POST | `/driver/emergency` | `driver.emergency` | `DriverController@reportEmergency` | `type` (`accident`, `breakdown`, `medical`, `security`, `other`), `description` (≤ 500) |

RF-10: el selector del mapa ofrece autobuses propios o disponibles y solo rutas vinculadas a la unidad elegida; el servidor repite la validación. RF-12: cada actualización de GPS se conserva en `trips.path_taken` y `end-trip` calcula `distance_km` con `GeoDistanceService` sin migraciones nuevas.

## Rutas del pasajero

Prefijo `/passenger`, nombre `passenger.*`, middleware `auth` + `verified` + `passenger`. Un pasajero sin correo verificado es redirigido a `verification.notice`.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/passenger` | `passenger.dashboard` | `PassengerController@map` | — |
| GET | `/passenger/menu` | `passenger.menu` | `PassengerController@index` | — |
| GET | `/passenger/routes` | `passenger.routes` | `PassengerController@routes` | — |
| GET | `/passenger/buses` | `passenger.buses` | `PassengerController@buses` | `q` (placa, modelo o conductor), `route_id`, `status` (por defecto `active`; `all` muestra todos), `sort` (`plate` \| `occupancy`) |
| POST | `/passenger/favorites/{stop}` | `passenger.favorites.add` | `PassengerController@addFavorite` | `nickname` (opcional) |
| DELETE | `/passenger/favorites/{stop}` | `passenger.favorites.remove` | `PassengerController@removeFavorite` | — |
| POST | `/passenger/favorite-routes/{route}` | `passenger.favorite-routes.add` | `FavoriteRouteController@add` | — |
| DELETE | `/passenger/favorite-routes/{route}` | `passenger.favorite-routes.remove` | `FavoriteRouteController@remove` | — |
| POST | `/passenger/share-location` | `passenger.share-location.start` | `LocationShareController@start` | `latitude`, `longitude`, `accuracy` (opcional), `bus_id` (opcional) |
| GET | `/passenger/share-location/current` | `passenger.share-location.current` | `LocationShareController@current` | — |
| PATCH | `/passenger/share-location/{share}` | `passenger.share-location.update` | `LocationShareController@update` | `latitude`, `longitude`, `accuracy` (opcional), `bus_id` (opcional) |
| DELETE | `/passenger/share-location/{share}` | `passenger.share-location.stop` | `LocationShareController@stop` | — |
| GET | `/passenger/notifications` | `passenger.notifications.index` | `NotificationSubscriptionController@index` | — |
| POST | `/passenger/notifications/subscribe` | `passenger.notifications.subscribe` | `NotificationSubscriptionController@subscribe` | `bus_id`, `stop_id` |
| DELETE | `/passenger/notifications/{subscription}` | `passenger.notifications.unsubscribe` | `NotificationSubscriptionController@unsubscribe` | — |
| POST | `/passenger/rate/{driver}` | `passenger.rate` | `PassengerController@rateDriver` | `rating` (1–5), `comment` (opcional), `trip_id` (opcional; debe ser un viaje completado con ese conductor) |

`RateDriverRequest` exige que el objetivo tenga rol `driver` y que el viaje asociado esté `completed` y pertenezca a ese conductor. Si el cliente omite `trip_id`, se usa el último viaje completado del conductor; si no hay ninguno, la petición se rechaza con un error de validación en `trip_id`.

## Rutas de me gusta

Middleware `auth` (cualquier rol autenticado). El estado se alterna en cada llamada.

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| POST | `/likes/routes/{route}` | `likes.routes.toggle` | `LikeController@toggleRoute` |
| POST | `/likes/stops/{stop}` | `likes.stops.toggle` | `LikeController@toggleStop` |

## Rutas públicas de ubicación compartida

Sin sesión. Devuelven 404 si el token no existe, caducó o fue revocado. La respuesta JSON contiene solo `active`, `latitude`, `longitude`, `accuracy` y `expires_at`: nunca expone nombre, correo ni ID del pasajero (RNF-02).

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/share/{token}` | `share.show` | `LocationShareController@show` (HTML) |
| GET | `/api/share/{token}` | `api.share.data` | `LocationShareController@data` (JSON) |

## Rutas de autenticación

| Método | URI | Nombre | Middleware |
|--------|-----|--------|------------|
| GET / POST | `/register` | `register` | `guest` |
| GET / POST | `/register-driver` | `driver.register.create` | `guest` |
| POST | `/check-email` | `check-email` | `guest` |
| GET / POST | `/login` | `login` | `guest` |
| GET / POST | `/forgot-password` | `password.request` / `password.email` | `guest` |
| GET | `/reset-password/{token}` | `password.reset` | `guest` |
| POST | `/reset-password` | `password.store` | `guest` |
| GET | `/verify-email` | `verification.notice` | `auth` |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | `auth`, `signed`, `throttle:6,1` |
| POST | `/email/verification-notification` | `verification.send` | `auth`, `throttle:6,1` |
| GET / POST | `/confirm-password` | `password.confirm` | `auth` |
| PUT | `/password` | `password.update` | `auth` |
| POST | `/logout` | `logout` | `auth` |

El registro de pasajeros envía `VerifyEmail` y redirige a `verification.notice`; el registro público de conductores crea una solicitud `pending` con licencia y foto privada, y redirige a `login`. El reenvío de verificación usa `throttle:6,1`.

El cierre de sesión exige `POST` con token CSRF. Antes de destruir la sesión, el controlador revoca los enlaces de ubicación activos del usuario, invalida los datos de sesión y regenera el token CSRF. Si quien sale es un conductor con viaje `in_progress`, el viaje se marca `completed` con `completed_at` y el autobús queda sin conductor y en estado `inactive`; los enlaces confirmados en el perfil dejan claro este efecto.

## Endpoints JSON

Prefijo `/api`, middleware `auth` + `roles:admin,driver,passenger`. Están definidos en `routes/web.php`, por lo que usan **autenticación por sesión** (cookie), no tokens: solo funcionan desde el navegador de un usuario autenticado y con uno de esos tres roles (matriz declarada en RNF-02). La ruta pública `/api/share/{token}` queda fuera del grupo autenticado.

### GET /api/buses/active

Nombre: `api.buses.active`. Devuelve los autobuses con `status = active` que tienen coordenadas.

Además del consumo directo, este endpoint es el **fallback de polling** del módulo `resources/js/realtime.js`: cuando el WebSocket (Laravel Reverb) no está disponible, se consulta cada 5 segundos y cada elemento recibido por esta vía se etiqueta con `source: "polling"`.

```json
[
  {
    "id": 1,
    "license_plate": "OAX-001-A",
    "latitude": 17.0654,
    "longitude": -96.7256,
    "capacity": 45,
    "current_passengers": 12,
    "available_seats": 33,
    "driver": "Juan Pérez",
    "route": "Ruta 1 - Centro - Tule",
    "route_color": "#3B82F6",
    "last_update": "hace 2 minutos",
    "fresh": true,
    "fresh_until": "2026-11-25T15:35:10.000000Z",
    "accuracy": 12,
    "sharing_passengers_count": 3
  }
]
```

> `available_seats` es un accessor calculado del modelo `Bus` (`capacidad − ocupación actual`, nunca negativo). `fresh`/`fresh_until` aplican la regla de cinco minutos calculada en servidor; `accuracy` es opcional y `sharing_passengers_count` es un conteo agregado sin identidades. Ver [Tiempo real y broadcasting](#tiempo-real-y-broadcasting) para el payload normalizado que emite el módulo en tiempo real.

### GET /api/buses/{bus}/location

Nombre: `api.buses.location`.

```json
{
  "id": 1,
  "latitude": 17.0654,
  "longitude": -96.7256,
  "status": "active",
  "current_passengers": 12,
  "last_update": "2026-10-06T14:32:10.000000Z",
  "fresh": true,
  "fresh_until": "2026-10-06T14:37:10.000000Z",
  "accuracy": null,
  "sharing_passengers_count": 0
}
```

### GET /api/routes/{route}/buses

Nombre: `api.routes.buses`. Devuelve los modelos `Bus` activos de la ruta, serializados completos, con la relación `driver` cargada, el pivote `bus_route` y los atributos derivados `fresh`, `fresh_until`, `accuracy` y `sharing_passengers_count`.

### GET /api/stops/{stop}/eta

Nombre: `api.stops.eta`. Devuelve la estimación de llegada por autobús activo que atiende la parada. No consulta OSRM en tiempo de petición.

```json
{
  "stop_id": 7,
  "stop_name": "Parada ETA",
  "generated_at": "2026-11-25T15:30:10.000000Z",
  "estimated": true,
  "uncertain": false,
  "notice": null,
  "buses": [
    {
      "id": 1,
      "license_plate": "ABC123",
      "route_id": 1,
      "route_name": "Ruta 1",
      "route_color": "#3B82F6",
      "eta_minutes": 7,
      "distance_km": 2.31,
      "last_update": "2026-11-25T15:29:10.000000Z",
      "fresh": true,
      "estimated": true,
      "uncertain": false
    }
  ]
}
```

Si la posición tiene más de cinco minutos, `fresh` es `false`, `uncertain` es `true` y `eta_minutes` es `null`; si no hay autobuses, `buses` está vacío, `estimated` es `false` y `notice` contiene el aviso aproximado. La fórmula usa Haversine en línea recta, 20 km/h y redondeo al alza (ver [desviaciones](requirements-deviations.md)).

### POST /driver/update-location

Usado por el mapa del conductor. Requiere la cabecera `X-CSRF-TOKEN`.

```http
POST /driver/update-location
Content-Type: application/json
X-CSRF-TOKEN: <token>

{ "latitude": 17.0654, "longitude": -96.7256, "accuracy": 12 }
```

`accuracy` es opcional (entero de metros entre 0 y 65535). Los clientes que no lo envían conservan el contrato anterior y el valor se guarda como `null`.

| Respuesta | Cuerpo |
|-----------|--------|
| 200 | `{"success": true}` |
| 400 | `{"error": "No bus assigned"}` |
| 422 | Errores de validación |

### POST / DELETE /passenger/favorites/{stop}

Cuando la petición incluye `Accept: application/json`, responde:

| Caso | Código | Cuerpo |
|------|--------|--------|
| Agregada | 200 | `{"success": true, "message": "Parada agregada a favoritos"}` |
| Ya existía | 400 | `{"error": "Esta parada ya está en tus favoritos"}` |
| Eliminada | 200 | `{"success": true, "message": "Parada eliminada de favoritos"}` |

### POST / DELETE /passenger/favorite-routes/{route}

| Caso | Código | Cuerpo |
|------|--------|--------|
| Agregada | 200 | `{"success": true, "message": "Ruta agregada a favoritos"}` |
| Ya existía | 400 | `{"error": "Esta ruta ya está en tus favoritos"}` |
| Eliminada | 200 | `{"success": true, "message": "Ruta eliminada de favoritos"}` |

### POST /likes/routes/{route} y POST /likes/stops/{stop}

Alternan el me gusta del usuario autenticado. Responden en JSON cuando la petición lo espera:

```json
{ "liked": true, "count": 12 }
```

`liked` indica el estado resultante para el usuario y `count` es el total público de la entidad. El índice único de `likes` impide duplicados.

### Enlaces de ubicación compartida

| Método y ruta | Respuesta |
|---------------|-----------|
| `POST /passenger/share-location` | `{"active": true, "id": 1, "token": "…48 caracteres…", "bus_id": 3, "url": "http://…/share/…", "expires_at": "…", "accuracy": 12}` |
| `GET /passenger/share-location/current` | `{"active": true, "share": {…}}` o `{"active": false}` |
| `PATCH /passenger/share-location/{share}` | `{"active": true, "share": {…}}`; 403 si el enlace es de otro usuario |
| `DELETE /passenger/share-location/{share}` | `{"active": false}`; 403 si el enlace es de otro usuario |
| `GET /api/share/{token}` | `{"active": true, "latitude": …, "longitude": …, "accuracy": …, "expires_at": "…"}`; 404 si caducó o fue revocado |

Al iniciar un enlace nuevo se revoca el anterior del mismo usuario. La caducidad por defecto es de **30 minutos**. El payload público no incluye nombre, correo ni ID del pasajero; `bus_id` sólo aparece en las respuestas autenticadas del dueño y alimenta el conteo agregado del mapa.

### Suscripciones in-app bus/parada

| Método y ruta | Respuesta |
|---------------|-----------|
| `GET /passenger/notifications` | `{"subscriptions": [{"id": 1, "bus_id": 3, "license_plate": "ABC123", "stop_id": 7, "stop_name": "Centro"}]}` |
| `POST /passenger/notifications/subscribe` | `{"subscribed": true, "id": 1, "bus_id": 3, "stop_id": 7}`; repetir la misma pareja reutiliza el registro. |
| `DELETE /passenger/notifications/{subscription}` | `{"subscribed": false}`; 403 si pertenece a otro pasajero; repetirlo es inofensivo. |

`bus_id` y `stop_id` deben existir. No se envía correo, SMS ni push externo; el aviso es una preferencia in-app (ver [desviaciones](requirements-deviations.md)).

### PATCH /admin/incidents/{incident}

Recibe `status` (`pending`, `in_review`, `resolved`). Responde con redirección y mensaje flash; `resolved_at` se fija al resolver y se limpia al reabrir.

### POST /check-email

Usado por el formulario de registro. Recibe `email` y responde `{"available": true}` o `{"available": false}`.

## Tiempo real y broadcasting

### Rutas de autorización de broadcasting

Laravel registra automáticamente (no aparecen en `routes/web.php`) las rutas de suscripción a canales, con el middleware `web`:

| Método | URI | Nombre | Acción | Middleware |
|--------|-----|--------|--------|------------|
| GET / POST | `/broadcasting/auth` | — | `BroadcastController@authenticate` | `web` |

El navegador las usa para autorizar la suscripción a canales privados. La única canal definido está en `routes/channels.php`:

| Canal | Política |
|-------|----------|
| `private-buses` | Cualquier usuario autenticado (cualquier rol), igual que los endpoints JSON `/api/*`. |

### Evento broadcast

Cuando un conductor envía su posición (`POST /driver/update-location`), el modelo `Bus::updateLocation()` despacha el evento `App\Events\BusLocationUpdated` (`ShouldBroadcastNow`, se publica sin worker de colas) en el canal `buses` con el alias `bus.location.updated`:

```json
{
  "id": 1,
  "license_plate": "ABC123",
  "latitude": 17.0654,
  "longitude": -96.7256,
  "status": "active",
  "current_passengers": 12,
  "available_seats": 33,
  "route_id": 1,
  "last_update": "2026-10-18T14:32:10.000000Z",
  "fresh": true,
  "fresh_until": "2026-10-18T14:37:10.000000Z",
  "accuracy": 12,
  "sharing_passengers_count": 3
}
```

`fresh`, `fresh_until`, `accuracy` y `sharing_passengers_count` se calculan en servidor con la misma regla que el polling y la vista Blade. El cliente (`resources/js/realtime.js`, importado desde `resources/js/app.js`) normaliza este evento y los elementos de `GET /api/buses/active` a un mismo payload:

```json
{
  "id": 1,
  "license_plate": "ABC123",
  "latitude": 17.0654,
  "longitude": -96.7256,
  "status": "active",
  "current_passengers": 12,
  "available_seats": 33,
  "route_id": 1,
  "last_update": "hace 2 minutos",
  "fresh": true,
  "fresh_until": "2026-10-18T14:37:10.000000Z",
  "accuracy": 12,
  "sharing_passengers_count": 3,
  "stale": false,
  "source": "websocket",
  "received_at": "2026-10-18T14:32:12.003Z"
}
```

`stale` se deriva en el cliente de `fresh`/`fresh_until` como salvaguarda; la decisión definitiva la toma el servidor. `source` indica el origen del dato (`websocket` o `polling`) y `received_at` el momento en que el cliente lo recibió. El módulo arranca con una descarga HTTP inicial, intenta conectar el WebSocket y, si no conecta dentro de 4 s (o se desconecta), mantiene el polling de `/api/buses/active` cada 5 s; el estado resultante (`websocket`, `polling`, `offline`) lo exponen sus suscriptores.

### Configuración de Laravel Reverb

Variables de entorno (`.env` / `.env.example`), con su contraparte pública para Vite:

| Variable | Uso |
|----------|-----|
| `BROADCAST_CONNECTION=reverb` | Conector de broadcasting activo. |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Credenciales de la aplicación Reverb. |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Servidor WebSocket (`localhost:8080`, `http` en desarrollo). |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | Valores expuestos al navegador (referenciados desde `realtime.js` con `import.meta.env`). |

La configuración del servidor vive en `config/reverb.php`; si falta `VITE_REVERB_APP_KEY`, el módulo `realtime.js` se queda solo en polling.
