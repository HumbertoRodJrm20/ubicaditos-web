<?php

namespace Database\Seeders;

/**
 * Agrega paradas de alta frecuencia (aproximadamente cada 500 m) a cada ruta.
 *
 * Los puntos salen del trazado guardado y toman el nombre de la calle desde el
 * paso a paso de OSRM. Se mantiene la posición final dentro del recorrido para
 * que el seeder sea reproducible.
 */
class FrequentRouteStopsSeeder extends AdditionalRouteStopsSeeder
{
    protected string $dataFile = 'dense_route_stops.json';

    protected string $label = 'Paradas de alta frecuencia';

    protected bool $isFrequent = true;
}
