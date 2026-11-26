<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRouteStopsRequest;
use App\Models\Route;
use App\Models\Stop;
use App\Services\RouteGeometryService;
use App\Services\RouteStopSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orquesta el Estudio de rutas y paradas. La lógica de dominio vive en los
 * servicios; este controlador sólo traduce HTTP al dominio y viceversa.
 */
class RouteManagementController extends Controller
{
    public function __construct(
        private readonly RouteStopSyncService $stopSync,
        private readonly RouteGeometryService $geometry,
    ) {}

    public function index()
    {
        return view('admin.routes.manage', ['studio' => $this->payload()]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function stops(Route $route): JsonResponse
    {
        $stops = $route->stops()
            ->orderByPivot('order')
            ->orderByPivot('id')
            ->get()
            ->map(fn (Stop $stop) => [
                'id' => $stop->id,
                'name' => $stop->name,
                'address' => $stop->address,
                'latitude' => (float) $stop->latitude,
                'longitude' => (float) $stop->longitude,
                'is_active' => $stop->is_active,
                'pivot' => [
                    'order' => (int) $stop->pivot->order,
                    'estimated_time_from_previous' => $stop->pivot->estimated_time_from_previous === null
                        ? null
                        : (int) $stop->pivot->estimated_time_from_previous,
                    'is_frequent' => (bool) $stop->pivot->is_frequent,
                ],
                'urls' => [
                    'update' => route('admin.stops.update', $stop),
                    'destroy' => route('admin.stops.destroy', $stop),
                    'status' => route('admin.stops.status', $stop),
                ],
            ])
            ->all();

        return response()->json(['stops' => $stops]);
    }

    public function syncStops(SyncRouteStopsRequest $request, Route $route): JsonResponse
    {
        try {
            $result = $this->stopSync->syncMainStops(
                $route,
                $request->input('stops', []),
                $request->input('minutes', [])
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function regenerateGeometry(Route $route): JsonResponse
    {
        $result = $this->geometry->generate($route, true);

        if ($result['status'] === 'failed') {
            return response()->json($result, 502);
        }

        return response()->json($result);
    }

    public function toggleStatus(Request $request, Route $route): JsonResponse|RedirectResponse
    {
        $route->update(['is_active' => ! $route->is_active]);
        $message = $route->is_active ? 'Ruta activada.' : 'Ruta desactivada.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'is_active' => (bool) $route->is_active,
            ]);
        }

        return redirect()->route('admin.routes.manage')->with('success', $message);
    }

    /**
     * Payload mínimo para el mapa: nada de usuarios, conductores ni datos
     * sensibles. Las rutas incluyen sus paradas ordenadas y el trazado ya
     * persistido para no volver a consultar OSRM en cada carga.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $routes = Route::withCount('stops')
            ->with(['stops' => fn ($query) => $query->wherePivot('is_frequent', false)->orderByPivot('order')->orderByPivot('id')])
            ->orderBy('code')
            ->get()
            ->map(fn (Route $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'code' => $route->code,
                'description' => $route->description,
                'color' => $route->color,
                'estimated_duration' => $route->estimated_duration,
                'is_active' => $route->is_active,
                'stop_count' => (int) $route->stops_count,
                'has_path' => count($route->path_coordinates ?? []) >= 2,
                'path_coordinates' => $route->path_coordinates ?? [],
                'stops' => $route->stops->map(fn (Stop $stop) => [
                    'id' => $stop->id,
                    'name' => $stop->name,
                    'latitude' => (float) $stop->latitude,
                    'longitude' => (float) $stop->longitude,
                    'is_active' => $stop->is_active,
                    'pivot' => [
                        'order' => (int) $stop->pivot->order,
                        'estimated_time_from_previous' => $stop->pivot->estimated_time_from_previous === null
                            ? null
                            : (int) $stop->pivot->estimated_time_from_previous,
                        'is_frequent' => (bool) $stop->pivot->is_frequent,
                    ],
                ])->all(),
                'urls' => [
                    'stops' => route('admin.routes.stops.data', $route),
                    'sync' => route('admin.routes.stops.sync', $route),
                    'geometry' => route('admin.routes.geometry.regenerate', $route),
                    'status' => route('admin.routes.status', $route),
                    'edit' => route('admin.routes.edit', $route),
                    'destroy' => route('admin.routes.destroy', $route),
                ],
            ])->all();

        $mainStopIds = DB::table('route_stop')
            ->select('stop_id')
            ->where('is_frequent', false)
            ->distinct();

        $stops = Stop::with('routes:id')
            ->whereIn('id', $mainStopIds)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn (Stop $stop) => [
                'id' => $stop->id,
                'name' => $stop->name,
                'address' => $stop->address,
                'latitude' => (float) $stop->latitude,
                'longitude' => (float) $stop->longitude,
                'is_active' => $stop->is_active,
                'route_ids' => $stop->routes->pluck('id')->all(),
                'urls' => [
                    'update' => route('admin.stops.update', $stop),
                    'destroy' => route('admin.stops.destroy', $stop),
                    'status' => route('admin.stops.status', $stop),
                ],
            ])->all();

        return [
            'routes' => $routes,
            'stops' => $stops,
            'endpoints' => [
                'data' => route('admin.routes.manage.data'),
                'routes' => [
                    'store' => route('admin.routes.store'),
                ],
                'stops' => [
                    'store' => route('admin.stops.store'),
                    'data' => route('admin.stops.data'),
                ],
            ],
        ];
    }
}
