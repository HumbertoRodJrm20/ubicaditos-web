<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Route, Stop, User, Trip};
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_buses' => Bus::count(),
            'active_buses' => Bus::where('status', 'active')->count(),
            'total_routes' => Route::count(),
            'total_stops' => Stop::count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'total_passengers' => User::where('role', 'passenger')->count(),
            'trips_today' => Trip::whereDate('started_at', today())->count(),
        ];

        $recentTrips = Trip::with(['bus', 'driver', 'route'])
            ->latest()
            ->limit(10)
            ->get();

        $activeBuses = Bus::with(['driver', 'routes'])
            ->where('status', 'active')
            ->get();

        // Agregar rutas y paradas para el mapa en tiempo real
        $routes = Route::with(['stops', 'buses' => function($query) {
            $query->where('status', 'active');
        }])->where('is_active', true)->get();

        $stops = Stop::where('is_active', true)->get();

        $buses = Bus::with(['driver', 'routes'])
            ->where('status', 'active')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTrips', 'activeBuses', 'routes', 'stops', 'buses'));
    }

    public function statistics()
    {
        $tripsByDay = Trip::selectRaw('DATE(started_at) as date, COUNT(*) as count')
            ->where('started_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->get();

        $busUsage = Bus::withCount(['trips' => function($query) {
            $query->where('started_at', '>=', now()->subDays(30));
        }])->get();

        $topDrivers = User::where('role', 'driver')
            ->withCount('trips')
            ->orderBy('trips_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.statistics', compact('tripsByDay', 'busUsage', 'topDrivers'));
    }

    public function drivers(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $drivers = User::where('role', 'driver')
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('maternal_last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with(['drivingBus', 'trips'])
            ->withCount('trips')
            ->withAvg('ratingsReceived', 'rating')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $availableBuses = Bus::whereNull('driver_id')->get();

        return view('admin.drivers', compact('drivers', 'availableBuses'));
    }

    public function assignBusToDriver(Request $request, User $driver)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
        ]);

        $bus = Bus::find($request->bus_id);

        // Unassign any previous driver from this bus
        if ($bus->driver_id) {
            $previousBus = Bus::where('driver_id', $bus->driver_id)->first();
            if ($previousBus) {
                $previousBus->update(['driver_id' => null]);
            }
        }

        // Unassign current driver's bus if any
        $currentBus = Bus::where('driver_id', $driver->id)->first();
        if ($currentBus) {
            $currentBus->update(['driver_id' => null]);
        }

        // Assign new driver
        $bus->update(['driver_id' => $driver->id]);

        return back()->with('success', 'Autobús asignado correctamente al conductor');
    }

    public function unassignBusFromDriver(User $driver)
    {
        $bus = $driver->drivingBus;

        if ($bus) {
            $bus->update(['driver_id' => null]);
        }

        return back()->with('success', 'Autobús desasignado del conductor');
    }

    public function passengers(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $passengers = User::where('role', 'passenger')
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('maternal_last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount(['ratingsGiven', 'favoriteStops'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.passengers', compact('passengers'));
    }
}
