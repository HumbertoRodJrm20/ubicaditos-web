<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStopRequest;
use App\Http\Requests\Admin\UpdateStopRequest;
use App\Models\Stop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StopController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.routes.manage');
    }

    public function create()
    {
        return redirect()->route('admin.routes.manage', ['panel' => 'stops', 'create' => 1]);
    }

    public function store(StoreStopRequest $request)
    {
        $stop = Stop::create($request->safe()->only([
            'name', 'address', 'latitude', 'longitude', 'description', 'is_active',
        ]));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Parada creada exitosamente',
                'stop' => $this->payload($stop),
            ], 201);
        }

        return redirect()->route('admin.routes.manage')
            ->with('success', 'Parada creada exitosamente');
    }

    public function show(Request $request, Stop $stop)
    {
        return redirect()->route('admin.routes.manage', ['panel' => 'stops', 'stop' => $stop->id]);
    }

    public function edit(Stop $stop)
    {
        return redirect()->route('admin.routes.manage', ['panel' => 'stops', 'stop' => $stop->id]);
    }

    public function update(UpdateStopRequest $request, Stop $stop)
    {
        $stop->update($request->safe()->only([
            'name', 'address', 'latitude', 'longitude', 'description', 'is_active',
        ]));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Parada actualizada exitosamente',
                'stop' => $this->payload($stop),
            ]);
        }

        return redirect()->route('admin.routes.manage')
            ->with('success', 'Parada actualizada exitosamente');
    }

    public function destroy(Request $request, Stop $stop)
    {
        if ($this->hasHistory($stop)) {
            $stop->update(['is_active' => false]);
            $message = 'La parada está en uso; se desactivó en lugar de eliminarse.';
        } else {
            DB::transaction(fn () => $stop->delete());
            $message = 'Parada eliminada exitosamente';
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.routes.manage')->with('success', $message);
    }

    public function toggleStatus(Request $request, Stop $stop)
    {
        $stop->update(['is_active' => ! $stop->is_active]);
        $message = $stop->is_active ? 'Parada activada.' : 'Parada desactivada.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'is_active' => (bool) $stop->is_active,
                'stop' => $this->payload($stop),
            ]);
        }

        return redirect()->route('admin.routes.manage')->with('success', $message);
    }

    /**
     * Una parada compartida por varias rutas (o con favoritos) no se elimina:
     * se desactiva para no perder las asociaciones históricas.
     */
    private function hasHistory(Stop $stop): bool
    {
        return $stop->routes()->exists()
            || $stop->favoritedByUsers()->exists()
            || $stop->likes()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Stop $stop): array
    {
        return [
            'id' => $stop->id,
            'name' => $stop->name,
            'address' => $stop->address,
            'latitude' => (float) $stop->latitude,
            'longitude' => (float) $stop->longitude,
            'is_active' => (bool) $stop->is_active,
            'route_ids' => $stop->routes()->pluck('routes.id')->all(),
            'urls' => [
                'update' => route('admin.stops.update', $stop),
                'destroy' => route('admin.stops.destroy', $stop),
                'status' => route('admin.stops.status', $stop),
            ],
        ];
    }
}
