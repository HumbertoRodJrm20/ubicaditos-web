<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Trip, EmergencyReport};
use Illuminate\Http\Request;

class DriverController extends Controller
{
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
        $bus = auth()->user()->drivingBus;

        if (!$bus) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No tienes un autobús asignado');
        }

        $route = $bus->routes->first();
        $stops = $route ? $route->getOrderedStops() : collect();

        $currentTrip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        return view('driver.map', compact('bus', 'route', 'stops', 'currentTrip'));
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

    public function startTrip(Request $request)
    {
        $driver = auth()->user();

        // Check if driver is active
        if (!$driver->is_active) {
            return back()->with('error', 'Debes estar activo para iniciar un viaje. Contacta al administrador.');
        }

        $bus = $driver->drivingBus;

        if (!$bus) {
            return back()->with('error', 'No tienes un autobús asignado');
        }

        $route = $bus->routes->first();

        if (!$route) {
            return back()->with('error', 'El autobús no tiene una ruta asignada');
        }

        // Check if there's already an active trip
        $activeTrip = Trip::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeTrip) {
            return back()->with('error', 'Ya tienes un viaje en progreso');
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

            if (!$isInShift) {
                $shiftNames = [
                    'morning' => 'matutino (6:00 - 14:00)',
                    'afternoon' => 'vespertino (14:00 - 22:00)',
                    'night' => 'nocturno (22:00 - 6:00)',
                ];

                return back()->with('error',
                    'No puedes iniciar un viaje fuera de tu horario asignado. ' .
                    'Tu turno es ' . ($shiftNames[$workShift] ?? $workShift) . '. ' .
                    'Hora actual: ' . now()->format('H:i')
                );
            }
        }

        // ===== VALIDACIÓN DE UBICACIÓN EN LA RUTA =====
        $driverLat = $bus->current_latitude;
        $driverLng = $bus->current_longitude;

        if (!$driverLat || !$driverLng) {
            return back()->with('error',
                'No se ha detectado tu ubicación. Por favor, asegúrate de que el GPS esté activado y espera unos segundos.'
            );
        }

        // Obtener las paradas de la ruta
        $stops = $route->getOrderedStops();

        if ($stops->isEmpty()) {
            return back()->with('error', 'La ruta no tiene paradas definidas');
        }

        // Calcular distancia a cada parada (fórmula de Haversine simplificada)
        $minDistance = PHP_FLOAT_MAX;
        $closestStop = null;

        foreach ($stops as $stop) {
            // Fórmula de Haversine para calcular distancia en km
            $earthRadius = 6371; // km

            $latDiff = deg2rad($stop->latitude - $driverLat);
            $lngDiff = deg2rad($stop->longitude - $driverLng);

            $a = sin($latDiff / 2) * sin($latDiff / 2) +
                 cos(deg2rad($driverLat)) * cos(deg2rad($stop->latitude)) *
                 sin($lngDiff / 2) * sin($lngDiff / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestStop = $stop;
            }
        }

        // Validar que esté dentro de 500 metros de alguna parada
        $maxDistanceKm = 0.5; // 500 metros

        if ($minDistance > $maxDistanceKm) {
            return back()->with('error',
                'No puedes iniciar el viaje porque no estás en tu ruta asignada. ' .
                'Debes estar cerca (máximo 500 metros) de una parada de tu ruta. ' .
                'Parada más cercana: ' . $closestStop->name . ' (' . round($minDistance * 1000) . ' metros)'
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
            'Viaje iniciado correctamente. Parada inicial: ' . $closestStop->name
        );
    }

    public function endTrip(Request $request)
    {
        $trip = Trip::where('driver_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (!$trip) {
            return back()->with('error', 'No tienes un viaje activo');
        }

        $bus = auth()->user()->drivingBus;

        $trip->update([
            'completed_at' => now(),
            'status' => 'completed',
            // Ocupación real del viaje (último conteo reportado por el conductor)
            'total_passengers' => (int) ($bus?->current_passengers ?? 0),
        ]);

        // El estado del autobús solo puede ser cambiado por el administrador
        // No se modifica al finalizar el viaje

        return back()->with('success', 'Viaje finalizado correctamente');
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $bus = auth()->user()->drivingBus;

        if (!$bus) {
            return response()->json(['error' => 'No bus assigned'], 400);
        }

        $bus->updateLocation(
            $request->latitude,
            $request->longitude
        );

        return response()->json(['success' => true]);
    }

    public function updatePassengers(Request $request)
    {
        $request->validate([
            'passengers' => 'required|integer|min:0',
        ]);

        $bus = auth()->user()->drivingBus;

        if (!$bus) {
            return back()->with('error', 'No tienes un autobús asignado');
        }

        $bus->update([
            'current_passengers' => min($request->passengers, $bus->capacity)
        ]);

        return back()->with('success', 'Pasajeros actualizados');
    }

    public function reportEmergency(Request $request)
    {
        $request->validate([
            'type' => 'required|in:accident,breakdown,medical,security,other',
            'description' => 'required|string|max:500',
        ]);

        $bus = auth()->user()->drivingBus;

        EmergencyReport::create([
            'reporter_id' => auth()->id(),
            'bus_id' => $bus?->id,
            'type' => $request->type,
            'description' => $request->description,
            'latitude' => $bus?->current_latitude,
            'longitude' => $bus?->current_longitude,
            'status' => 'pending',
        ]);

        if ($bus) {
            $bus->update(['status' => 'emergency']);
        }

        return back()->with('success', 'Emergencia reportada. Ayuda en camino.');
    }
}
