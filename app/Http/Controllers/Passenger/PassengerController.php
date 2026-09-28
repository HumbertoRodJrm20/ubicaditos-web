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

    public function buses()
    {
        $buses = Bus::with(['driver', 'routes'])->get();
        return view('passenger.buses', compact('buses'));
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
