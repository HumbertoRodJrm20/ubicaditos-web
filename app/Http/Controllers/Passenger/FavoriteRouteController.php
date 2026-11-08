<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Route;

class FavoriteRouteController extends Controller
{
    /**
     * Guarda una ruta en los favoritos del pasajero.
     */
    public function add(Route $route)
    {
        $user = auth()->user();

        if ($user->favoriteRoutes()->where('route_id', $route->id)->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Esta ruta ya está en tus favoritos'], 400);
            }

            return back()->with('error', 'Esta ruta ya está en tus favoritos');
        }

        $user->favoriteRoutes()->attach($route->id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'favorite' => true,
                'message' => 'Ruta agregada a favoritos',
            ]);
        }

        return back()->with('success', 'Ruta agregada a favoritos');
    }

    /**
     * Quita una ruta de los favoritos del pasajero.
     */
    public function remove(Route $route)
    {
        auth()->user()->favoriteRoutes()->detach($route->id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'favorite' => false,
                'message' => 'Ruta eliminada de favoritos',
            ]);
        }

        return back()->with('success', 'Ruta eliminada de favoritos');
    }
}
