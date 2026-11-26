<?php

namespace App\Services;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Escribe la secuencia de paradas de una ruta en route_stop.
 *
 * route_stop.order es una sola secuencia que mezcla paradas principales
 * (is_frequent = false, definen el trazado) y de alta frecuencia
 * (is_frequent = true). Este servicio cambia solo las principales y conserva
 * las de alta frecuencia "ancladas": cada una sigue a la parada principal que
 * la precedía. Si esa principal se elimina, pasa a la anterior que sobrevive.
 */
class RouteStopSyncService
{
    /** Mismo criterio que RouteCorridorSeeder: 15 km/h promedio urbano. */
    private const MINUTES_PER_KM = 4;

    public function __construct(private readonly GeoDistanceService $geoDistance = new GeoDistanceService) {}

    /**
     * Reemplaza la secuencia de paradas principales de una ruta.
     *
     * @param  array<int, int>  $mainStopIds  ids en el orden deseado
     * @param  array<int, int>  $minutes  stop_id => minutos desde la principal anterior (opcional)
     * @return array{added: int[], removed: int[], main_sequence_changed: bool, frequent_review_needed: bool}
     *
     * @throws InvalidArgumentException si hay ids repetidos o paradas inexistentes
     */
    public function syncMainStops(Route $route, array $mainStopIds, array $minutes = []): array
    {
        $mainStopIds = array_values(array_map('intval', $mainStopIds));
        $this->assertUnique($mainStopIds);

        return DB::transaction(function () use ($route, $mainStopIds, $minutes) {
            $stops = Stop::whereIn('id', $mainStopIds)->get()->keyBy('id');

            if ($stops->count() !== count($mainStopIds)) {
                throw new InvalidArgumentException('Una o más paradas no existen.');
            }

            $existing = DB::table('route_stop')
                ->where('route_id', $route->id)
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            $rows = $existing->map(fn ($row) => [
                'stop_id' => (int) $row->stop_id,
                'is_frequent' => (bool) $row->is_frequent,
            ])->all();

            // Principales actuales: stop_id => principal anterior (o null) y minutos.
            $oldPrevious = [];
            $oldMinutes = [];
            $previous = null;

            foreach ($existing as $row) {
                if ($row->is_frequent) {
                    continue;
                }

                $oldPrevious[(int) $row->stop_id] = $previous;
                $oldMinutes[(int) $row->stop_id] = $row->estimated_time_from_previous;
                $previous = (int) $row->stop_id;
            }

            $oldMainIds = array_keys($oldPrevious);
            $attached = array_map(fn ($row) => $row['stop_id'], $rows);
            $removed = array_values(array_diff($oldMainIds, $mainStopIds));
            $added = array_values(array_diff($mainStopIds, $attached));
            $sequenceChanged = $oldMainIds !== $mainStopIds;

            $plan = $this->planSequence($rows, $mainStopIds);
            $hasFrequent = in_array(true, array_column($plan, 'is_frequent'), true);

            $mainMinutes = [];

            foreach ($mainStopIds as $index => $stopId) {
                $previousId = $index > 0 ? $mainStopIds[$index - 1] : null;

                if ($previousId === null) {
                    $mainMinutes[$stopId] = 0;
                } elseif (array_key_exists($stopId, $minutes)) {
                    $mainMinutes[$stopId] = max(0, (int) $minutes[$stopId]);
                } elseif (
                    array_key_exists($stopId, $oldPrevious)
                    && $oldPrevious[$stopId] === $previousId
                    && $oldMinutes[$stopId] !== null
                ) {
                    $mainMinutes[$stopId] = (int) $oldMinutes[$stopId];
                } else {
                    $km = $this->geoDistance->distanceKm(
                        (float) $stops[$previousId]->latitude,
                        (float) $stops[$previousId]->longitude,
                        (float) $stops[$stopId]->latitude,
                        (float) $stops[$stopId]->longitude,
                    );
                    $mainMinutes[$stopId] = max(1, (int) round($km * self::MINUTES_PER_KM));
                }
            }

            if ($removed !== []) {
                DB::table('route_stop')
                    ->where('route_id', $route->id)
                    ->whereIn('stop_id', $removed)
                    ->delete();
            }

            $now = now();

            foreach ($plan as $index => $item) {
                $stopId = $item['stop_id'];

                $attributes = [
                    'order' => $index,
                    'is_frequent' => $item['is_frequent'],
                    'updated_at' => $now,
                ];

                if (! $item['is_frequent']) {
                    $attributes['estimated_time_from_previous'] = $mainMinutes[$stopId];
                }

                if (in_array($stopId, $attached, true)) {
                    DB::table('route_stop')
                        ->where('route_id', $route->id)
                        ->where('stop_id', $stopId)
                        ->update($attributes);
                } else {
                    DB::table('route_stop')->insert($attributes + [
                        'route_id' => $route->id,
                        'stop_id' => $stopId,
                        'created_at' => $now,
                    ]);
                }
            }

            return [
                'added' => $added,
                'removed' => $removed,
                'main_sequence_changed' => $sequenceChanged,
                'frequent_review_needed' => $sequenceChanged && $hasFrequent,
            ];
        });
    }

    /**
     * Calcula la nueva secuencia combinada sin tocar la base de datos.
     *
     * @param  array<int, array{stop_id: int, is_frequent: bool}>  $rows  secuencia actual, ya ordenada
     * @param  array<int, int>  $mainStopIds
     * @return array<int, array{stop_id: int, is_frequent: bool}>
     */
    public function planSequence(array $rows, array $mainStopIds): array
    {
        $mainStopIds = array_values(array_map('intval', $mainStopIds));
        $this->assertUnique($mainStopIds);

        $wanted = array_flip($mainStopIds);
        $leading = [];
        $blocks = [];
        $anchor = null;

        foreach ($rows as $row) {
            $id = (int) $row['stop_id'];

            if (! $row['is_frequent']) {
                if (isset($wanted[$id])) {
                    $anchor = $id;
                    $blocks[$id] = [];
                }

                continue;
            }

            if (isset($wanted[$id])) {
                continue; // se promueve a principal
            }

            if ($anchor === null) {
                $leading[] = $id;
            } else {
                $blocks[$anchor][] = $id;
            }
        }

        $plan = [];

        foreach ($leading as $id) {
            $plan[] = ['stop_id' => $id, 'is_frequent' => true];
        }

        foreach ($mainStopIds as $id) {
            $plan[] = ['stop_id' => $id, 'is_frequent' => false];

            foreach ($blocks[$id] ?? [] as $frequentId) {
                $plan[] = ['stop_id' => $frequentId, 'is_frequent' => true];
            }
        }

        return $plan;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function assertUnique(array $ids): void
    {
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Una parada no puede repetirse en la secuencia.');
        }
    }
}
