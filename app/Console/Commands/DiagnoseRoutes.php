<?php

namespace App\Console\Commands;

use App\Models\Route;
use Illuminate\Console\Command;

class DiagnoseRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routes:diagnose
                            {--threshold=0.70 : Similitud mínima (Jaccard de paradas) para reportar dos rutas como parecidas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica rutas repetidas, tramos compartidos y trazados faltantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = max(0, min(1, (float) $this->option('threshold')));

        $routes = Route::with(['stops', 'buses'])->orderBy('id')->get();

        if ($routes->isEmpty()) {
            $this->error('No hay rutas registradas.');
            return self::FAILURE;
        }

        $this->info('📊 Diagnóstico de rutas');
        $this->newLine();

        $rows = [];
        $stopIdsByRoute = [];
        $routesWithoutPath = [];
        $routesWithFewStops = [];

        foreach ($routes as $route) {
            $orderedStops = $route->stops->sortBy(fn ($stop) => $stop->pivot->order)->values();
            $stopIds = $orderedStops->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $path = is_array($route->path_coordinates) ? $route->path_coordinates : [];

            $stopIdsByRoute[$route->id] = $stopIds;

            if (count($path) < 2) {
                $routesWithoutPath[] = $route->code;
            }

            if (count($stopIds) < 2) {
                $routesWithFewStops[] = $route->code;
            }

            $rows[] = [
                $route->id,
                $route->code,
                $route->name,
                count($stopIds),
                count($path),
                $route->buses->count(),
                $route->is_active ? 'sí' : 'no',
            ];
        }

        $this->table(
            ['ID', 'Código', 'Nombre', 'Paradas', 'Puntos trazado', 'Buses', 'Activa'],
            $rows
        );

        if ($routesWithoutPath !== []) {
            $this->warn('⚠ Rutas sin trazado guardado (se dibujarán como líneas rectas): ' . implode(', ', $routesWithoutPath));
        }

        if ($routesWithFewStops !== []) {
            $this->warn('⚠ Rutas con menos de 2 paradas (no se pueden dibujar): ' . implode(', ', $routesWithFewStops));
        }

        $this->newLine();
        $this->info("🔁 Rutas con paradas en común (Jaccard >= {$threshold})");

        $similarPairs = [];

        $routeList = $routes->values();
        for ($i = 0; $i < $routeList->count(); $i++) {
            for ($j = $i + 1; $j < $routeList->count(); $j++) {
                $a = $routeList[$i];
                $b = $routeList[$j];
                $setA = $stopIdsByRoute[$a->id];
                $setB = $stopIdsByRoute[$b->id];

                $shared = count(array_intersect($setA, $setB));
                $union = count(array_unique(array_merge($setA, $setB)));
                $jaccard = $union > 0 ? $shared / $union : 0;

                if ($shared >= 2 && $jaccard >= $threshold) {
                    $similarPairs[] = [$a, $b, $shared, $jaccard];
                }
            }
        }

        usort($similarPairs, fn ($x, $y) => $y[3] <=> $x[3]);

        if ($similarPairs === []) {
            $this->line('  No se encontraron pares por encima del umbral.');
        } else {
            $pairRows = array_map(function ($pair) {
                [$a, $b, $shared, $jaccard] = $pair;
                return [
                    $a->code,
                    $b->code,
                    $shared,
                    round($jaccard * 100) . '%',
                ];
            }, $similarPairs);

            $this->table(['Ruta A', 'Ruta B', 'Paradas compartidas', 'Similitud'], $pairRows);
        }

        $this->newLine();
        $this->info('🧩 Tramos compartidos');

        $segments = [];
        $segmentInstances = 0;
        $stopNames = [];

        foreach ($routes as $route) {
            $stops = $stopIdsByRoute[$route->id];

            foreach ($route->stops as $stop) {
                $stopNames[(int) $stop->id] = $stop->name;
            }

            for ($i = 0; $i < count($stops) - 1; $i++) {
                $a = $stops[$i];
                $b = $stops[$i + 1];
                $key = min($a, $b) . '|' . max($a, $b);

                $segments[$key]['routes'][$route->code] = true;
                $segmentInstances++;
            }
        }

        $sharedSegments = array_filter($segments, fn ($segment) => count($segment['routes']) > 1);

        $this->line('  Instancias de tramo (suma de todas las rutas): ' . $segmentInstances);
        $this->line('  Tramos únicos: ' . count($segments));
        $this->line('  Tramos compartidos por 2+ rutas: ' . count($sharedSegments));
        $this->line('  Dibujos que se evitan al deduplicar: ' . ($segmentInstances - count($segments)));

        if ($sharedSegments !== []) {
            $this->newLine();
            $this->info('  Tramos más repetidos:');

            uasort($sharedSegments, fn ($a, $b) => count($b['routes']) <=> count($a['routes']));

            $top = array_slice($sharedSegments, 0, 10, true);
            $topRows = [];

            foreach ($top as $key => $segment) {
                [$stopA, $stopB] = explode('|', $key);
                $topRows[] = [
                    ($stopNames[(int) $stopA] ?? $stopA) . ' ↔ ' . ($stopNames[(int) $stopB] ?? $stopB),
                    count($segment['routes']),
                    implode(', ', array_keys($segment['routes'])),
                ];
            }

            $this->table(['Par de paradas', 'Rutas', 'Códigos'], $topRows);
        }

        return self::SUCCESS;
    }
}
