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
| `verified` | Requiere correo verificado. Como `User` no implementa `MustVerifyEmail`, en la práctica no bloquea. |
| `admin` / `driver` / `passenger` | Requiere el rol correspondiente; si no, responde 403. |
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

## Rutas del administrador

Prefijo `/admin`, nombre `admin.*`, middleware `auth` + `admin`.

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/admin` | `admin.dashboard` | `AdminController@index` |
| GET | `/admin/statistics` | `admin.statistics` | `AdminController@statistics` |
| GET | `/admin/drivers` | `admin.drivers` | `AdminController@drivers` |
| POST | `/admin/drivers/{driver}/assign-bus` | `admin.drivers.assign-bus` | `AdminController@assignBusToDriver` |
| DELETE | `/admin/drivers/{driver}/unassign-bus` | `admin.drivers.unassign-bus` | `AdminController@unassignBusFromDriver` |
| GET | `/admin/passengers` | `admin.passengers` | `AdminController@passengers` |
| GET | `/admin/reports` | `admin.reports` | `ReportController@index` |
| GET | `/admin/reports/export-pdf` | `admin.reports.export-pdf` | `ReportController@exportPdf` |
| GET | `/admin/reports/export-excel` | `admin.reports.export-excel` | `ReportController@exportExcel` |

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
| `buses` | `Admin\BusController` | `index`, `create`, `store`, `edit`, `update`, `destroy` |
| `routes` | `Admin\RouteController` | `index`, `create`, `store`, `edit`, `update`, `destroy` |
| `users` | `Admin\UserController` | `create`, `store`, `edit`, `update`, `destroy` |
| `stops` | `Admin\StopController` | Ninguna (métodos vacíos) |

Las acciones no implementadas (`show` en todos los recursos e `index` en `users`) están registradas pero producen un error si se visitan.

## Rutas del conductor

Prefijo `/driver`, nombre `driver.*`, middleware `auth` + `driver`.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/driver` | `driver.dashboard` | `DriverController@index` | — |
| GET | `/driver/map` | `driver.map` | `DriverController@map` | — |
| POST | `/driver/start-trip` | `driver.start-trip` | `DriverController@startTrip` | — |
| POST | `/driver/end-trip` | `driver.end-trip` | `DriverController@endTrip` | — |
| POST | `/driver/update-location` | `driver.update-location` | `DriverController@updateLocation` | `latitude`, `longitude` (numéricos) |
| POST | `/driver/update-passengers` | `driver.update-passengers` | `DriverController@updatePassengers` | `passengers` (entero ≥ 0) |
| POST | `/driver/emergency` | `driver.emergency` | `DriverController@reportEmergency` | `type`, `description` |

## Rutas del pasajero

Prefijo `/passenger`, nombre `passenger.*`, middleware `auth` + `passenger`.

| Método | URI | Nombre | Acción | Parámetros |
|--------|-----|--------|--------|------------|
| GET | `/passenger` | `passenger.dashboard` | `PassengerController@map` | — |
| GET | `/passenger/menu` | `passenger.menu` | `PassengerController@index` | — |
| GET | `/passenger/routes` | `passenger.routes` | `PassengerController@routes` | — |
| GET | `/passenger/buses` | `passenger.buses` | `PassengerController@buses` | — |
| POST | `/passenger/favorites/{stop}` | `passenger.favorites.add` | `PassengerController@addFavorite` | `nickname` (opcional) |
| DELETE | `/passenger/favorites/{stop}` | `passenger.favorites.remove` | `PassengerController@removeFavorite` | — |
| POST | `/passenger/rate/{driver}` | `passenger.rate` | `PassengerController@rateDriver` | `rating` (1–5), `comment` (opcional) |

## Rutas de autenticación

| Método | URI | Nombre | Middleware |
|--------|-----|--------|------------|
| GET / POST | `/register` | `register` | `guest` |
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

## Endpoints JSON

Prefijo `/api`, middleware `auth`. Están definidos en `routes/web.php`, por lo que usan **autenticación por sesión** (cookie), no tokens: solo funcionan desde el navegador de un usuario autenticado y con cualquier rol.

### GET /api/buses/active

Nombre: `api.buses.active`. Devuelve los autobuses con `status = active` que tienen coordenadas.

```json
[
  {
    "id": 1,
    "license_plate": "OAX-001-A",
    "latitude": 17.0654,
    "longitude": -96.7256,
    "capacity": 45,
    "current_passengers": 12,
    "available_seats": null,
    "driver": "Juan Pérez",
    "route": "Ruta 1 - Centro - Tule",
    "route_color": "#3B82F6",
    "last_update": "hace 2 minutos"
  }
]
```

> `available_seats` siempre es `null` porque el modelo `Bus` no define ese atributo.

### GET /api/buses/{bus}/location

Nombre: `api.buses.location`.

```json
{
  "id": 1,
  "latitude": 17.0654,
  "longitude": -96.7256,
  "status": "active",
  "current_passengers": 12,
  "last_update": "2026-10-06T14:32:10.000000Z"
}
```

### GET /api/routes/{route}/buses

Nombre: `api.routes.buses`. Devuelve los modelos `Bus` activos de la ruta, serializados completos, con la relación `driver` cargada y el pivote `bus_route`.

### POST /driver/update-location

Usado por el mapa del conductor. Requiere la cabecera `X-CSRF-TOKEN`.

```http
POST /driver/update-location
Content-Type: application/json
X-CSRF-TOKEN: <token>

{ "latitude": 17.0654, "longitude": -96.7256 }
```

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

### POST /check-email

Usado por el formulario de registro. Recibe `email` y responde `{"available": true}` o `{"available": false}`.
