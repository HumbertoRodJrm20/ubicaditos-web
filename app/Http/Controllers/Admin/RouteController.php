<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRouteRequest;
use App\Http\Requests\Admin\UpdateRouteRequest;
use App\Models\Route;
use App\Models\Stop;
use App\Services\RouteStopSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function __construct(private readonly RouteStopSyncService $stopSync)
    {
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $routes = Route::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
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
        $stops = Stop::orderBy('name')->get();

        return view('admin.routes.create', compact('stops'));
    }

    public function store(StoreRouteRequest $request)
    {
        $validated = $request->safe()->only([
            'name', 'code', 'description', 'color', 'estimated_duration', 'is_active',
        ]);
        $stopIds = $request->validate([
            'stops' => ['sometimes', 'array'],
            'stops.*' => ['integer', 'exists:stops,id', 'distinct'],
        ])['stops'] ?? [];

        DB::transaction(function () use ($validated, $stopIds) {
            $route = Route::create($validated);

            if ($stopIds !== []) {
                $this->stopSync->syncMainStops($route, $stopIds);
            }
        });

        return redirect()->route('admin.routes.index')
            ->with('success', 'Ruta creada exitosamente');
    }

    public function edit(Route $route)
    {
        // Primero las paradas de la ruta en su orden real y después el resto,
        // para que el formulario respete el order del pivote.
        $attached = $route->stops()->orderByPivot('order')->get();
        $remaining = Stop::whereNotIn('id', $attached->pluck('id'))
            ->orderBy('name')
            ->get();
        $stops = $attached->concat($remaining);

        return view('admin.routes.edit', compact('route', 'stops'));
    }

    public function update(UpdateRouteRequest $request, Route $route)
    {
        $validated = $request->safe()->only([
            'name', 'code', 'description', 'color', 'estimated_duration', 'is_active',
        ]);
        $stopIds = $request->validate([
            'stops' => ['sometimes', 'array'],
            'stops.*' => ['integer', 'exists:stops,id', 'distinct'],
        ])['stops'] ?? null;

        DB::transaction(function () use ($validated, $stopIds, $route) {
            $route->update($validated);

            if ($stopIds !== null) {
                $this->stopSync->syncMainStops($route, $stopIds);
            }
        });

        return redirect()->route('admin.routes.index')
            ->with('success', 'Ruta actualizada exitosamente');
    }

    public function destroy(Route $route)
    {
        if ($this->hasHistory($route)) {
            $route->update(['is_active' => false]);

            return back()->with('success', 'La ruta tiene historial; se desactivó en lugar de eliminarse.');
        }

        DB::transaction(function () use ($route) {
            $route->stops()->detach();
            $route->buses()->detach();
            $route->delete();
        });

        return back()->with('success', 'Ruta eliminada exitosamente');
    }

    /**
     * Los viajes, autobuses y valoraciones son histórico: borrar la ruta rompería
     * la integridad referencial de ese registro.
     */
    private function hasHistory(Route $route): bool
    {
        return $route->trips()->exists()
            || $route->buses()->exists()
            || $route->likes()->exists()
            || $route->favoritedByUsers()->exists();
    }
}
