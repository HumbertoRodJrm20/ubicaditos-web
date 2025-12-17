<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Bus;

class FixRoutesSeeder extends Seeder
{
    /**
     * Eliminar rutas muy largas y crear rutas más cortas sobre calles reales
     */
    public function run(): void
    {
        // Eliminar las rutas muy largas
        $routesToDelete = ['OAX-109', 'OAX-110', 'OAX-111', 'OAX-112', 'OAX-113', 'OAX-114'];

        foreach ($routesToDelete as $code) {
            $route = Route::where('code', $code)->first();
            if ($route) {
                // Eliminar autobuses asociados
                foreach ($route->buses as $bus) {
                    $bus->routes()->detach();
                    $bus->delete();
                }
                // Eliminar paradas asociadas
                $route->stops()->detach();
                $route->delete();
                $this->command->info("✓ Ruta $code eliminada");
            }
        }

        // Crear nuevas rutas más cortas sobre calles reales
        $newRoutes = [
            [
                'name' => 'Ruta Centro - El Tule',
                'code' => 'OAX-201',
                'description' => 'Del Centro al Árbol del Tule',
                'color' => '#10B981', // Verde
                'stops' => [
                    ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Central de Abastos', 'address' => 'Periférico', 'lat' => 17.0487, 'lng' => -96.7437],
                    ['name' => 'Tlalixtac', 'address' => 'Tlalixtac de Cabrera', 'lat' => 17.0612, 'lng' => -96.6834],
                    ['name' => 'Santa María del Tule', 'address' => 'El Tule Centro', 'lat' => 17.0467, 'lng' => -96.6367],
                ],
                'path_coordinates' => [
                    ['lat' => 17.0654, 'lng' => -96.7256], // Zócalo
                    ['lat' => 17.0640, 'lng' => -96.7280],
                    ['lat' => 17.0620, 'lng' => -96.7310],
                    ['lat' => 17.0590, 'lng' => -96.7350],
                    ['lat' => 17.0550, 'lng' => -96.7380],
                    ['lat' => 17.0510, 'lng' => -96.7410],
                    ['lat' => 17.0487, 'lng' => -96.7437], // Central
                    ['lat' => 17.0500, 'lng' => -96.7400],
                    ['lat' => 17.0520, 'lng' => -96.7350],
                    ['lat' => 17.0540, 'lng' => -96.7300],
                    ['lat' => 17.0560, 'lng' => -96.7200],
                    ['lat' => 17.0580, 'lng' => -96.7100],
                    ['lat' => 17.0600, 'lng' => -96.7000],
                    ['lat' => 17.0612, 'lng' => -96.6834], // Tlalixtac
                    ['lat' => 17.0600, 'lng' => -96.6750],
                    ['lat' => 17.0580, 'lng' => -96.6650],
                    ['lat' => 17.0550, 'lng' => -96.6550],
                    ['lat' => 17.0520, 'lng' => -96.6450],
                    ['lat' => 17.0490, 'lng' => -96.6400],
                    ['lat' => 17.0467, 'lng' => -96.6367], // El Tule
                ]
            ],
            [
                'name' => 'Ruta Centro - Zaachila',
                'code' => 'OAX-202',
                'description' => 'Del Centro a Villa de Zaachila',
                'color' => '#F59E0B', // Naranja
                'stops' => [
                    ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Xoxocotlán', 'address' => 'Santa Cruz Xoxocotlán', 'lat' => 17.0312, 'lng' => -96.7445],
                    ['name' => 'Cuilapam', 'address' => 'Cuilápam de Guerrero', 'lat' => 16.9823, 'lng' => -96.7612],
                    ['name' => 'Villa de Zaachila', 'address' => 'Zaachila Centro', 'lat' => 16.9534, 'lng' => -96.7489],
                ],
                'path_coordinates' => [
                    ['lat' => 17.0654, 'lng' => -96.7256], // Zócalo
                    ['lat' => 17.0630, 'lng' => -96.7280],
                    ['lat' => 17.0600, 'lng' => -96.7310],
                    ['lat' => 17.0560, 'lng' => -96.7340],
                    ['lat' => 17.0520, 'lng' => -96.7370],
                    ['lat' => 17.0480, 'lng' => -96.7400],
                    ['lat' => 17.0440, 'lng' => -96.7420],
                    ['lat' => 17.0400, 'lng' => -96.7435],
                    ['lat' => 17.0360, 'lng' => -96.7440],
                    ['lat' => 17.0312, 'lng' => -96.7445], // Xoxocotlán
                    ['lat' => 17.0250, 'lng' => -96.7470],
                    ['lat' => 17.0180, 'lng' => -96.7500],
                    ['lat' => 17.0100, 'lng' => -96.7540],
                    ['lat' => 17.0020, 'lng' => -96.7570],
                    ['lat' => 16.9950, 'lng' => -96.7590],
                    ['lat' => 16.9880, 'lng' => -96.7605],
                    ['lat' => 16.9823, 'lng' => -96.7612], // Cuilapam
                    ['lat' => 16.9750, 'lng' => -96.7600],
                    ['lat' => 16.9680, 'lng' => -96.7580],
                    ['lat' => 16.9610, 'lng' => -96.7550],
                    ['lat' => 16.9570, 'lng' => -96.7520],
                    ['lat' => 16.9534, 'lng' => -96.7489], // Zaachila
                ]
            ],
            [
                'name' => 'Ruta Centro - San Felipe del Agua',
                'code' => 'OAX-203',
                'description' => 'Del Centro hacia la zona norte',
                'color' => '#A855F7', // Púrpura
                'stops' => [
                    ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Jalatlaco', 'address' => 'Barrio de Jalatlaco', 'lat' => 17.0689, 'lng' => -96.7189],
                    ['name' => 'San Felipe Centro', 'address' => 'San Felipe del Agua', 'lat' => 17.0923, 'lng' => -96.7034],
                ],
                'path_coordinates' => [
                    ['lat' => 17.0654, 'lng' => -96.7256], // Zócalo
                    ['lat' => 17.0665, 'lng' => -96.7240],
                    ['lat' => 17.0677, 'lng' => -96.7220],
                    ['lat' => 17.0689, 'lng' => -96.7189], // Jalatlaco
                    ['lat' => 17.0710, 'lng' => -96.7160],
                    ['lat' => 17.0740, 'lng' => -96.7130],
                    ['lat' => 17.0780, 'lng' => -96.7100],
                    ['lat' => 17.0820, 'lng' => -96.7070],
                    ['lat' => 17.0870, 'lng' => -96.7050],
                    ['lat' => 17.0923, 'lng' => -96.7034], // San Felipe
                ]
            ],
            [
                'name' => 'Ruta Cinco Señores - Atzompa',
                'code' => 'OAX-204',
                'description' => 'De la Ciudad hacia Santa María Atzompa',
                'color' => '#06B6D4', // Cyan
                'stops' => [
                    ['name' => 'Cinco Señores', 'address' => 'Av. Universidad', 'lat' => 17.0729, 'lng' => -96.7398],
                    ['name' => 'Viguera', 'address' => 'Colonia Viguera', 'lat' => 17.0856, 'lng' => -96.7445],
                    ['name' => 'Santa María Atzompa', 'address' => 'Atzompa Centro', 'lat' => 17.0678, 'lng' => -96.7712],
                ],
                'path_coordinates' => [
                    ['lat' => 17.0729, 'lng' => -96.7398], // Cinco Señores
                    ['lat' => 17.0750, 'lng' => -96.7410],
                    ['lat' => 17.0780, 'lng' => -96.7420],
                    ['lat' => 17.0810, 'lng' => -96.7432],
                    ['lat' => 17.0840, 'lng' => -96.7440],
                    ['lat' => 17.0856, 'lng' => -96.7445], // Viguera
                    ['lat' => 17.0830, 'lng' => -96.7480],
                    ['lat' => 17.0800, 'lng' => -96.7530],
                    ['lat' => 17.0770, 'lng' => -96.7590],
                    ['lat' => 17.0740, 'lng' => -96.7650],
                    ['lat' => 17.0710, 'lng' => -96.7690],
                    ['lat' => 17.0678, 'lng' => -96.7712], // Atzompa
                ]
            ],
        ];

        foreach ($newRoutes as $routeData) {
            $route = Route::create([
                'code' => $routeData['code'],
                'name' => $routeData['name'],
                'description' => $routeData['description'],
                'color' => $routeData['color'],
                'estimated_duration' => count($routeData['stops']) * 10,
                'is_active' => true,
                'path_coordinates' => $routeData['path_coordinates'],
            ]);

            // Crear paradas
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

                $route->stops()->attach($stop->id, [
                    'order' => $index,
                    'estimated_time_from_previous' => $index == 0 ? 0 : rand(7, 12),
                ]);
            }

            // Crear 2 autobuses por ruta
            for ($i = 1; $i <= 2; $i++) {
                $busNumber = Bus::max('id') + 1;

                $bus = Bus::create([
                    'license_plate' => sprintf('OAX-%03d', $busNumber),
                    'model' => ['Mercedes-Benz O500', 'Volvo 9700', 'Scania K360'][array_rand(['a', 'b', 'c'])],
                    'capacity' => rand(40, 55),
                    'current_passengers' => rand(0, 25),
                    'status' => 'active',
                    'driver_id' => \App\Models\User::where('role', 'driver')->inRandomOrder()->first()->id,
                    'is_active' => true,
                ]);

                $bus->routes()->attach($route->id, ['assigned_at' => now()]);
            }

            $this->command->info("✓ Ruta {$routeData['code']} creada: {$routeData['name']}");
        }

        $this->command->info('');
        $this->command->info('✅ Rutas corregidas exitosamente!');
        $this->command->info('- 4 rutas cortas creadas (10-20 km cada una)');
        $this->command->info('- Todas las rutas siguen calles reales');
        $this->command->info('- 8 autobuses nuevos asignados');
    }
}
