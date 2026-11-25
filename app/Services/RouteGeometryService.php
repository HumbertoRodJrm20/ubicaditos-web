<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Genera el trazado de una ruta usando OSRM.
 *
 * Extrae la lógica que antes vivía sólo en `routes:generate-paths` para que el
 * comando y el Estudio de rutas compartan exactamente el mismo camino. Nunca
 * sustituye un trazado válido por una respuesta vacía o inválida.
 */
class RouteGeometryService
{
    /**
     * @return array{status: string, points: int, message: string}
     */
    public function generate(Route $route, bool $force = false): array
    {
        // Sólo las paradas principales definen el trazado; las de alta
        // frecuencia están muestreadas sobre él y engrosarían la petición.
        $stops = $route->getOrderedStops(false);

        if ($stops->count() < 2) {
            return [
                'status' => 'skipped',
                'points' => 0,
                'message' => 'La ruta necesita al menos dos paradas para generar un trazado.',
            ];
        }

        $existing = $this->validPath($route->path_coordinates);

        if (! $force && $existing !== []) {
            return [
                'status' => 'skipped',
                'points' => count($existing),
                'message' => 'La ruta ya tiene un trazado válido; no se consultó OSRM.',
            ];
        }

        $waypoints = $stops->map(
            fn ($stop) => (float) $stop->longitude . ',' . (float) $stop->latitude
        )->join(';');

        $url = rtrim((string) config('services.osrm.url', 'https://router.project-osrm.org'), '/')
            . '/route/v1/driving/' . $waypoints;

        try {
            $response = Http::timeout((int) config('services.osrm.timeout', 30))
                ->get($url, [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }

        if (! $response->successful() || $response->json('code') !== 'Ok') {
            return $this->failure('OSRM no devolvió un trazado válido.');
        }

        $path = $this->validPath($response->json('routes.0.geometry.coordinates') ?? []);

        if (count($path) < 2) {
            return $this->failure('OSRM devolvió una geometría vacía o inválida.');
        }

        $route->path_coordinates = $path;
        $route->estimated_duration = $this->estimateDurationMinutes($path, $stops->count());
        $route->save();

        return [
            'status' => 'generated',
            'points' => count($path),
            'message' => 'Trazado generado y guardado correctamente.',
        ];
    }

    /**
     * @return array{status: string, points: int, message: string}
     */
    private function failure(string $message): array
    {
        return [
            'status' => 'failed',
            'points' => 0,
            'message' => $message,
        ];
    }

    /**
     * Normaliza y valida puntos GeoJSON [lng, lat] o `{lat, lng}`.
     *
     * @param  mixed  $points
     * @return array<int, array{lat: float, lng: float}>
     */
    private function validPath(mixed $points): array
    {
        if (! is_array($points)) {
            return [];
        }

        $path = [];

        foreach ($points as $point) {
            if (is_array($point) && array_is_list($point) && count($point) >= 2) {
                $lng = $this->number($point[0]);
                $lat = $this->number($point[1]);
            } else {
                $lng = $this->number($point['lng'] ?? $point['longitude'] ?? null);
                $lat = $this->number($point['lat'] ?? $point['latitude'] ?? null);
            }

            if ($lat === null || $lng === null) {
                continue;
            }

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
                continue;
            }

            $path[] = ['lat' => $lat, 'lng' => $lng];
        }

        return $path;
    }

    private function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $path
     */
    private function estimateDurationMinutes(array $path, int $stopCount): int
    {
        $distanceKm = 0.0;

        for ($i = 1; $i < count($path); $i += 1) {
            $distanceKm += $this->distanceKm(
                $path[$i - 1]['lat'],
                $path[$i - 1]['lng'],
                $path[$i]['lat'],
                $path[$i]['lng']
            );
        }

        // 20 km/h promedio urbano más una pequeña espera por parada.
        return max(1, (int) round(($distanceKm / 20) * 60 + ($stopCount * 0.5)));
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radius = 6371.0;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
