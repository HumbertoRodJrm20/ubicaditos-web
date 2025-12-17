<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Route;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Redirigir según el rol del usuario
        return match($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'driver' => redirect()->route('driver.dashboard'),
            'passenger' => redirect()->route('passenger.dashboard'), // Ahora dashboard es el mapa
            default => abort(403),
        };
    }

    // API Methods for real-time data
    public function getActiveBuses()
    {
        $buses = Bus::with(['driver', 'routes'])
            ->where('status', 'active')
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get()
            ->map(function ($bus) {
                return [
                    'id' => $bus->id,
                    'license_plate' => $bus->license_plate,
                    'latitude' => (float) $bus->current_latitude,
                    'longitude' => (float) $bus->current_longitude,
                    'capacity' => $bus->capacity,
                    'current_passengers' => $bus->current_passengers,
                    'available_seats' => $bus->available_seats,
                    'driver' => $bus->driver?->name,
                    'route' => $bus->routes->first()?->name,
                    'route_color' => $bus->routes->first()?->color,
                    'last_update' => $bus->last_location_update?->diffForHumans(),
                ];
            });

        return response()->json($buses);
    }

    public function getBusLocation(Bus $bus)
    {
        return response()->json([
            'id' => $bus->id,
            'latitude' => (float) $bus->current_latitude,
            'longitude' => (float) $bus->current_longitude,
            'status' => $bus->status,
            'current_passengers' => $bus->current_passengers,
            'last_update' => $bus->last_location_update,
        ]);
    }

    public function getRouteBuses(Route $route)
    {
        $buses = $route->buses()
            ->where('status', 'active')
            ->with('driver')
            ->get();

        return response()->json($buses);
    }
}
