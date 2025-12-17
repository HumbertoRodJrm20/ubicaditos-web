<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Route, User};
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $buses = Bus::when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('license_plate', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhereHas('driver', function($driverQuery) use ($search) {
                          $driverQuery->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%")
                                      ->orWhere('maternal_last_name', 'like', "%{$search}%");
                      });
                });
            })
            ->with(['routes', 'driver'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.buses.index', compact('buses'));
    }

    public function create()
    {
        $routes = Route::all();
        // Solo mostrar conductores que no tienen autobús asignado
        $drivers = User::where('role', 'driver')
            ->whereDoesntHave('drivingBus')
            ->get();
        return view('admin.buses.create', compact('routes', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|size:6|unique:buses,license_plate|regex:/^[A-Z]{3}[0-9]{3}$/',
            'model' => 'required|string|in:Foton AUV,Yutong,Mercedes-Benz,Dina,Toyota,Honda',
            'capacity' => 'required|integer|min:30|max:50',
            'driver_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,maintenance',
        ], [
            'license_plate.size' => 'La placa debe tener exactamente 6 caracteres',
            'license_plate.regex' => 'La placa debe tener el formato: 3 letras seguidas de 3 números (Ej: ABC123)',
            'model.in' => 'Debe seleccionar un modelo válido',
            'capacity.required' => 'Se necesita que se asigne capacidad',
            'capacity.integer' => 'La capacidad debe ser un número entero',
            'capacity.min' => 'La capacidad mínima es de 30 pasajeros',
            'capacity.max' => 'La capacidad máxima es de 50 pasajeros',
        ]);

        $bus = Bus::create($validated);

        if ($request->has('routes')) {
            $bus->routes()->sync($request->routes);
        }

        return redirect()->route('admin.buses.index')
            ->with('success', 'Autobús creado exitosamente');
    }

    public function edit(Bus $bus)
    {
        $routes = Route::all();
        // Mostrar conductores sin autobús asignado + el conductor actual del autobús (si tiene)
        $drivers = User::where('role', 'driver')
            ->where(function($query) use ($bus) {
                $query->whereDoesntHave('drivingBus')
                      ->orWhere('id', $bus->driver_id);
            })
            ->get();
        return view('admin.buses.edit', compact('bus', 'routes', 'drivers'));
    }

    public function update(Request $request, Bus $bus)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|size:6|unique:buses,license_plate,' . $bus->id . '|regex:/^[A-Z]{3}[0-9]{3}$/',
            'model' => 'required|string|in:Foton AUV,Yutong,Mercedes-Benz,Dina,Toyota,Honda',
            'capacity' => 'required|integer|min:30|max:50',
            'driver_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,maintenance',
        ], [
            'license_plate.size' => 'La placa debe tener exactamente 6 caracteres',
            'license_plate.regex' => 'La placa debe tener el formato: 3 letras seguidas de 3 números (Ej: ABC123)',
            'model.in' => 'Debe seleccionar un modelo válido',
            'capacity.required' => 'Se necesita que se asigne capacidad',
            'capacity.integer' => 'La capacidad debe ser un número entero',
            'capacity.min' => 'La capacidad mínima es de 30 pasajeros',
            'capacity.max' => 'La capacidad máxima es de 50 pasajeros',
        ]);

        $bus->update($validated);

        if ($request->has('routes')) {
            $bus->routes()->sync($request->routes);
        }

        return redirect()->route('admin.buses.index')
            ->with('success', 'Autobús actualizado exitosamente');
    }

    public function destroy(Bus $bus)
    {
        $bus->delete();
        return back()->with('success', 'Autobús eliminado exitosamente');
    }
}
