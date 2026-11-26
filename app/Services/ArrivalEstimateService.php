<?php

namespace App\Services;

use App\Models\Bus;
use App\Models\Stop;
use Illuminate\Support\Carbon;

/**
 * HU-009/HU-30: estimaciones de llegada por autobús.
 *
 * Supuestos documentados (sin OSRM en tiempo de consulta):
 * - distancia Haversine en línea recta desde la última posición conocida;
 * - velocidad media de 20 km/h y sin tráfico;
 * - resultado siempre redondeado al alza;
 * - una posición con más de cinco minutos no produce un tiempo preciso.
 */
class ArrivalEstimateService
{
    private const AVERAGE_SPEED_KMH = 20.0;

    public function __construct(private readonly GeoDistanceService $geoDistance) {}

    /**
     * @return array<string, mixed>
     */
    public function forStop(Stop $stop): array
    {
        $routeIds = $stop->routes()->pluck('routes.id')->map(fn ($id) => (int) $id)->all();

        $buses = $routeIds === []
            ? collect()
            : Bus::query()
                ->where('status', 'active')
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->whereHas('routes', fn ($query) => $query->whereIn('routes.id', $routeIds))
                ->with('routes')
                ->get();

        $estimates = [];
        $hasStaleBus = false;

        foreach ($buses as $bus) {
            $route = $bus->routes->first(fn ($candidate) => in_array((int) $candidate->id, $routeIds, true));

            if ($route === null) {
                continue;
            }

            $distanceKm = $this->geoDistance->distanceKm(
                (float) $bus->current_latitude,
                (float) $bus->current_longitude,
                (float) $stop->latitude,
                (float) $stop->longitude
            );

            $fresh = $bus->isLocationFresh();
            $hasStaleBus = $hasStaleBus || ! $fresh;

            $estimates[] = [
                'id' => $bus->id,
                'license_plate' => $bus->license_plate,
                'route_id' => $route->id,
                'route_name' => $route->name,
                'route_color' => $route->color,
                'eta_minutes' => $fresh
                    ? max(1, (int) ceil(($distanceKm / self::AVERAGE_SPEED_KMH) * 60))
                    : null,
                'distance_km' => round($distanceKm, 2),
                'last_update' => $bus->last_location_update?->toISOString(),
                'fresh' => $fresh,
                'estimated' => true,
                'uncertain' => ! $fresh,
            ];
        }

        usort($estimates, function (array $left, array $right) {
            $leftEta = $left['eta_minutes'] ?? PHP_INT_MAX;
            $rightEta = $right['eta_minutes'] ?? PHP_INT_MAX;

            return $leftEta <=> $rightEta;
        });

        $uncertain = $estimates === [] || $hasStaleBus;

        return [
            'stop_id' => $stop->id,
            'stop_name' => $stop->name,
            'generated_at' => Carbon::now()->toIso8601String(),
            'estimated' => $estimates !== [],
            'uncertain' => $uncertain,
            'notice' => $this->notice($estimates === [], $hasStaleBus),
            'buses' => $estimates,
        ];
    }

    private function notice(bool $withoutBus, bool $hasStaleBus): ?string
    {
        if ($withoutBus) {
            return 'No hay autobuses activos hacia esta parada; los tiempos de llegada son aproximados y pueden no estar disponibles.';
        }

        if ($hasStaleBus) {
            return 'Tiempos aproximados: la última ubicación de uno o más autobuses tiene más de cinco minutos; no mostramos una hora precisa.';
        }

        return null;
    }
}
