<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Route, Stop, User, Trip, EmergencyReport, Rating};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ReportsExport;

class ReportController extends Controller
{
    public function index()
    {
        // Estadísticas generales
        $stats = [
            'total_trips' => Trip::count(),
            'total_trips_month' => Trip::whereMonth('started_at', now()->month)->count(),
            'total_buses' => Bus::count(),
            'active_buses' => Bus::where('status', 'active')->count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'total_passengers' => User::where('role', 'passenger')->count(),
            'total_routes' => Route::count(),
            'total_stops' => Stop::count(),
            'average_rating' => Rating::avg('rating') ?? 0,
            'total_emergencies' => EmergencyReport::count(),
            'pending_emergencies' => EmergencyReport::where('status', 'pending')->count(),
        ];

        // Viajes por día (últimos 30 días)
        $tripsByDay = Trip::selectRaw('DATE(started_at) as date, COUNT(*) as count')
            ->where('started_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Completar días faltantes con 0
        $dates = [];
        $counts = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d/m');
            $counts[] = $tripsByDay[$date] ?? 0;
        }

        // Uso de autobuses
        $busUsage = Bus::withCount(['trips' => function($query) {
            $query->where('started_at', '>=', now()->subDays(30));
        }])
            ->orderBy('trips_count', 'desc')
            ->limit(10)
            ->get();

        // Mejores conductores
        $topDrivers = User::where('role', 'driver')
            ->withCount('trips')
            ->withAvg('ratingsReceived', 'rating')
            ->orderBy('trips_count', 'desc')
            ->limit(10)
            ->get();

        // Rutas más utilizadas
        $topRoutes = Route::withCount(['trips' => function($query) {
            $query->where('started_at', '>=', now()->subDays(30));
        }])
            ->orderBy('trips_count', 'desc')
            ->limit(5)
            ->get();

        // Ocupación promedio por hora
        $occupancyByHour = Trip::selectRaw("CAST(strftime('%H', started_at) AS INTEGER) as hour, AVG(total_passengers) as avg_passengers")
            ->where('started_at', '>=', now()->subDays(7))
            ->whereNotNull('total_passengers')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Emergencias por tipo
        $emergenciesByType = EmergencyReport::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        return view('admin.reports', compact(
            'stats',
            'dates',
            'counts',
            'busUsage',
            'topDrivers',
            'topRoutes',
            'occupancyByHour',
            'emergenciesByType'
        ));
    }

    public function exportPdf(Request $request)
    {
        $stats = [
            'total_trips' => Trip::count(),
            'total_buses' => Bus::count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'total_passengers' => User::where('role', 'passenger')->count(),
            'average_rating' => Rating::avg('rating') ?? 0,
        ];

        $tripsByDay = Trip::selectRaw('DATE(started_at) as date, COUNT(*) as count')
            ->where('started_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topDrivers = User::where('role', 'driver')
            ->withCount('trips')
            ->withAvg('ratingsReceived', 'rating')
            ->orderBy('trips_count', 'desc')
            ->limit(10)
            ->get();

        $pdf = Pdf::loadView('admin.reports-pdf', compact('stats', 'tripsByDay', 'topDrivers'));
        return $pdf->download('reporte-ubicaditos-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $export = new ReportsExport();
        $data = $export->collection();

        $filename = 'reporte-ubicaditos-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
