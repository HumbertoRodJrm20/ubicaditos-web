<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Services\RouteGeometryService;
use Illuminate\Console\Command;

class GenerateRoutePaths extends Command
{
    protected $signature = 'routes:generate-paths
                            {--force : Regenera el trazado aunque la ruta ya tenga path_coordinates}
                            {--route= : Procesa únicamente la ruta con este código}
                            {--sleep=1 : Segundos de espera entre peticiones a OSRM}';

    protected $description = 'Genera y guarda en la BD el trazado de las rutas usando OSRM (una sola vez)';

    public function handle(RouteGeometryService $geometry): int
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
            $result = $geometry->generate($route, $force);

            switch ($result['status']) {
                case 'generated':
                    $this->info("✓ {$route->code}: {$result['points']} puntos guardados.");
                    $generated += 1;
                    break;

                case 'skipped':
                    $this->line("• {$route->code}: {$result['message']}");
                    $skipped += 1;
                    break;

                default:
                    $this->error("✗ {$route->code}: {$result['message']}");
                    $failed += 1;
                    break;
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
}
