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
    protected $signature = 'routes:generate-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera path_coordinates para todas las rutas usando OSRM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routes = Route::with('stops')->get();

        $this->info('Generando rutas que siguen las calles...');

        foreach ($routes as $route) {
            if ($route->stops->count() < 2) {
                $this->warn("Ruta {$route->name} tiene menos de 2 paradas, saltando...");
                continue;
            }

            $this->info("Procesando: {$route->name}");

            // Ordenar paradas por orden
            $stops = $route->stops->sortBy(function ($stop) use ($route) {
                return $stop->pivot->order;
            });

            // Crear lista de waypoints para OSRM
            $waypoints = $stops->map(function ($stop) {
                return $stop->longitude . ',' . $stop->latitude;
            })->join(';');

            try {
                // Consultar OSRM
                $response = Http::timeout(30)->get(
                    "https://router.project-osrm.org/route/v1/driving/{$waypoints}",
                    [
                        'overview' => 'full',
                        'geometries' => 'geojson'
                    ]
                );

                if ($response->successful() && $response->json('code') === 'Ok') {
                    $geometry = $response->json('routes.0.geometry.coordinates');

                    // Convertir coordenadas [lng, lat] a formato {lat, lng}
                    $pathCoordinates = collect($geometry)->map(function ($coord) {
                        return [
                            'lat' => $coord[1],
                            'lng' => $coord[0]
                        ];
                    })->toArray();

                    // Guardar en la ruta
                    $route->path_coordinates = $pathCoordinates;
                    $route->save();

                    $pointCount = count($pathCoordinates);
                    $this->info("✅ {$route->name}: {$pointCount} puntos generados");
                } else {
                    $this->error("❌ Error en OSRM para {$route->name}");
                }

                // Esperar un poco para no saturar OSRM
                sleep(1);
            } catch (\Exception $e) {
                $this->error("❌ Excepción al procesar {$route->name}: {$e->getMessage()}");
            }
        }

        $this->info('');
        $this->info('🎉 Proceso completado!');
    }
}
