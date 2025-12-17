<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Route, Stop};
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $routes = Route::when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount(['buses', 'stops'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.routes.index', compact('routes'));
    }

    public function create()
    {
        $stops = Stop::all();
        return view('admin.routes.create', compact('stops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:routes,name',
            'code' => 'required|string|unique:routes,code',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:40|max:90',
            'is_active' => 'boolean',
        ], [
            'name.max' => 'El nombre no puede tener más de 30 caracteres',
            'name.unique' => 'Este nombre de ruta ya existe',
            'estimated_duration.min' => 'La duración estimada debe ser al menos de 40 minutos hasta 90 minutos',
            'estimated_duration.max' => 'La duración estimada no puede exceder los 40 minutos hasta 90 minutos',
        ]);

        $route = Route::create($validated);

        if ($request->has('stops')) {
            $stops = [];
            foreach ($request->stops as $index => $stopId) {
                $stops[$stopId] = ['order' => $index];
            }
            $route->stops()->sync($stops);
        }

        return redirect()->route('admin.routes.index')
            ->with('success', 'Ruta creada exitosamente');
    }

    public function edit(Route $route)
    {
        $stops = Stop::all();
        return view('admin.routes.edit', compact('route', 'stops'));
    }

    public function update(Request $request, Route $route)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30',
            'code' => 'required|string|unique:routes,code,' . $route->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:40|max:90',
            'is_active' => 'boolean',
        ], [
            'name.max' => 'El nombre no puede tener más de 30 caracteres',
            'estimated_duration.min' => 'La duración estimada debe ser al menos de 40 minutos',
            'estimated_duration.max' => 'La duración estimada no puede exceder los 90 minutos',
        ]);

        $route->update($validated);

        if ($request->has('stops')) {
            $stops = [];
            foreach ($request->stops as $index => $stopId) {
                $stops[$stopId] = ['order' => $index];
            }
            $route->stops()->sync($stops);
        }

        return redirect()->route('admin.routes.index')
            ->with('success', 'Ruta actualizada exitosamente');
    }

    public function destroy(Route $route)
    {
        $route->delete();
        return back()->with('success', 'Ruta eliminada exitosamente');
    }
}
