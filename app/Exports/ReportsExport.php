<?php

namespace App\Exports;

use App\Models\{Trip, User, Bus, Route};
use Illuminate\Support\Collection;

class ReportsExport
{
    public function collection()
    {
        $data = new Collection();

        // Sección: Viajes Recientes
        $data->push(['VIAJES RECIENTES (ÚLTIMOS 30 DÍAS)']);
        $data->push(['ID', 'Conductor', 'Autobús', 'Ruta', 'Inicio', 'Fin', 'Duración (min)', 'Pasajeros', 'Estado']);

        $trips = Trip::with(['driver', 'bus', 'route'])
            ->where('started_at', '>=', now()->subDays(30))
            ->orderBy('started_at', 'desc')
            ->get();

        foreach ($trips as $trip) {
            $duration = $trip->completed_at
                ? $trip->started_at->diffInMinutes($trip->completed_at)
                : 'En progreso';

            $data->push([
                $trip->id,
                $trip->driver->name ?? 'N/A',
                $trip->bus->license_plate ?? 'N/A',
                $trip->route->name ?? 'N/A',
                $trip->started_at->format('d/m/Y H:i'),
                $trip->completed_at ? $trip->completed_at->format('d/m/Y H:i') : 'En progreso',
                $duration,
                $trip->total_passengers ?? 'N/A',
                $trip->status === 'completed' ? 'Completado' : 'En progreso',
            ]);
        }

        // Espacio
        $data->push([]);
        $data->push([]);

        // Sección: Conductores
        $data->push(['CONDUCTORES']);
        $data->push(['ID', 'Nombre', 'Email', 'Viajes Realizados', 'Calificación Promedio']);

        $drivers = User::where('role', 'driver')
            ->withCount('trips')
            ->withAvg('ratingsReceived', 'rating')
            ->get();

        foreach ($drivers as $driver) {
            $data->push([
                $driver->id,
                $driver->name,
                $driver->email,
                $driver->trips_count,
                $driver->ratings_received_avg_rating
                    ? number_format($driver->ratings_received_avg_rating, 2)
                    : 'Sin calificación',
            ]);
        }

        // Espacio
        $data->push([]);
        $data->push([]);

        // Sección: Autobuses
        $data->push(['AUTOBUSES']);
        $data->push(['ID', 'Placa', 'Modelo', 'Capacidad', 'Conductor Asignado', 'Ruta Asignada', 'Viajes Realizados', 'Estado']);

        $buses = Bus::with(['driver', 'routes'])
            ->withCount('trips')
            ->get();

        foreach ($buses as $bus) {
            $data->push([
                $bus->id,
                $bus->license_plate,
                $bus->model,
                $bus->capacity,
                $bus->driver->name ?? 'Sin asignar',
                $bus->routes->first()->name ?? 'Sin ruta',
                $bus->trips_count,
                $bus->status === 'active' ? 'Activo' : 'Inactivo',
            ]);
        }

        // Espacio
        $data->push([]);
        $data->push([]);

        // Sección: Rutas
        $data->push(['RUTAS']);
        $data->push(['ID', 'Código', 'Nombre', 'Descripción', 'Paradas', 'Viajes Realizados', 'Duración Estimada (min)', 'Estado']);

        $routes = Route::withCount(['stops', 'trips'])->get();

        foreach ($routes as $route) {
            $data->push([
                $route->id,
                $route->code,
                $route->name,
                $route->description,
                $route->stops_count,
                $route->trips_count,
                $route->estimated_duration,
                $route->is_active ? 'Activa' : 'Inactiva',
            ]);
        }

        return $data;
    }
}
