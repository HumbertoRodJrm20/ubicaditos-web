<?php

namespace App\Services;

/**
 * Cálculos de distancia geodésica mediante Haversine.
 *
 * La misma fórmula se usa para estimar minutos entre paradas y para medir la
 * distancia real recorrida por un viaje; centralizarla evita que dos reglas
 * distintas produzcan resultados distintos.
 */
class GeoDistanceService
{
    /** Radio medio de la Tierra en kilómetros. */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Distancia entre dos coordenadas, en kilómetros.
     */
    public function distanceKm(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $lat1 = deg2rad($latitude1);
        $lat2 = deg2rad($latitude2);
        $latDelta = $lat2 - $lat1;
        $lngDelta = deg2rad($longitude2 - $longitude1);

        $a = sin($latDelta / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Suma de distancias entre posiciones consecutivas de una trayectoria.
     *
     * @param  mixed  $path  posiciones con claves latitude/longitude (o lat/lng)
     */
    public function pathDistanceKm(mixed $path): float
    {
        if (! is_array($path) || count($path) < 2) {
            return 0.0;
        }

        $points = [];

        foreach ($path as $point) {
            if (! is_array($point)) {
                continue;
            }

            $latitude = $point['latitude'] ?? $point['lat'] ?? null;
            $longitude = $point['longitude'] ?? $point['lng'] ?? $point['lon'] ?? null;

            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                continue;
            }

            $points[] = [(float) $latitude, (float) $longitude];
        }

        if (count($points) < 2) {
            return 0.0;
        }

        $total = 0.0;

        for ($index = 1; $index < count($points); $index++) {
            [$previousLat, $previousLng] = $points[$index - 1];
            [$latitude, $longitude] = $points[$index];

            $total += $this->distanceKm($previousLat, $previousLng, $latitude, $longitude);
        }

        return $total;
    }
}
