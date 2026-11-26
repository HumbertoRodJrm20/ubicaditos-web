<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emergency\StoreEmergencyReportRequest;
use App\Models\Bus;
use App\Services\EmergencyReportService;

/**
 * RF-19: reportes de emergencia enviados por pasajeros.
 */
class EmergencyController extends Controller
{
    public function create()
    {
        return view('passenger.emergency', [
            'buses' => Bus::where('is_active', true)
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'model']),
        ]);
    }

    public function store(StoreEmergencyReportRequest $request, EmergencyReportService $service)
    {
        $service->report($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Emergencia reportada. Ayuda en camino.',
            ]);
        }

        return redirect()->route('passenger.menu')
            ->with('success', 'Emergencia reportada. Ayuda en camino.');
    }
}
