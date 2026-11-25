<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    /**
     * Listado de reportes del usuario autenticado.
     */
    public function index(Request $request)
    {
        $incidents = $request->user()
            ->incidents()
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
        ]);
    }

    /**
     * Registra la incidencia y la asocia al usuario autenticado.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => [
                'required',
                'string',
                Rule::in(array_keys(Incident::CATEGORIES)),
            ],
            'description' => [
                'required',
                'string',
                'max:' . Incident::MAX_DESCRIPTION_LENGTH,
            ],
        ], [
            'category.required' => 'Selecciona el tipo de incidencia.',
            'category.in' => 'El tipo de incidencia seleccionado no es válido.',
            'description.required' => 'Describe la incidencia antes de enviar el reporte.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los '
                . Incident::MAX_DESCRIPTION_LENGTH . ' caracteres.',
        ]);

        $request->user()->incidents()->create([
            'category' => $validated['category'],
            'description' => trim($validated['description']),
            // El estado inicial siempre lo define el servidor. El usuario no
            // puede enviarlo desde el formulario.
            'status' => Incident::STATUS_PENDING,
        ]);

        return redirect()
            ->route('incidents.index')
            ->with('success', 'Tu reporte se registró correctamente. Quedó en estado pendiente.');
    }
}
