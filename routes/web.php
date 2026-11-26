<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\DriverApprovalController;
use App\Http\Controllers\Admin\IncidentManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\RouteManagementController;
use App\Http\Controllers\Admin\StopController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\ArrivalEstimateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LocationShareController;
use App\Http\Controllers\Passenger\EmergencyController;
use App\Http\Controllers\Passenger\FavoriteRouteController;
use App\Http\Controllers\Passenger\NotificationSubscriptionController;
use App\Http\Controllers\Passenger\PassengerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard principal (redirige según rol)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/driver/pending', fn () => view('driver.pending'))
    ->middleware('auth')
    ->name('driver.pending');

// Rutas de Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    Route::post('drivers/{driver}/approve', [DriverApprovalController::class, 'approve'])->name('drivers.approve');
    Route::post('drivers/{driver}/reject', [DriverApprovalController::class, 'reject'])->name('drivers.reject');
    Route::post('drivers/{driver}/send-password-reset', [DriverApprovalController::class, 'sendPasswordReset'])->name('drivers.send-password-reset');

    // CRUD Buses
    Route::resource('buses', BusController::class);

    // Estudio de rutas y paradas (declarado antes del resource para que
    // "/routes/manage" no sea capturado como "{route}").
    Route::get('routes/manage', [RouteManagementController::class, 'index'])->name('routes.manage');
    Route::get('routes/manage/data', [RouteManagementController::class, 'data'])->name('routes.manage.data');
    Route::get('routes/{route}/stops', [RouteManagementController::class, 'stops'])->name('routes.stops.data');
    Route::post('routes/{route}/stops', [RouteManagementController::class, 'syncStops'])->name('routes.stops.sync');
    Route::post('routes/{route}/geometry', [RouteManagementController::class, 'regenerateGeometry'])->name('routes.geometry.regenerate');
    Route::patch('routes/{route}/status', [RouteManagementController::class, 'toggleStatus'])->name('routes.status');

    // CRUD Routes
    Route::resource('routes', RouteController::class);

    // CRUD Stops
    Route::get('stops/data', [StopController::class, 'data'])->name('stops.data');
    Route::patch('stops/{stop}/status', [StopController::class, 'toggleStatus'])->name('stops.status');
    Route::resource('stops', StopController::class);

    // CRUD Users
    Route::resource('users', UserController::class);

    // Reportes y estadísticas (vista única)
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    Route::redirect('statistics', '/admin/reports')->name('statistics');

    // Emergencias / incidencias
    Route::get('emergencies', [AdminController::class, 'emergencies'])->name('emergencies');
    Route::post('emergencies/{emergency}/resolve', [AdminController::class, 'resolveEmergency'])->name('emergencies.resolve');

    // Reportes de incidencias enviados por los usuarios
    Route::get('incidents', [IncidentManagementController::class, 'index'])->name('incidents.index');
    Route::patch('incidents/{incident}', [IncidentManagementController::class, 'update'])->name('incidents.update');

    // Driver Management
    Route::get('drivers', [AdminController::class, 'drivers'])->name('drivers');
    Route::post('drivers/{driver}/assign-bus', [AdminController::class, 'assignBusToDriver'])->name('drivers.assign-bus');
    Route::delete('drivers/{driver}/unassign-bus', [AdminController::class, 'unassignBusFromDriver'])->name('drivers.unassign-bus');

    // Passenger Management
    Route::get('passengers', [AdminController::class, 'passengers'])->name('passengers');
});

// Rutas de Driver
Route::middleware(['auth', 'driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('dashboard');
    Route::get('/map', [DriverController::class, 'map'])->name('map');
    Route::get('/statistics', [DriverController::class, 'statistics'])->name('statistics');
    Route::post('/start-trip', [DriverController::class, 'startTrip'])->name('start-trip');
    Route::post('/end-trip', [DriverController::class, 'endTrip'])->name('end-trip');
    Route::post('/update-location', [DriverController::class, 'updateLocation'])->name('update-location');
    Route::post('/update-passengers', [DriverController::class, 'updatePassengers'])->name('update-passengers');
    Route::post('/emergency', [DriverController::class, 'reportEmergency'])->name('emergency');
});

// Rutas de Passenger
Route::middleware(['auth', 'verified', 'passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    Route::get('/', [PassengerController::class, 'map'])->name('dashboard'); // Mapa como página principal
    Route::get('/menu', [PassengerController::class, 'index'])->name('menu'); // Menú de opciones
    Route::get('/routes', [PassengerController::class, 'routes'])->name('routes');
    Route::get('/buses', [PassengerController::class, 'buses'])->name('buses');
    Route::get('/search', [PassengerController::class, 'search'])->name('search');

    // Favorite Stops
    Route::post('/favorites/{stop}', [PassengerController::class, 'addFavorite'])->name('favorites.add');
    Route::delete('/favorites/{stop}', [PassengerController::class, 'removeFavorite'])->name('favorites.remove');

    // Favorite Routes
    Route::post('/favorite-routes/{route}', [FavoriteRouteController::class, 'add'])->name('favorite-routes.add');
    Route::patch('/favorite-routes/{route}', [FavoriteRouteController::class, 'update'])->name('favorite-routes.update');
    Route::delete('/favorite-routes/{route}', [FavoriteRouteController::class, 'remove'])->name('favorite-routes.remove');

    // Emergencias reportadas por pasajeros (RF-19)
    Route::get('/emergency', [EmergencyController::class, 'create'])->name('emergency.create');
    Route::post('/emergency', [EmergencyController::class, 'store'])->name('emergency.store');

    // Compartir ubicación en vivo
    Route::post('/share-location', [LocationShareController::class, 'start'])->name('share-location.start');
    Route::get('/share-location/current', [LocationShareController::class, 'current'])->name('share-location.current');
    Route::patch('/share-location/{share}', [LocationShareController::class, 'update'])->name('share-location.update');
    Route::delete('/share-location/{share}', [LocationShareController::class, 'stop'])->name('share-location.stop');

    // Avisos in-app bus/parada (HU-012)
    Route::get('/notifications', [NotificationSubscriptionController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/subscribe', [NotificationSubscriptionController::class, 'subscribe'])->name('notifications.subscribe');
    Route::delete('/notifications/{subscription}', [NotificationSubscriptionController::class, 'unsubscribe'])->name('notifications.unsubscribe');

    // Ratings
    Route::post('/rate/{driver}', [PassengerController::class, 'rateDriver'])->name('rate');
});

// Likes públicos de rutas y paradas (cualquier usuario autenticado)
Route::middleware('auth')->group(function () {
    Route::post('/likes/routes/{route}', [LikeController::class, 'toggleRoute'])->name('likes.routes.toggle');
    Route::post('/likes/stops/{stop}', [LikeController::class, 'toggleStop'])->name('likes.stops.toggle');
});

// API Routes for real-time data (RNF-02: roles declarados explícitamente)
Route::middleware(['auth', 'roles:admin,driver,passenger'])->prefix('api')->group(function () {
    Route::get('/buses/active', [DashboardController::class, 'getActiveBuses'])->name('api.buses.active');
    Route::get('/buses/{bus}/location', [DashboardController::class, 'getBusLocation'])->name('api.buses.location');
    Route::get('/routes/{route}/buses', [DashboardController::class, 'getRouteBuses'])->name('api.routes.buses');
    Route::get('/stops/{stop}/eta', [ArrivalEstimateController::class, 'index'])->name('api.stops.eta');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Reportes de incidencias del usuario autenticado
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
});

// Enlace público para compartir la ubicación (sin autenticación)
Route::get('/share/{token}', [LocationShareController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('share.show');

// Incidencias marcadas como públicas (sin identidad del reportante)
Route::get('/incidents/public', [IncidentController::class, 'publicIndex'])->name('incidents.public');

Route::get('/api/share/{token}', [LocationShareController::class, 'data'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('api.share.data');

require __DIR__.'/auth.php';
