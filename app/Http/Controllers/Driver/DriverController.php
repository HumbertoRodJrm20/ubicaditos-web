<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StartTripRequest;
use App\Http\Requests\Emergency\StoreEmergencyReportRequest;
use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\Route;
use App\Models\Trip;
use App\Services\EmergencyReportService;
use App\Services\GeoDistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    /** Tope de lecturas GPS conservadas por viaje para evitar JSON ilimitado. */
    private const MAX_TRIP_PATH_POINTS = 2000;

    public function index()
    {
        $bus = auth()->user()->drivingBus;

        $currentTrip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        $recentTrips = Trip::where('driver_id', auth()->id())
            ->with(['route', 'bus'])
            ->latest()
            ->limit(10)
            ->get();

        $averageRating = auth()->user()->average_rating;

        return view('driver.dashboard', compact('bus', 'currentTrip', 'recentTrips', 'averageRating'));
    }

    public function map()
    {
        $currentTrip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        // RF-10: el conductor puede elegir entre sus autobuses asignados y los
        // disponibles, y entre las rutas vinculadas a cualquiera de ellos.
        $buses = Bus::where('status', 'active')
            ->where(function ($query) {
                $query->where('driver_id', auth()->id())
                    ->orWhereNull('driver_id');
            })
            ->orderBy('license_plate')
            ->get();

        // Durante un viaje se muestra la unidad realmente seleccionada, que
        // puede estar disponible sin estar asignada de forma permanente. Sin
        // viaje ni asignación, se propone la primera unidad disponible para que
        // el selector pueda utilizarse; si no hay ninguna, se mantiene el error.
        $bus = $currentTrip?->bus ?? auth()->user()->drivingBus ?? $buses->first();

        if (! $bus) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No tienes un autobús asignado');
        }

        $route = $currentTrip?->route ?? $bus->routes->first();
        // En el mapa del conductor se muestran sólo las paradas principales:
        // las de alta frecuencia son para el mapa de pasajero y saturarían la
        // lista de próximas paradas.
        $stops = $route ? $route->getOrderedStops(false) : collect();

        $routes = Route::whereHas('buses', function ($query) use ($buses) {
            $query->whereIn('buses.id', $buses->pluck('id'));
        })->with('buses:id')->orderBy('name')->get();

        return view('driver.map', compact('bus', 'route', 'stops', 'currentTrip', 'buses', 'routes'));
    }

    /**
     * Estadísticas del perfil del conductor: viajes, ocupación, calificaciones,
     * distancia, tiempo al volante e incidencias reportadas.
     */
    public function statistics()
    {
        $driver = auth()->user();

        $trips = Trip::where('driver_id', $driver->id)->with('bus')->get();
        $completed = $trips->where('status', 'completed');

        $occupancies = $completed
            ->filter(fn ($trip) => $trip->bus && $trip->bus->capacity > 0)
            ->map(fn ($trip) => min(100, round(($trip->total_passengers / $trip->bus->capacity) * 100, 2)));

        $stats = [
            'total_trips' => $trips->count(),
            'completed_trips' => $completed->count(),
            'active_trips' => $trips->where('status', 'in_progress')->count(),
            'total_passengers' => (int) $completed->sum('total_passengers'),
            'avg_occupancy' => $occupancies->isEmpty() ? 0.0 : round($occupancies->avg(), 2),
            'avg_rating' => round((float) $driver->ratingsReceived()->avg('rating'), 2),
            'ratings_count' => $driver->ratingsReceived()->count(),
            'total_distance_km' => round((float) $completed->sum('distance_km'), 2),
            'total_minutes' => (int) $completed->sum(
                fn ($trip) => $trip->started_at && $trip->completed_at
                    ? round($trip->started_at->diffInMinutes($trip->completed_at))
                    : 0
            ),
            'emergencies_reported' => EmergencyReport::where('reporter_id', $driver->id)->count(),
        ];

        $stats['avg_minutes'] = $stats['completed_trips'] > 0
            ? (int) round($stats['total_minutes'] / $stats['completed_trips'])
            : 0;

        $stats['by_day'] = Trip::selectRaw('DATE(started_at) as date, COUNT(*) as total')
            ->where('driver_id', $driver->id)
            ->where('started_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $stats['by_route'] = Trip::selectRaw('route_id, COUNT(*) as total')
            ->where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->groupBy('route_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('route')
            ->get();

        $recentRatings = $driver->ratingsReceived()
            ->with('passenger')
            ->latest()
            ->limit(5)
            ->get();

        return view('driver.statistics', compact('stats', 'recentRatings'));
    }

    public function startTrip(StartTripRequest $request)
    {
        $driver = auth()->user();

        // Check if driver is active
        if (! $driver->is_active) {
            return back()->with('error', 'Debes estar activo para iniciar un viaje. Contacta al administrador.');
        }

        $busId = $request->integer('bus_id');
        $routeId = $request->integer('route_id');

        $bus = $busId > 0
            ? Bus::whereKey($busId)
                ->where(function ($query) use ($driver) {
                    $query->where('driver_id', $driver->id)->orWhereNull('driver_id');
                })->first()
            : $driver->drivingBus;

        if (! $bus) {
            return $busId > 0
                ? back()->withErrors(['bus_id' => 'Ese autobús no está disponible para ti.'])
                    ->with('error', 'El autobús seleccionado no está disponible.')
                : back()->with('error', 'No tienes un autobús asignado');
        }

        $route = $routeId > 0
            ? Route::whereKey($routeId)
                ->whereHas('buses', fn ($query) => $query->where('buses.id', $bus->id))
                ->first()
            : $bus->routes()->first();

        if (! $route) {
            return $routeId > 0
                ? back()->withErrors(['route_id' => 'La ruta seleccionada no está asignada a ese autobús.'])
                    ->with('error', 'La ruta seleccionada no es válida para el autobús.')
                : back()->with('error', 'El autobús no tiene una ruta asignada');
        }

        // Check if there's already an active trip
        $activeTrip = Trip::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeTrip) {
            return back()
                ->withErrors(['trip' => 'Ya tienes un viaje en progreso.'])
                ->with('error', 'Ya tienes un viaje en progreso');
        }

        if (Trip::where('bus_id', $bus->id)->where('status', 'in_progress')->exists()) {
            return back()
                ->withErrors(['bus_id' => 'Ese autobús ya está en un viaje activo.'])
                ->with('error', 'El autobús seleccionado ya está en un viaje activo.');
        }

        // ===== VALIDACIÓN DE HORARIO =====
        $currentHour = now()->hour;
        $workShift = $driver->work_shift;

        // Definir horarios de turnos
        $shiftSchedules = [
            'morning' => ['start' => 6, 'end' => 14],    // 6am - 2pm
            'afternoon' => ['start' => 14, 'end' => 22], // 2pm - 10pm
            'night' => ['start' => 22, 'end' => 6],      // 10pm - 6am
        ];

        if ($workShift && isset($shiftSchedules[$workShift])) {
            $schedule = $shiftSchedules[$workShift];
            $isInShift = false;

            // Manejar turnos que cruzan medianoche (turno nocturno)
            if ($schedule['start'] > $schedule['end']) {
                $isInShift = $currentHour >= $schedule['start'] || $currentHour < $schedule['end'];
            } else {
                $isInShift = $currentHour >= $schedule['start'] && $currentHour < $schedule['end'];
            }

            if (! $isInShift) {
                $shiftNames = [
                    'morning' => 'matutino (6:00 - 14:00)',
                    'afternoon' => 'vespertino (14:00 - 22:00)',
                    'night' => 'nocturno (22:00 - 6:00)',
                ];

                return back()->with('error',
                    'No puedes iniciar un viaje fuera de tu horario asignado. '.
                    'Tu turno es '.($shiftNames[$workShift] ?? $workShift).'. '.
                    'Hora actual: '.now()->format('H:i')
                );
            }
        }

        // ===== VALIDACIÓN DE UBICACIÓN EN LA RUTA =====
        $driverLat = $bus->current_latitude;
        $driverLng = $bus->current_longitude;

        if (! $driverLat || ! $driverLng) {
            return back()->with('error',
                'No se ha detectado tu ubicación. Por favor, asegúrate de que el GPS esté activado y espera unos segundos.'
            );
        }

        // Obtener las paradas de la ruta
        $stops = $route->getOrderedStops();

        if ($stops->isEmpty()) {
            return back()->with('error', 'La ruta no tiene paradas definidas');
        }

        // Distancia a cada parada con la misma fórmula Haversine usada para
        // medir la distancia recorrida al finalizar el viaje.
        $geoDistance = app(GeoDistanceService::class);
        $minDistance = PHP_FLOAT_MAX;
        $closestStop = null;

        foreach ($stops as $stop) {
            $distance = $geoDistance->distanceKm(
                (float) $driverLat,
                (float) $driverLng,
                (float) $stop->latitude,
                (float) $stop->longitude,
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $stop;
            }
        }

        // Validar que esté dentro de 500 metros de alguna parada
        $maxDistanceKm = 0.5; // 500 metros

        if ($minDistance > $maxDistanceKm) {
            return back()->with('error',
                'No puedes iniciar el viaje porque no estás en tu ruta asignada. '.
                'Debes estar cerca (máximo 500 metros) de una parada de tu ruta. '.
                'Parada más cercana: '.$closestStop->name.' ('.round($minDistance * 1000).' metros)'
            );
        }

        // ===== TODO VÁLIDO - CREAR VIAJE =====
        $trip = Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        // El estado del autobús solo puede ser cambiado por el administrador
        // No se modifica al iniciar el viaje

        return back()->with('success',
            'Viaje iniciado correctamente. Parada inicial: '.$closestStop->name
        );
    }

    public function endTrip(Request $request)
    {
        $trip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (! $trip) {
            return back()->with('error', 'No tienes un viaje activo');
        }

        $bus = $trip->bus;
        $path = $trip->path_taken ?? [];

        $trip->update([
            'completed_at' => now(),
            'status' => 'completed',
            // Ocupación real del viaje (último conteo reportado por el conductor)
            'total_passengers' => (int) ($bus?->current_passengers ?? 0),
            // RF-12: distancia Haversine sobre las posiciones capturadas.
            'distance_km' => round(app(GeoDistanceService::class)->pathDistanceKm($path), 2),
            'path_taken' => $path !== [] ? $path : null,
        ]);

        // El estado del autobús solo puede ser cambiado por el administrador
        // No se modifica al finalizar el viaje

        return back()->with('success', 'Viaje finalizado correctamente');
    }

    public function updateLocation(Request $request)
    {
        $coordinates = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0|max:65535',
        ]);

        $trip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        // Si hay viaje activo, la posición pertenece a la unidad del viaje,
        // aunque el conductor tenga otra autobús asignado de forma permanente.
        $bus = $trip?->bus ?? auth()->user()->drivingBus;

        if (! $bus) {
            return response()->json(['error' => 'No bus assigned'], 400);
        }

        DB::transaction(function () use ($bus, $trip, $coordinates) {
            $bus->updateLocation(
                $coordinates['latitude'],
                $coordinates['longitude'],
                isset($coordinates['accuracy']) ? (int) $coordinates['accuracy'] : null
            );

            if ($trip === null) {
                return;
            }

            $path = $trip->path_taken ?? [];
            $path[] = [
                'latitude' => (float) $coordinates['latitude'],
                'longitude' => (float) $coordinates['longitude'],
                'recorded_at' => now()->toIso8601String(),
            ];

            if (count($path) > self::MAX_TRIP_PATH_POINTS) {
                $path = array_slice($path, -self::MAX_TRIP_PATH_POINTS);
            }

            $trip->update(['path_taken' => $path]);
        });

        return response()->json(['success' => true]);
    }

    public function updatePassengers(Request $request)
    {
        $request->validate([
            'passengers' => 'required|integer|min:0',
        ]);

        $bus = auth()->user()->drivingBus;

        if (! $bus) {
            return back()->with('error', 'No tienes un autobús asignado');
        }

        $bus->update([
            'current_passengers' => min($request->passengers, $bus->capacity),
        ]);

        return back()->with('success', 'Pasajeros actualizados');
    }

    public function reportEmergency(StoreEmergencyReportRequest $request, EmergencyReportService $service)
    {
        $service->report($request->user(), $request->validated());

        return back()->with('success', 'Emergencia reportada. Ayuda en camino.');
    }
}
