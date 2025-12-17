<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Ubicaditos - {{ now()->format('d/m/Y') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #06b6d4; text-align: center; }
        h2 { color: #333; border-bottom: 2px solid #06b6d4; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #06b6d4; color: white; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 15px; background: #f0f0f0; border-radius: 5px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #06b6d4; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{{ public_path('images/autobus.svg') }}" alt="Ubicaditos Logo" style="width: 60px; height: 60px; display: inline-block;">
        <h1 style="display: inline-block; margin: 0 0 0 10px; vertical-align: middle;">Reporte de Ubicaditos</h1>
    </div>
    <p style="text-align: center; color: #666;">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Estadísticas Generales</h2>
    <table>
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Total de Viajes</td>
            <td>{{ number_format($stats['total_trips']) }}</td>
        </tr>
        <tr>
            <td>Total de Autobuses</td>
            <td>{{ $stats['total_buses'] }}</td>
        </tr>
        <tr>
            <td>Total de Conductores</td>
            <td>{{ $stats['total_drivers'] }}</td>
        </tr>
        <tr>
            <td>Total de Pasajeros</td>
            <td>{{ $stats['total_passengers'] }}</td>
        </tr>
        <tr>
            <td>Calificación Promedio</td>
            <td>{{ number_format($stats['average_rating'], 2) }} / 5.0</td>
        </tr>
    </table>

    <h2>Viajes por Día (Últimos 30 Días)</h2>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Cantidad de Viajes</th>
        </tr>
        @foreach($tripsByDay as $trip)
        <tr>
            <td>{{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}</td>
            <td>{{ $trip->count }}</td>
        </tr>
        @endforeach
    </table>

    <h2>Top 10 Conductores</h2>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Viajes Realizados</th>
            <th>Calificación</th>
        </tr>
        @foreach($topDrivers as $driver)
        <tr>
            <td>{{ $driver->name }}</td>
            <td>{{ $driver->trips_count }}</td>
            <td>{{ $driver->received_ratings_avg_rating ? number_format($driver->received_ratings_avg_rating, 2) : 'Sin calificación' }}</td>
        </tr>
        @endforeach
    </table>

    <p style="text-align: center; margin-top: 30px; color: #666; font-size: 10px;">
        © {{ now()->year }} Ubicaditos - Sistema de Transporte
    </p>
</body>
</html>
