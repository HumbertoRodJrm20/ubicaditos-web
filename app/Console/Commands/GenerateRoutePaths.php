<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Route;
use Illuminate\Support\Facades\Http;

class GenerateRoutePaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routes:generate-paths
                            {--force : Regenera el trazado aunque la ruta ya tenga path_coordinates}
                            {--route= : Procesa únicamente la ruta con este código}
                            {--sleep=1 : Segundos de espera entre peticiones a OSRM}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera y guarda en la BD el trazado de las rutas usando OSRM (una sola vez)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Route::query();

        if ($code = $this->option('route')) {
            $query->where('code', $code);
        }

        $routes = $query->orderBy('id')->get();

        if ($routes->isEmpty()) {
            $this->error('No se encontraron rutas para procesar.');
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $sleep = max(0, (int) $this->option('sleep'));

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $this->info('Generando trazados sobre calles (OSRM)...');

        foreach ($routes as $route) {
            // OSRM se calcula con las paradas principales. Las paradas de alta
            // frecuencia están muestreadas sobre ese mismo trazado y harían
            // demasiado grande la petición (hasta 180 waypoints por ruta).
            $stops = $route->getOrderedStops(false);

            if ($stops->count() < 2) {
                $this->warn("⚠ {$route->code}: tiene menos de 2 paradas, se omite.");
                $skipped++;
                continue;
            }

            $existingPath = is_array($route->path_coordinates) ? $route->path_coordinates : [];

            if (!$force && count($existingPath) >= 2) {
                $this->line("• {$route->code}: ya tiene {$this->pointCount($existingPath)} puntos, se conserva.");
                $skipped++;
                continue;
            }

            $waypoints = $stops->map(function ($stop) {
                return $stop->longitude . ',' . $stop->latitude;
            })->join(';');

            $this->line("→ {$route->code}: consultando OSRM...");

            try {
                $response = Http::timeout(30)->get(
                    "https://router.project-osrm.org/route/v1/driving/{$waypoints}",
                    [
                        'overview' => 'full',
                        'geometries' => 'geojson',
                    ]
                );

                $geometry = $response->json('routes.0.geometry.coordinates');

                if (!$response->successful() || $response->json('code') !== 'Ok' || !is_array($geometry)) {
                    $this->error("✗ {$route->code}: OSRM no devolvió un trazado válido.");
                    $failed++;
                    continue;
                }

                $pathCoordinates = collect($geometry)->map(function ($coord) {
                    return [
                        'lat' => $coord[1],
                        'lng' => $coord[0],
                    ];
                })->toArray();

                $route->path_coordinates = $pathCoordinates;
                $route->save();

                $this->info("✓ {$route->code}: {$this->pointCount($pathCoordinates)} puntos guardados.");
                $generated++;
            } catch (\Throwable $e) {
                $this->error("✗ {$route->code}: {$e->getMessage()}");
                $failed++;
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->table(
            ['Generadas', 'Conservadas', 'Fallidas'],
            [[$generated, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function pointCount(array $path): int
    {
        return count($path);
    }
}
