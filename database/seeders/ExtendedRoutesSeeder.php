<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Bus;
use App\Models\User;

class ExtendedRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rutas extendidas que llegan más lejos
        $routes = [
            [
                'name' => 'Ruta Centro - Tlacolula',
                'code' => 'OAX-109',
                'description' => 'Ruta al Valle de Tlacolula pasando por comunidades',
                'color' => '#10B981', // Verde
                'stops' => [
                    ['name' => 'Zócalo de Oaxaca', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Tule', 'address' => 'Santa María del Tule', 'lat' => 17.0467, 'lng' => -96.6367],
                    ['name' => 'Dainzú', 'address' => 'San Pedro Dainzú', 'lat' => 17.0123, 'lng' => -96.5876],
                    ['name' => 'Teotitlán del Valle', 'address' => 'Teotitlán del Valle', 'lat' => 17.0543, 'lng' => -96.5432],
                    ['name' => 'Tlacochahuaya', 'address' => 'San Jerónimo Tlacochahuaya', 'lat' => 17.0345, 'lng' => -96.5123],
                    ['name' => 'Tlacolula Centro', 'address' => 'Mercado de Tlacolula', 'lat' => 16.9578, 'lng' => -96.4789],
                ]
            ],
            [
                'name' => 'Ruta Centro - Zaachila - Ocotlán',
                'code' => 'OAX-110',
                'description' => 'Ruta hacia el sur por los valles centrales',
                'color' => '#3B82F6', // Azul
                'stops' => [
                    ['name' => 'Zócalo de Oaxaca', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Xoxocotlán', 'address' => 'Santa Cruz Xoxocotlán', 'lat' => 17.0312, 'lng' => -96.7445],
                    ['name' => 'Atzompa', 'address' => 'Santa María Atzompa', 'lat' => 16.9989, 'lng' => -96.7534],
                    ['name' => 'Cuilapam', 'address' => 'Cuilápam de Guerrero', 'lat' => 16.9823, 'lng' => -96.7612],
                    ['name' => 'Zaachila Centro', 'address' => 'Villa de Zaachila', 'lat' => 16.9534, 'lng' => -96.7489],
                    ['name' => 'San Antonino', 'address' => 'San Antonino Castillo Velasco', 'lat' => 16.8923, 'lng' => -96.7234],
                    ['name' => 'Ocotlán Centro', 'address' => 'Mercado de Ocotlán', 'lat' => 16.7945, 'lng' => -96.6734],
                ]
            ],
            [
                'name' => 'Ruta Centro - San Bartolo Coyotepec',
                'code' => 'OAX-111',
                'description' => 'Ruta hacia comunidades artesanales del sur',
                'color' => '#EF4444', // Rojo
                'stops' => [
                    ['name' => 'Zócalo de Oaxaca', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Aeropuerto', 'address' => 'Aeropuerto Internacional Xoxocotlán', 'lat' => 16.9999, 'lng' => -96.7266],
                    ['name' => 'San Agustín Yatareni', 'address' => 'San Agustín Yatareni', 'lat' => 16.9645, 'lng' => -96.7389],
                    ['name' => 'San Bartolo Coyotepec', 'address' => 'San Bartolo Coyotepec Centro', 'lat' => 16.9423, 'lng' => -96.7012],
                ]
            ],
            [
                'name' => 'Ruta Mitla - Centro (Carretera Panamericana)',
                'code' => 'OAX-112',
                'description' => 'Ruta larga hacia Mitla por la carretera federal',
                'color' => '#F59E0B', // Naranja
                'stops' => [
                    ['name' => 'Mitla Zona Arqueológica', 'address' => 'San Pablo Villa de Mitla', 'lat' => 16.9267, 'lng' => -96.3578],
                    ['name' => 'Mitla Centro', 'address' => 'Centro de Mitla', 'lat' => 16.9289, 'lng' => -96.3689],
                    ['name' => 'Tlacolula', 'address' => 'Mercado de Tlacolula', 'lat' => 16.9578, 'lng' => -96.4789],
                    ['name' => 'Santa Ana del Valle', 'address' => 'Santa Ana del Valle', 'lat' => 17.0067, 'lng' => -96.5234],
                    ['name' => 'Tule', 'address' => 'Santa María del Tule', 'lat' => 17.0467, 'lng' => -96.6367],
                    ['name' => 'Tlalixtac', 'address' => 'Tlalixtac de Cabrera', 'lat' => 17.0612, 'lng' => -96.6834],
                    ['name' => 'Central de Abastos', 'address' => 'Periférico Oaxaca', 'lat' => 17.0487, 'lng' => -96.7437],
                    ['name' => 'Zócalo de Oaxaca', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                ]
            ],
            [
                'name' => 'Ruta Centro - San Felipe del Agua',
                'code' => 'OAX-113',
                'description' => 'Ruta hacia la zona norte alta de la ciudad',
                'color' => '#A855F7', // Púrpura
                'stops' => [
                    ['name' => 'Zócalo de Oaxaca', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Jalatlaco', 'address' => 'Barrio de Jalatlaco', 'lat' => 17.0689, 'lng' => -96.7189],
                    ['name' => 'Av. Ferrocarril', 'address' => 'Avenida Ferrocarril', 'lat' => 17.0745, 'lng' => -96.7145],
                    ['name' => 'San Felipe Centro', 'address' => 'San Felipe del Agua Centro', 'lat' => 17.0923, 'lng' => -96.7034],
                    ['name' => 'San Felipe Alto', 'address' => 'Parte alta San Felipe', 'lat' => 17.1067, 'lng' => -96.6945],
                ]
            ],
            [
                'name' => 'Ruta Ixcotel - Salina Cruz (Interurbana)',
                'code' => 'OAX-114',
                'description' => 'Ruta interurbana hacia el Istmo de Tehuantepec',
                'color' => '#06B6D4', // Cyan
                'stops' => [
                    ['name' => 'Terminal Ixcotel', 'address' => 'Santa Lucía del Camino', 'lat' => 17.0734, 'lng' => -96.6945],
                    ['name' => 'Tlacolula de Matamoros', 'address' => 'Carretera 190', 'lat' => 16.9578, 'lng' => -96.4789],
                    ['name' => 'Mitla', 'address' => 'Villa de Mitla', 'lat' => 16.9267, 'lng' => -96.3578],
                    ['name' => 'Tehuantepec', 'address' => 'Santo Domingo Tehuantepec', 'lat' => 16.3223, 'lng' => -95.2389],
                    ['name' => 'Salina Cruz Centro', 'address' => 'Centro de Salina Cruz', 'lat' => 16.1734, 'lng' => -95.1945],
                    ['name' => 'Puerto Salina Cruz', 'address' => 'Puerto Industrial', 'lat' => 16.1623, 'lng' => -95.1856],
                ]
            ],
        ];

        $drivers = User::where('role', 'driver')->get();

        if ($drivers->count() === 0) {
            $this->command->error('❌ No hay conductores disponibles. Ejecuta primero TransportSystemSeeder.');
            return;
        }

        $busModels = ['Mercedes-Benz O500', 'Volvo 9700', 'Scania K360', 'MAN Lion\'s Coach', 'Isuzu Visigo'];
        $busNumber = Bus::max('id') ?? 0;

        foreach ($routes as $routeData) {
            // Crear ruta
            $route = Route::firstOrCreate(
                ['code' => $routeData['code']],
                [
                    'name' => $routeData['name'],
                    'description' => $routeData['description'],
                    'color' => $routeData['color'],
                    'estimated_duration' => count($routeData['stops']) * 8, // 8 minutos por parada aprox
                    'is_active' => true,
                ]
            );

            // Generar coordenadas de ruta
            $pathCoordinates = [];
            foreach ($routeData['stops'] as $stopData) {
                $pathCoordinates[] = [
                    'lat' => $stopData['lat'],
                    'lng' => $stopData['lng']
                ];
            }
            $route->path_coordinates = $pathCoordinates;
            $route->save();

            // Crear paradas y vincularlas a la ruta
            foreach ($routeData['stops'] as $index => $stopData) {
                $stop = Stop::firstOrCreate(
                    [
                        'latitude' => $stopData['lat'],
                        'longitude' => $stopData['lng'],
                    ],
                    [
                        'name' => $stopData['name'],
                        'address' => $stopData['address'],
                        'is_active' => true,
                    ]
                );

                // Vincular parada a la ruta con orden
                if (!$route->stops()->where('stops.id', $stop->id)->exists()) {
                    $route->stops()->attach($stop->id, [
                        'order' => $index,
                        'estimated_time_from_previous' => $index == 0 ? 0 : rand(5, 12),
                    ]);
                }
            }

            // Crear 2-3 autobuses por ruta (más para rutas largas)
            $busCount = count($routeData['stops']) >= 6 ? 3 : 2;

            for ($i = 1; $i <= $busCount; $i++) {
                $busNumber++;
                $driver = $drivers->random();

                $bus = Bus::create([
                    'license_plate' => sprintf('OAX-%03d', $busNumber + 200),
                    'model' => $busModels[array_rand($busModels)],
                    'capacity' => rand(45, 65),
                    'current_passengers' => rand(0, 35),
                    'status' => 'active',
                    'driver_id' => $driver->id,
                    'is_active' => true,
                ]);

                // Vincular autobús a la ruta
                $bus->routes()->attach($route->id, [
                    'assigned_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ 6 rutas extendidas creadas exitosamente!');
        $this->command->info('- Ruta Centro - Tlacolula (OAX-109) - 6 paradas');
        $this->command->info('- Ruta Centro - Zaachila - Ocotlán (OAX-110) - 7 paradas');
        $this->command->info('- Ruta Centro - San Bartolo Coyotepec (OAX-111) - 4 paradas');
        $this->command->info('- Ruta Mitla - Centro (OAX-112) - 8 paradas');
        $this->command->info('- Ruta Centro - San Felipe del Agua (OAX-113) - 5 paradas');
        $this->command->info('- Ruta Ixcotel - Salina Cruz Interurbana (OAX-114) - 6 paradas');
        $this->command->info('- ' . ($busNumber - Bus::where('license_plate', 'like', 'OAX-2%')->count()) . ' autobuses adicionales creados');
    }
}
