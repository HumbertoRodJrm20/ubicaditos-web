<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneSecondaryStops extends Command
{
    protected $signature = 'routes:prune-secondary-stops
                            {--max-distance=200 : Distancia máxima permitida en metros}
                            {--route= : Procesa únicamente la ruta con este código}
                            {--dry-run : Muestra las paradas que se desvincularían sin borrar nada}';

    protected $description = 'Desvincula paradas secundarias que quedaron lejos del corredor real de una ruta';

    public function handle(): int
    {
        $maxDistance = max(1, (int) $this->option('max-distance'));
        $dryRun = (bool) $this->option('dry-run');
        $query = Route::query()->orderBy('id');

        if ($code = $this->option('route')) {
            $query->where('code', $code);
        }

        $routes = $query->get();

        if ($routes->isEmpty()) {
            $this->error('No se encontraron rutas para procesar.');
            return self::FAILURE;
        }

        $totalRemoved = 0;

        $this->info("Revisando paradas secundarias a más de {$maxDistance} m del corredor...");

        foreach ($routes as $route) {
            $stops = $route->getOrderedStops(true);
            $principal = $stops
                ->filter(fn (Stop $stop) => (int) ($stop->pivot->is_frequent ?? 0) === 0)
                ->values();

            $path = is_array($route->path_coordinates) ? $route->path_coordinates : [];

            if (count($path) < 2) {
                $path = $principal->map(fn (Stop $stop) => [
                    'lat' => (float) $stop->latitude,
                    'lng' => (float) $stop->longitude,
                ])->all();
            }

            if (count($path) < 2) {
                $this->warn("  • {$route->code}: sin trazado suficiente, se omite.");
                continue;
            }

            $toRemove = $stops
                ->filter(fn (Stop $stop) => (int) ($stop->pivot->is_frequent ?? 0) === 1)
                ->filter(function (Stop $stop) use ($path, $maxDistance) {
                    return $this->distanceToPathMeters(
                        (float) $stop->latitude,
                        (float) $stop->longitude,
                        $path
                    ) > $maxDistance;
                })
                ->values();

            if ($toRemove->isEmpty()) {
                $this->line("  • {$route->code}: sin cambios.");
                continue;
            }

            if (! $dryRun) {
                DB::table('route_stop')
                    ->where('route_id', $route->id)
                    ->whereIn('stop_id', $toRemove->pluck('id')->all())
                    ->delete();
            }

            $totalRemoved += $toRemove->count();
            $this->line(sprintf(
                '  • %s: %d paradas %s.',
                $route->code,
                $toRemove->count(),
                $dryRun ? 'se desvincularían' : 'desvinculadas'
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d paradas secundarias.',
            $dryRun ? 'Vista previa' : 'Total desvinculadas',
            $totalRemoved
        ));

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $path
     */
    private function distanceToPathMeters(float $latitude, float $longitude, array $path): float
    {
        $best = INF;

        for ($i = 1; $i < count($path); $i++) {
            $distance = $this->distancePointToSegmentMeters(
                $latitude,
                $longitude,
                (float) $path[$i - 1]['lat'],
                (float) $path[$i - 1]['lng'],
                (float) $path[$i]['lat'],
                (float) $path[$i]['lng']
            );

            if ($distance < $best) {
                $best = $distance;
            }
        }

        return $best;
    }

    private function distancePointToSegmentMeters(
        float $latitude,
        float $longitude,
        float $aLatitude,
        float $aLongitude,
        float $bLatitude,
        float $bLongitude
    ): float {
        $scale = cos(deg2rad($latitude));

        $px = $longitude * $scale * 111320.0;
        $py = $latitude * 110540.0;
        $ax = $aLongitude * $scale * 111320.0;
        $ay = $aLatitude * 110540.0;
        $bx = $bLongitude * $scale * 111320.0;
        $by = $bLatitude * 110540.0;

        $dx = $bx - $ax;
        $dy = $by - $ay;
        $lengthSquared = ($dx * $dx) + ($dy * $dy);

        if ($lengthSquared == 0.0) {
            return sqrt((($px - $ax) ** 2) + (($py - $ay) ** 2));
        }

        $t = ((($px - $ax) * $dx) + (($py - $ay) * $dy)) / $lengthSquared;
        $t = max(0.0, min(1.0, $t));

        $closestX = $ax + ($t * $dx);
        $closestY = $ay + ($t * $dy);

        return sqrt((($px - $closestX) ** 2) + (($py - $closestY) ** 2));
    }
}
