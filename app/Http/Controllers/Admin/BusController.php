<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusRequest;
use App\Http\Requests\Admin\UpdateBusRequest;
use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $allowedStatuses = ['active', 'inactive', 'maintenance', 'emergency'];
        $status = in_array($status, $allowedStatuses, true) ? $status : null;

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 20, 50], true) ? $perPage : 15;

        $buses = Bus::when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('license_plate', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('driver', function ($driverQuery) use ($search) {
                        $driverQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('maternal_last_name', 'like', "%{$search}%");
                    });
            });
        })
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->with(['routes', 'driver'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = Bus::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => Bus::count(),
            'active' => (int) ($statusCounts['active'] ?? 0),
            'maintenance' => (int) ($statusCounts['maintenance'] ?? 0),
            'emergency' => (int) ($statusCounts['emergency'] ?? 0),
            'inactive' => (int) ($statusCounts['inactive'] ?? 0),
        ];

        return view('admin.buses.index', compact('buses', 'summary', 'status', 'search'));
    }

    /**
     * Detalle de un autobús: datos, conductor, ruta asignada y viajes recientes.
     */
    public function show(Bus $bus)
    {
        $bus->load(['driver', 'routes']);

        $recentTrips = $bus->trips()
            ->with(['route', 'driver'])
            ->latest()
            ->limit(10)
            ->get();

        $emergencies = $bus->emergencyReports()->latest()->limit(5)->get();

        return view('admin.buses.show', compact('bus', 'recentTrips', 'emergencies'));
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

    public function store(StoreBusRequest $request)
    {
        $validated = $request->validated();

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
            ->where(function ($query) use ($bus) {
                $query->whereDoesntHave('drivingBus')
                    ->orWhere('id', $bus->driver_id);
            })
            ->get();

        return view('admin.buses.edit', compact('bus', 'routes', 'drivers'));
    }

    public function update(UpdateBusRequest $request, Bus $bus)
    {
        $validated = $request->validated();

        $bus->update($validated);

        if ($request->has('routes')) {
            $bus->routes()->sync($request->routes);
        }

        return redirect()->route('admin.buses.index')
            ->with('success', 'Autobús actualizado exitosamente');
    }

    public function destroy(Bus $bus)
    {
        if ($bus->trips()->where('status', 'in_progress')->exists() || $bus->driver()->exists()) {
            return back()->with('error', 'No se puede eliminar el autobús mientras tenga un viaje activo o un conductor asignado.');
        }

        if ($this->hasHistory($bus)) {
            $bus->update([
                'is_active' => false,
                'status' => 'inactive',
            ]);

            return back()->with('success', 'El autobús tiene historial; se desactivó en lugar de eliminarse.');
        }

        $bus->delete();

        return back()->with('success', 'Autobús eliminado exitosamente');
    }

    private function hasHistory(Bus $bus): bool
    {
        return $bus->trips()->exists()
            || $bus->routes()->exists()
            || $bus->messages()->exists()
            || $bus->emergencyReports()->exists();
    }
}
