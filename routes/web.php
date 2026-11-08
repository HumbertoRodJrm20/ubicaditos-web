<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LocationShareController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\IncidentManagementController;
use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\Passenger\FavoriteRouteController;
use App\Http\Controllers\Passenger\PassengerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard principal (redirige según rol)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Rutas de Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // CRUD Buses
    Route::resource('buses', \App\Http\Controllers\Admin\BusController::class);

    // CRUD Routes
    Route::resource('routes', \App\Http\Controllers\Admin\RouteController::class);

    // CRUD Stops
    Route::resource('stops', \App\Http\Controllers\Admin\StopController::class);

    // CRUD Users
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

    // Statistics
    Route::get('statistics', [AdminController::class, 'statistics'])->name('statistics');

    // Reports
    Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports');
    Route::get('reports/export-pdf', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('reports/export-excel', [\App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('reports.export-excel');

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
Route::middleware(['auth', 'passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    Route::get('/', [PassengerController::class, 'map'])->name('dashboard'); // Mapa como página principal
    Route::get('/menu', [PassengerController::class, 'index'])->name('menu'); // Menú de opciones
    Route::get('/routes', [PassengerController::class, 'routes'])->name('routes');
    Route::get('/buses', [PassengerController::class, 'buses'])->name('buses');

    // Favorite Stops
    Route::post('/favorites/{stop}', [PassengerController::class, 'addFavorite'])->name('favorites.add');
    Route::delete('/favorites/{stop}', [PassengerController::class, 'removeFavorite'])->name('favorites.remove');

    // Favorite Routes
    Route::post('/favorite-routes/{route}', [FavoriteRouteController::class, 'add'])->name('favorite-routes.add');
    Route::delete('/favorite-routes/{route}', [FavoriteRouteController::class, 'remove'])->name('favorite-routes.remove');

    // Compartir ubicación en vivo
    Route::post('/share-location', [LocationShareController::class, 'start'])->name('share-location.start');
    Route::get('/share-location/current', [LocationShareController::class, 'current'])->name('share-location.current');
    Route::patch('/share-location/{share}', [LocationShareController::class, 'update'])->name('share-location.update');
    Route::delete('/share-location/{share}', [LocationShareController::class, 'stop'])->name('share-location.stop');

    // Ratings
    Route::post('/rate/{driver}', [PassengerController::class, 'rateDriver'])->name('rate');
});

// Likes públicos de rutas y paradas (cualquier usuario autenticado)
Route::middleware('auth')->group(function () {
    Route::post('/likes/routes/{route}', [LikeController::class, 'toggleRoute'])->name('likes.routes.toggle');
    Route::post('/likes/stops/{stop}', [LikeController::class, 'toggleStop'])->name('likes.stops.toggle');
});

// API Routes for real-time data (accessible by all authenticated users)
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/buses/active', [DashboardController::class, 'getActiveBuses'])->name('api.buses.active');
    Route::get('/buses/{bus}/location', [DashboardController::class, 'getBusLocation'])->name('api.buses.location');
    Route::get('/routes/{route}/buses', [DashboardController::class, 'getRouteBuses'])->name('api.routes.buses');
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

Route::get('/api/share/{token}', [LocationShareController::class, 'data'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('api.share.data');

require __DIR__.'/auth.php';
