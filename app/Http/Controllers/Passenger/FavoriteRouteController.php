<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\FavoriteRoute;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteRouteController extends Controller
{
    /**
     * Guarda una ruta en los favoritos del pasajero, hasta un máximo de cinco.
     */
    public function add(Route $route)
    {
        $result = DB::transaction(function () use ($route) {
            $user = auth()->user();
            $count = $user->favoriteRoutes()->count();

            if ($count >= FavoriteRoute::MAX_FAVORITES) {
                return 'limit';
            }

            if ($user->favoriteRoutes()->where('routes.id', $route->id)->exists()) {
                return 'duplicate';
            }

            $user->favoriteRoutes()->attach($route->id, ['position' => $count + 1]);

            return 'added';
        });

        if ($result === 'limit') {
            $error = 'Solo puedes guardar '.FavoriteRoute::MAX_FAVORITES.' rutas favoritas.';

            if (request()->expectsJson()) {
                return response()->json(['error' => $error], 422);
            }

            return back()->with('error', $error);
        }

        if ($result === 'duplicate') {
            $error = 'Esta ruta ya está en tus favoritos';

            if (request()->expectsJson()) {
                return response()->json(['error' => $error], 400);
            }

            return back()->with('error', $error);
        }

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
     * Reordena y/o etiqueta una ruta favorita del pasajero autenticado.
     */
    public function update(Request $request, Route $route)
    {
        $user = $request->user();
        $favorite = $user->favoriteRoutes()->where('routes.id', $route->id)->firstOrFail();
        $total = $user->favoriteRoutes()->count();

        $validated = $request->validate([
            'position' => [
                'required',
                'integer',
                'min:1',
                'max:'.$total,
            ],
            'label' => [
                'nullable',
                'string',
                'max:'.FavoriteRoute::MAX_LABEL_LENGTH,
            ],
        ], [
            'position.required' => 'Indica la nueva posición.',
            'position.integer' => 'La posición debe ser un número entero.',
            'position.min' => 'La posición mínima es 1.',
            'position.max' => 'La posición no puede superar tus rutas favoritas.',
            'label.string' => 'La etiqueta debe ser texto.',
            'label.max' => 'La etiqueta no puede superar los '
                .FavoriteRoute::MAX_LABEL_LENGTH.' caracteres.',
        ]);

        $position = (int) $validated['position'];
        $label = array_key_exists('label', $validated)
            ? $validated['label']
            : $favorite->pivot->label;

        DB::transaction(function () use ($user, $route, $position, $label) {
            $orderedIds = $user->favoriteRoutes()
                ->where('routes.id', '!=', $route->id)
                ->pluck('routes.id');

            $orderedIds->splice($position - 1, 0, $route->id);

            foreach ($orderedIds as $index => $routeId) {
                $attributes = ['position' => $index + 1];

                if ((int) $routeId === $route->id) {
                    $attributes['label'] = $label;
                }

                DB::table('favorite_routes')
                    ->where('user_id', $user->id)
                    ->where('route_id', $routeId)
                    ->update($attributes);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'position' => $position,
                'label' => $label,
                'message' => 'Preferencias guardadas',
            ]);
        }

        return back()->with('success', 'Preferencias guardadas');
    }

    /**
     * Quita una ruta y compacta la numeración restante.
     */
    public function remove(Route $route)
    {
        DB::transaction(function () use ($route) {
            $user = auth()->user();
            $user->favoriteRoutes()->detach($route->id);

            $remainingIds = $user->favoriteRoutes()->pluck('routes.id');

            foreach ($remainingIds as $index => $routeId) {
                DB::table('favorite_routes')
                    ->where('user_id', $user->id)
                    ->where('route_id', $routeId)
                    ->update(['position' => $index + 1]);
            }
        });

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
