<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Database\Seeder;

/**
 * Agrega paradas intermedias a las rutas existentes.
 *
 * Los puntos fueron muestreados sobre el trazado real de cada ruta (OSRM) y
 * geocodificados con Nominatim. El archivo de datos también guarda la posición
 * final dentro del recorrido para que el seeder sea reproducible.
 */
class AdditionalRouteStopsSeeder extends Seeder
{
    protected string $dataFile = 'additional_route_stops.json';

    protected string $label = 'Paradas adicionales';

    protected bool $isFrequent = false;

    public function run(): void
    {
        $dataPath = database_path('data/' . $this->dataFile);

        if (! is_file($dataPath)) {
            $this->command?->error('No se encontró database/data/' . $this->dataFile);
            return;
        }

        $data = json_decode(file_get_contents($dataPath), true);

        if (! is_array($data) || empty($data['stops']) || empty($data['routes'])) {
            $this->command?->error('El archivo ' . $this->dataFile . ' no tiene un formato válido.');
            return;
        }

        $stops = [];

        foreach ($data['stops'] as $row) {
            $stop = Stop::firstOrCreate(
                [
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                ],
                [
                    'name' => $row['name'],
                    'address' => $row['address'] ?? null,
                    'is_active' => true,
                ]
            );

            $stops[$row['key']] = $stop;
        }

        $added = 0;

        foreach ($data['routes'] as $routeCode => $additions) {
            $route = Route::where('code', $routeCode)->first();

            if (! $route) {
                continue;
            }

            $newStops = [];

            foreach ($additions as $addition) {
                if (isset($addition['stop_key'])) {
                    $stop = $stops[$addition['stop_key']] ?? null;
                } elseif (isset($addition['existing_stop'])) {
                    // Referencia estable por coordenadas: los IDs cambian al
                    // sembrar una base nueva, pero las coordenadas no.
                    $stop = Stop::firstOrCreate(
                        [
                            'latitude' => $addition['existing_stop']['latitude'],
                            'longitude' => $addition['existing_stop']['longitude'],
                        ],
                        [
                            'name' => $addition['existing_stop']['name'],
                            'address' => $addition['existing_stop']['address'] ?? null,
                            'is_active' => true,
                        ]
                    );
                } elseif (isset($addition['existing_stop_id'])) {
                    $stop = Stop::find($addition['existing_stop_id']);
                } else {
                    $stop = null;
                }

                if (! $stop) {
                    continue;
                }

                $alreadyAttached = $route->stops()
                    ->where('stops.id', $stop->id)
                    ->exists();

                if ($alreadyAttached) {
                    if ($this->isFrequent) {
                        $route->stops()->updateExistingPivot($stop->id, [
                            'is_frequent' => true,
                        ]);
                    }

                    continue;
                }

                $newStops[] = [
                    'stop' => $stop,
                    'order' => (int) $addition['order'],
                    'minutes' => (int) $addition['minutes'],
                ];
            }

            if ($newStops === []) {
                continue;
            }

            usort($newStops, fn (array $a, array $b) => $a['order'] <=> $b['order']);

            $minutesByStop = collect($newStops)
                ->mapWithKeys(fn (array $newStop) => [$newStop['stop']->id => $newStop['minutes']])
                ->all();

            // La lista actual ya viene ordenada por route_stop.order.
            $ordered = $route->stops()->get()->values()->all();

            foreach ($newStops as $newStop) {
                $position = max(0, min($newStop['order'], count($ordered)));
                array_splice($ordered, $position, 0, [$newStop['stop']]);
            }

            foreach ($ordered as $index => $stop) {
                if (array_key_exists($stop->id, $minutesByStop)) {
                    if (! $route->stops()->where('stops.id', $stop->id)->exists()) {
                        $route->stops()->attach($stop->id, [
                            'order' => $index,
                            'estimated_time_from_previous' => $minutesByStop[$stop->id],
                            'is_frequent' => $this->isFrequent,
                        ]);
                        $added++;
                    }
                } else {
                    $route->stops()->updateExistingPivot($stop->id, [
                        'order' => $index,
                    ]);
                }
            }
        }

        $this->command?->info($this->label . " agregadas: {$added}");
    }
}
