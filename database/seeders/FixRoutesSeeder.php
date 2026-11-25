<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Bus;

class FixRoutesSeeder extends Seeder
{
    /**
     * Eliminar rutas muy largas y crear rutas más cortas.
     * El trazado no se inventa aquí: se genera con `routes:generate-paths`.
     */
    public function run(): void
    {
        // Eliminar únicamente las rutas largas creadas por ExtendedRoutesSeeder.
        // Antes se borraba por código (OAX-109..114), lo que también eliminaba
        // rutas distintas de DatabaseSeeder que usan esos mismos códigos.
        $routeNamesToDelete = [
            'Ruta Centro - Tlacolula',
            'Ruta Centro - Zaachila - Ocotlán',
            'Ruta Centro - San Bartolo Coyotepec',
            'Ruta Mitla - Centro (Carretera Panamericana)',
            'Ruta Centro - San Felipe del Agua',
            'Ruta Ixcotel - Salina Cruz (Interurbana)',
        ];

        foreach ($routeNamesToDelete as $name) {
            $route = Route::where('name', $name)->first();
            if ($route) {
                // Eliminar autobuses asociados
                foreach ($route->buses as $bus) {
                    $bus->routes()->detach();
                    $bus->delete();
                }
                // Eliminar paradas asociadas
                $route->stops()->detach();
                $route->delete();
                $this->command->info("✓ Ruta '{$name}' eliminada");
            }
        }

        // Crear nuevas rutas más cortas. El trazado queda vacío y se genera
        // después con `php artisan routes:generate-paths --force`.
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
            ],
        ];

        foreach ($newRoutes as $routeData) {
            $route = Route::firstOrCreate(
                ['code' => $routeData['code']],
                [
                    'name' => $routeData['name'],
                    'description' => $routeData['description'],
                    'color' => $routeData['color'],
                    'estimated_duration' => count($routeData['stops']) * 10,
                    'is_active' => true,
                    'path_coordinates' => null,
                ]
            );

            if (!$route->wasRecentlyCreated) {
                $this->command->line("• Ruta {$routeData['code']} ya existe, se conserva.");
                continue;
            }

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
        $this->command->info('- Trazados pendientes: ejecuta routes:generate-paths --force');
        $this->command->info('- 8 autobuses nuevos asignados');
    }
}
