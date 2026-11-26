<?php

namespace App\Http\Controllers;

use App\Http\Requests\Incident\StoreIncidentRequest;
use App\Models\Bus;
use App\Models\Incident;
use App\Models\Route;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    /**
     * Listado de reportes del usuario autenticado.
     */
    public function index(Request $request)
    {
        $incidents = $request->user()
            ->incidents()
            ->with(['route', 'bus'])
            ->latest()
            ->get();

        return view('incidents.index', compact('incidents'));
    }

    /**
     * Formulario para registrar una incidencia.
     */
    public function create()
    {
        return view('incidents.create', [
            'categories' => Incident::CATEGORIES,
            'maxDescriptionLength' => Incident::MAX_DESCRIPTION_LENGTH,
            'routes' => Route::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'buses' => Bus::where('is_active', true)
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'model']),
        ]);
    }

    /**
     * Registra la incidencia y la asocia al usuario autenticado.
     */
    public function store(StoreIncidentRequest $request)
    {
        $validated = $request->safe()->only([
            'category',
            'description',
            'route_id',
            'bus_id',
            'latitude',
            'longitude',
        ]);

        $request->user()->incidents()->create([
            'category' => $validated['category'],
            'description' => trim($validated['description']),
            // El estado inicial siempre lo define el servidor. El usuario no
            // puede enviarlo desde el formulario.
            'status' => Incident::STATUS_PENDING,
            'route_id' => $validated['route_id'] ?? null,
            'bus_id' => $validated['bus_id'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()
            ->route('incidents.index')
            ->with('success', 'Tu reporte se registró correctamente. Quedó en estado pendiente.');
    }

    /**
     * Listado público de incidencias marcadas como públicas.
     * Nunca carga ni muestra la identidad del reportante.
     */
    public function publicIndex()
    {
        $incidents = Incident::where('is_public', true)
            ->with([
                'route:id,name,code',
                'bus:id,license_plate,model',
            ])
            ->latest()
            ->get();

        return view('incidents.public', compact('incidents'));
    }
}
