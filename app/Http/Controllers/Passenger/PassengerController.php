<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Route, Stop, Rating};
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    public function index()
    {
        $favoriteStops = auth()->user()->favoriteStops;

        return view('passenger.menu', compact('favoriteStops'));
    }

    public function map()
    {
        $routes = Route::with(['stops', 'buses' => function($query) {
            $query->where('status', 'active');
        }])->where('is_active', true)->get();

        $stops = Stop::where('is_active', true)->get();
        $buses = Bus::with(['driver', 'routes'])->where('status', 'active')->get();

        return view('passenger.dashboard', compact('routes', 'stops', 'buses'));
    }

    public function routes()
    {
        $routes = Route::with(['stops', 'buses'])->where('is_active', true)->get();
        return view('passenger.routes', compact('routes'));
    }

    /**
     * Listado de autobuses con búsqueda y filtrado.
     * Parámetros: q (placa, modelo o conductor), route_id, status (active|all|...) y sort (plate|occupancy).
     */
    public function buses(Request $request)
    {
        $status = $request->query('status', 'active');
        $search = trim((string) $request->query('q'));
        $routeId = $request->query('route_id');
        $sort = $request->query('sort', 'plate');

        $buses = Bus::with(['driver', 'routes'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('license_plate', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('driver', function ($driverQuery) use ($search) {
                            $driverQuery->where(function ($q) use ($search) {
                                $q->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('maternal_last_name', 'like', "%{$search}%");
                            });
                        });
                });
            })
            ->when($routeId, fn ($query) => $query->whereHas(
                'routes',
                fn ($routes) => $routes->where('routes.id', $routeId)
            ))
            ->when(
                $sort === 'occupancy',
                fn ($query) => $query->orderByDesc('current_passengers'),
                fn ($query) => $query->orderBy('license_plate')
            )
            ->get();

        $routes = Route::where('is_active', true)->get();

        // Datos completos y verificables para el modal de detalles (Requisito 4)
        $busDetailsData = $buses->map(fn ($bus) => [
            'id' => $bus->id,
            'plate' => $bus->license_plate ?? '—',
            'model' => $bus->model ?? 'Modelo no especificado',
            'capacity' => $bus->capacity,
            'passengers' => $bus->current_passengers ?? 0,
            'available_seats' => $bus->available_seats,
            'status' => $bus->status,
            'route' => $bus->routes->first()?->name ?? 'Sin ruta asignada',
            'route_code' => $bus->routes->first()?->code ?? '',
            'route_color' => $bus->routes->first()?->color ?? '#6b7bad',
            'driver' => $bus->driver?->name ?? 'Sin conductor asignado',
            'last_update' => $bus->last_location_update?->diffForHumans() ?? 'Sin datos de ubicación',
            'map_url' => route('passenger.dashboard', ['bus_id' => $bus->id]),
        ])->values();

        return view('passenger.buses', compact('buses', 'routes', 'busDetailsData'));
    }

    public function addFavorite(Stop $stop)
    {
        // Check if already favorited
        if (auth()->user()->favoriteStops()->where('stop_id', $stop->id)->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Esta parada ya está en tus favoritos'], 400);
            }
            return back()->with('error', 'Esta parada ya está en tus favoritos');
        }

        auth()->user()->favoriteStops()->attach($stop->id, [
            'nickname' => request('nickname'),
        ]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Parada agregada a favoritos']);
        }

        return back()->with('success', 'Parada agregada a favoritos');
    }

    public function removeFavorite(Stop $stop)
    {
        auth()->user()->favoriteStops()->detach($stop->id);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Parada eliminada de favoritos']);
        }

        return back()->with('success', 'Parada eliminada de favoritos');
    }

    public function rateDriver(Request $request, $driverId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        Rating::create([
            'passenger_id' => auth()->id(),
            'driver_id' => $driverId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Calificación enviada correctamente');
    }
}
