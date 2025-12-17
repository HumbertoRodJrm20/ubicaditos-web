<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;

class TransportSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios admin, conductores y pasajeros
        $admin = User::firstOrCreate(
            ['email' => 'admin@ubicaditos.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $drivers = [];
        for ($i = 1; $i <= 10; $i++) {
            $drivers[] = User::firstOrCreate(
                ['email' => "conductor{$i}@ubicaditos.com"],
                [
                    'name' => "Conductor {$i}",
                    'password' => bcrypt('password'),
                    'role' => 'driver',
                    'is_active' => true,
                ]
            );
        }

        $passenger = User::firstOrCreate(
            ['email' => 'pasajero@ubicaditos.com'],
            [
                'name' => 'Pasajero Demo',
                'password' => bcrypt('password'),
                'role' => 'passenger',
                'is_active' => true,
            ]
        );

        // Crear rutas con paradas en Ciudad de México
        $routes = [
            [
                'name' => 'Ruta 1 - Centro - Polanco',
                'code' => 'R1',
                'description' => 'Del Centro Histórico a Polanco',
                'color' => '#3b82f6', // Azul
                'stops' => [
                    ['name' => 'Zócalo', 'lat' => 19.4326, 'lng' => -99.1332],
                    ['name' => 'Bellas Artes', 'lat' => 19.4354, 'lng' => -99.1419],
                    ['name' => 'Alameda Central', 'lat' => 19.4361, 'lng' => -99.1426],
                    ['name' => 'Reforma', 'lat' => 19.4270, 'lng' => -99.1676],
                    ['name' => 'Ángel de la Independencia', 'lat' => 19.4270, 'lng' => -99.1676],
                    ['name' => 'Polanco', 'lat' => 19.4338, 'lng' => -99.1870],
                ]
            ],
            [
                'name' => 'Ruta 2 - Chapultepec - Coyoacán',
                'code' => 'R2',
                'description' => 'De Chapultepec a Coyoacán',
                'color' => '#10b981', // Verde
                'stops' => [
                    ['name' => 'Bosque de Chapultepec', 'lat' => 19.4204, 'lng' => -99.1895],
                    ['name' => 'Auditorio Nacional', 'lat' => 19.4256, 'lng' => -99.1915],
                    ['name' => 'Condesa', 'lat' => 19.4110, 'lng' => -99.1718],
                    ['name' => 'Roma Norte', 'lat' => 19.4152, 'lng' => -99.1628],
                    ['name' => 'San Ángel', 'lat' => 19.3483, 'lng' => -99.1896],
                    ['name' => 'Coyoacán Centro', 'lat' => 19.3500, 'lng' => -99.1622],
                ]
            ],
            [
                'name' => 'Ruta 3 - Norte - Sur',
                'code' => 'R3',
                'description' => 'De Indios Verdes a Taxqueña',
                'color' => '#ef4444', // Rojo
                'stops' => [
                    ['name' => 'Indios Verdes', 'lat' => 19.5950, 'lng' => -99.1195],
                    ['name' => 'Instituto Politécnico Nacional', 'lat' => 19.5045, 'lng' => -99.1469],
                    ['name' => 'Centro Histórico', 'lat' => 19.4326, 'lng' => -99.1332],
                    ['name' => 'Pino Suárez', 'lat' => 19.4246, 'lng' => -99.1331],
                    ['name' => 'Chabacano', 'lat' => 19.4090, 'lng' => -99.1357],
                    ['name' => 'Taxqueña', 'lat' => 19.3267, 'lng' => -99.1877],
                ]
            ],
            [
                'name' => 'Ruta 4 - Oriente - Poniente',
                'code' => 'R4',
                'description' => 'De Pantitlán a Observatorio',
                'color' => '#f59e0b', // Amarillo
                'stops' => [
                    ['name' => 'Pantitlán', 'lat' => 19.4154, 'lng' => -99.0726],
                    ['name' => 'Aeropuerto', 'lat' => 19.4340, 'lng' => -99.0874],
                    ['name' => 'Centro Histórico', 'lat' => 19.4326, 'lng' => -99.1332],
                    ['name' => 'Juárez', 'lat' => 19.4343, 'lng' => -99.1477],
                    ['name' => 'Chapultepec', 'lat' => 19.4204, 'lng' => -99.1895],
                    ['name' => 'Observatorio', 'lat' => 19.3988, 'lng' => -99.2036],
                ]
            ],
            [
                'name' => 'Ruta 5 - Santa Fe Express',
                'code' => 'R5',
                'description' => 'Del Centro a Santa Fe',
                'color' => '#8b5cf6', // Morado
                'stops' => [
                    ['name' => 'Zócalo', 'lat' => 19.4326, 'lng' => -99.1332],
                    ['name' => 'Reforma', 'lat' => 19.4270, 'lng' => -99.1676],
                    ['name' => 'Chapultepec', 'lat' => 19.4204, 'lng' => -99.1895],
                    ['name' => 'Observatorio', 'lat' => 19.3988, 'lng' => -99.2036],
                    ['name' => 'Santa Fe', 'lat' => 19.3598, 'lng' => -99.2590],
                ]
            ],
        ];

        foreach ($routes as $routeData) {
            // Crear ruta
            $route = Route::firstOrCreate(
                ['code' => $routeData['code']],
                [
                    'name' => $routeData['name'],
                    'description' => $routeData['description'],
                    'color' => $routeData['color'],
                    'estimated_duration' => 45,
                    'is_active' => true,
                ]
            );

            // Crear paradas y vincularlas a la ruta
            foreach ($routeData['stops'] as $index => $stopData) {
                $stop = Stop::firstOrCreate(
                    [
                        'name' => $stopData['name'],
                        'latitude' => $stopData['lat'],
                        'longitude' => $stopData['lng'],
                    ],
                    [
                        'address' => $stopData['name'] . ', Ciudad de México',
                        'is_active' => true,
                    ]
                );

                // Vincular parada a la ruta con orden
                if (!$route->stops()->where('stops.id', $stop->id)->exists()) {
                    $route->stops()->attach($stop->id, [
                        'order' => $index + 1,
                        'estimated_time_from_previous' => $index == 0 ? 0 : rand(5, 15),
                    ]);
                }
            }
        }

        // Crear autobuses (2 por ruta = 10 autobuses)
        $busModels = ['Mercedes-Benz', 'Volvo', 'Scania', 'MAN', 'Isuzu'];
        $busNumber = 1;

        foreach (Route::all() as $route) {
            for ($i = 1; $i <= 2; $i++) {
                $driver = $drivers[($busNumber - 1) % count($drivers)];

                $bus = Bus::firstOrCreate(
                    ['license_plate' => sprintf('CDMX-%03d', $busNumber)],
                    [
                        'model' => $busModels[array_rand($busModels)],
                        'capacity' => rand(40, 60),
                        'current_passengers' => rand(0, 30),
                        'status' => 'active',
                        'driver_id' => $driver->id,
                        'is_active' => true,
                    ]
                );

                // Vincular autobús a la ruta
                if (!$bus->routes()->where('routes.id', $route->id)->exists()) {
                    $bus->routes()->attach($route->id, [
                        'assigned_at' => now(),
                    ]);
                }

                $busNumber++;
            }
        }

        $this->command->info('Sistema de transporte poblado exitosamente!');
        $this->command->info('- Rutas creadas: ' . Route::count());
        $this->command->info('- Paradas creadas: ' . Stop::count());
        $this->command->info('- Autobuses creados: ' . Bus::count());
        $this->command->info('- Conductores creados: ' . User::where('role', 'driver')->count());
        $this->command->info('');
        $this->command->info('Credenciales de prueba:');
        $this->command->info('Admin: admin@ubicaditos.com / password');
        $this->command->info('Conductor: conductor1@ubicaditos.com / password');
        $this->command->info('Pasajero: pasajero@ubicaditos.com / password');
    }
}
