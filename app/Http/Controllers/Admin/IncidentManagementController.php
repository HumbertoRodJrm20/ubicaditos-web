<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentManagementController extends Controller
{
    /**
     * Listado global de reportes de usuarios con filtros y contadores.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $category = $request->query('category');
        $search = trim((string) $request->query('q'));

        $incidents = Incident::with('user')
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->when($category, fn ($query, $value) => $query->where('category', $value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Incident::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'statuses' => Incident::STATUSES,
            'categories' => Incident::CATEGORIES,
            'counts' => $counts,
            'status' => $status,
            'category' => $category,
            'search' => $search,
        ]);
    }

    /**
     * Cambia el estado de un reporte (pendiente → en revisión → resuelto).
     */
    public function update(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(array_keys(Incident::STATUSES)),
            ],
        ], [
            'status.required' => 'Selecciona el nuevo estado del reporte.',
            'status.in' => 'El estado seleccionado no es válido.',
        ]);

        $incident->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === Incident::STATUS_RESOLVED
                ? ($incident->resolved_at ?? now())
                : null,
        ]);

        return back()->with('success', 'El estado del reporte se actualizó correctamente.');
    }
}
