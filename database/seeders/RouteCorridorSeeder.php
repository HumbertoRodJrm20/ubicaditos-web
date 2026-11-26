<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Define los corredores principales de cada ruta.
 *
 * La secuencia de paradas es la fuente de verdad del trazado: OSRM sólo
 * conecta estas paradas siguiendo calles. Las demás paradas que ya estaban
 * asociadas a la ruta no se eliminan; quedan como paradas secundarias de alta
 * frecuencia y dejan de participar en el cálculo del recorrido.
 */
class RouteCorridorSeeder extends Seeder
{
    /**
     * Coordenadas de referencia para elegir la parada correcta cuando el
     * nombre se repite en la base de datos.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const STOP_COORDINATES = [
        'ADO' => [17.069600, -96.718400],
        'Aeropuerto' => [16.999900, -96.726600],
        'Atzompa' => [17.067800, -96.771200],
        'CFE' => [17.064500, -96.721200],
        'Calzada Madero' => [17.069700, -96.722300],
        'Central de Abastos' => [17.048700, -96.743700],
        'Cinco Señores' => [17.072900, -96.739800],
        'Cuilápam' => [16.982300, -96.761200],
        'Etla' => [17.201200, -96.783400],
        'Forjadores' => [17.042300, -96.732300],
        'Ixcotel' => [17.078900, -96.698000],
        'Jalatlaco' => [17.068900, -96.718900],
        'Llano Park' => [17.048900, -96.726700],
        'Mercado Benito Juárez' => [17.063600, -96.725500],
        'Parque del Amor' => [17.073400, -96.723400],
        'Plaza del Valle' => [17.052000, -96.718000],
        'Reforma' => [17.061200, -96.712300],
        'San Agustín' => [17.023400, -96.612300],
        'San Felipe' => [17.097100, -96.715000],
        'San Martín Mexicapam' => [17.092300, -96.688900],
        'Santo Domingo' => [17.067600, -96.720800],
        'Símbolos Patrios' => [17.085100, -96.721000],
        'Tecnológico' => [17.054200, -96.702300],
        'Tlacolula' => [16.957400, -96.476500],
        'Tlalixtac' => [17.061200, -96.683400],
        'Tule' => [17.046700, -96.636400],
        'UABJO' => [17.059900, -96.725500],
        'Vasco de Quiroga' => [17.053400, -96.725600],
        'Viguera' => [17.070100, -96.733400],
        'Xoxocotlán' => [17.033200, -96.730100],
        'Zaachila' => [16.953400, -96.748900],
        'Zócalo' => [17.065400, -96.725600],
    ];

    /**
     * Corredores principales, en orden de recorrido.
     *
     * Se usan únicamente puntos que ya existen en la base. No se inventan
     * calles ni recorridos oficiales: se seleccionan paradas existentes sobre
     * ejes continuos (poniente-oriente, norte-sur y diagonales).
     *
     * @var array<string, array<int, string>>
     */
    public const CORRIDORS = [
        'OAX-101' => [
            'Zócalo', 'Mercado Benito Juárez', 'UABJO', 'Plaza del Valle',
            'Tecnológico', 'Tlalixtac', 'Tule',
        ],
        'OAX-102' => [
            'Zócalo', 'Mercado Benito Juárez', 'UABJO', 'Vasco de Quiroga',
            'Llano Park', 'Xoxocotlán',
        ],
        'OAX-103' => [
            'Zócalo', 'Calzada Madero', 'Parque del Amor', 'Símbolos Patrios',
            'San Felipe',
        ],
        'OAX-104' => [
            'Zócalo', 'Mercado Benito Juárez', 'UABJO', 'Reforma',
            'Tecnológico',
        ],
        'OAX-105' => [
            'Zócalo', 'Santo Domingo', 'Jalatlaco', 'ADO', 'Ixcotel',
        ],
        'OAX-106' => [
            'San Felipe', 'Símbolos Patrios', 'Ixcotel',
        ],
        'OAX-107' => [
            'Central de Abastos', 'Forjadores', 'Xoxocotlán', 'Aeropuerto',
        ],
        'OAX-108' => [
            'Zócalo', 'Tecnológico', 'Tlalixtac', 'Tule',
        ],
        'OAX-109' => [
            'Zócalo', 'Reforma', 'Tecnológico', 'Tlalixtac',
        ],
        'OAX-110' => [
            'Zócalo', 'Mercado Benito Juárez', 'UABJO', 'Vasco de Quiroga',
            'Xoxocotlán', 'Aeropuerto',
        ],
        'OAX-111' => [
            'Zócalo', 'Viguera', 'Cinco Señores', 'Atzompa', 'Etla',
        ],
        'OAX-112' => [
            'Zócalo', 'Tecnológico', 'Tlalixtac', 'Tule', 'San Agustín',
            'Tlacolula',
        ],
        'OAX-113' => [
            'Zócalo', 'Mercado Benito Juárez', 'UABJO', 'Xoxocotlán',
            'Cuilápam', 'Zaachila',
        ],
        'OAX-114' => [
            'Jalatlaco', 'CFE', 'Zócalo', 'Viguera', 'Cinco Señores',
            'Atzompa',
        ],
        'OAX-115' => [
            'Zócalo', 'Tecnológico', 'Tlalixtac', 'Tule', 'San Agustín',
        ],
        'OAX-116' => [
            'San Felipe', 'Símbolos Patrios', 'Ixcotel', 'Tlalixtac', 'Tule',
            'San Agustín',
        ],
        'OAX-117' => [
            'San Felipe', 'Símbolos Patrios', 'Parque del Amor', 'Zócalo',
            'Mercado Benito Juárez', 'UABJO', 'Vasco de Quiroga',
            'Xoxocotlán', 'Zaachila',
        ],
        'OAX-118' => [
            'Atzompa', 'Cinco Señores', 'Viguera', 'Zócalo', 'Reforma',
            'Tecnológico', 'Tlalixtac',
        ],
    ];

    public function run(): void
    {
        $routes = Route::whereIn('code', array_keys(self::CORRIDORS))
            ->get()
            ->keyBy('code');

        $missing = [];
        $plans = [];

        foreach (self::CORRIDORS as $code => $names) {
            $route = $routes->get($code);

            if (! $route) {
                $missing[] = "ruta {$code}";
                continue;
            }

            $stops = [];

            foreach ($names as $name) {
                $stop = $this->findStop($name);

                if (! $stop) {
                    $missing[] = "{$code}: {$name}";
                    continue;
                }

                $stops[] = $stop;
            }

            if (count($stops) >= 2) {
                $plans[$code] = ['route' => $route, 'stops' => $stops];
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException(
                'No se pudieron aplicar los corredores. Faltan paradas o rutas: '
                . implode(', ', $missing)
            );
        }

        foreach ($plans as $code => $plan) {
            $this->applyCorridor($plan['route'], $plan['stops']);
            $this->command?->line("  • {$code}: " . count($plan['stops']) . ' paradas principales');
        }

        $this->command?->info('Corredores aplicados: ' . count($plans) . ' rutas.');
    }

    /**
     * @param array<int, Stop> $stops
     */
    private function applyCorridor(Route $route, array $stops): void
    {
        // Todas las relaciones actuales pasan a ser secundarias. No se borra
        // ninguna parada ni se elimina su relación con la ruta.
        DB::table('route_stop')
            ->where('route_id', $route->id)
            ->update([
                'is_frequent' => true,
                'updated_at' => now(),
            ]);

        $previous = null;
        $totalMinutes = 0;

        foreach ($stops as $order => $stop) {
            $minutes = 0;

            if ($previous !== null) {
                // 15 km/h promedio urbano con paradas: 4 minutos por kilómetro.
                $minutes = max(1, (int) round($this->distanceKm($previous, $stop) * 4));
            }

            $totalMinutes += $minutes;

            $attributes = [
                'order' => $order,
                'estimated_time_from_previous' => $minutes,
                'is_frequent' => false,
                'updated_at' => now(),
            ];

            $exists = DB::table('route_stop')
                ->where('route_id', $route->id)
                ->where('stop_id', $stop->id)
                ->exists();

            if ($exists) {
                DB::table('route_stop')
                    ->where('route_id', $route->id)
                    ->where('stop_id', $stop->id)
                    ->update($attributes);
            } else {
                DB::table('route_stop')->insert(array_merge($attributes, [
                    'route_id' => $route->id,
                    'stop_id' => $stop->id,
                    'created_at' => now(),
                ]));
            }

            $previous = $stop;
        }

        // Las paradas secundarias quedan después de las principales para que
        // cualquier vista que las muestre conserve un orden coherente.
        $secondaryStopIds = DB::table('route_stop')
            ->where('route_id', $route->id)
            ->where('is_frequent', true)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('stop_id');

        $nextOrder = count($stops);

        foreach ($secondaryStopIds as $stopId) {
            DB::table('route_stop')
                ->where('route_id', $route->id)
                ->where('stop_id', $stopId)
                ->update([
                    'order' => $nextOrder++,
                    'estimated_time_from_previous' => 0,
                    'updated_at' => now(),
                ]);
        }

        $path = is_array($route->path_coordinates) ? $route->path_coordinates : [];
        $duration = count($path) >= 2
            ? (int) round(($this->pathLengthKm($path) / 20) * 60 + count($stops) * 0.5)
            : $totalMinutes;

        $route->forceFill([
            'estimated_duration' => max(1, $duration),
        ])->save();
    }

    private function findStop(string $name): ?Stop
    {
        $candidates = Stop::where('name', $name)->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $coordinates = self::STOP_COORDINATES[$name] ?? null;

        if ($coordinates === null) {
            return $candidates->first();
        }

        [$latitude, $longitude] = $coordinates;

        return $candidates->sortBy(function (Stop $stop) use ($latitude, $longitude) {
            $latDelta = (float) $stop->latitude - $latitude;
            $lngDelta = (float) $stop->longitude - $longitude;

            return ($latDelta * $latDelta) + ($lngDelta * $lngDelta);
        })->first();
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $path
     */
    private function pathLengthKm(array $path): float
    {
        $distanceKm = 0.0;

        for ($i = 1; $i < count($path); $i++) {
            $distanceKm += $this->distanceBetween(
                (float) $path[$i - 1]['lat'],
                (float) $path[$i - 1]['lng'],
                (float) $path[$i]['lat'],
                (float) $path[$i]['lng']
            );
        }

        return $distanceKm;
    }

    private function distanceKm(Stop $from, Stop $to): float
    {
        return $this->distanceBetween(
            (float) $from->latitude,
            (float) $from->longitude,
            (float) $to->latitude,
            (float) $to->longitude
        );
    }

    private function distanceBetween(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $earthRadiusKm = 6371.0;
        $lat1 = deg2rad($fromLatitude);
        $lat2 = deg2rad($toLatitude);
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
