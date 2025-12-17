<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Bus;
use App\Models\User;

class OaxacaRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nuevas rutas de Oaxaca
        $routes = [
            [
                'name' => 'Ruta Centro - Monte Albán',
                'code' => 'OAX-106',
                'description' => 'Ruta turística hacia Monte Albán',
                'color' => '#F59E0B', // Amarillo/Naranja
                'stops' => [
                    ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                    ['name' => 'Mercado 20 de Noviembre', 'address' => 'Mercado 20 de Noviembre', 'lat' => 17.0638, 'lng' => -96.7254],
                    ['name' => 'Periférico Poniente', 'address' => 'Carretera a Monte Albán', 'lat' => 17.0523, 'lng' => -96.7589],
                    ['name' => 'Atzompa', 'address' => 'Santa María Atzompa', 'lat' => 17.0678, 'lng' => -96.7712],
                    ['name' => 'Monte Albán', 'address' => 'Zona Arqueológica', 'lat' => 17.0432, 'lng' => -96.7678],
                ]
            ],
            [
                'name' => 'Ruta Etla - Centro',
                'code' => 'OAX-107',
                'description' => 'Conexión San Pablo Etla con el Centro',
                'color' => '#8B5CF6', // Morado
                'stops' => [
                    ['name' => 'San Pablo Etla', 'address' => 'Centro de Etla', 'lat' => 17.1989, 'lng' => -96.7845],
                    ['name' => 'Reyes Etla', 'address' => 'Villa de Etla', 'lat' => 17.1567, 'lng' => -96.7734],
                    ['name' => 'Guadalupe Etla', 'address' => 'Guadalupe Etla', 'lat' => 17.1234, 'lng' => -96.7623],
                    ['name' => 'Viguera', 'address' => 'Colonia Viguera', 'lat' => 17.0856, 'lng' => -96.7445],
                    ['name' => 'Cinco Señores', 'address' => 'Av. Universidad', 'lat' => 17.0729, 'lng' => -96.7398],
                    ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'lat' => 17.0654, 'lng' => -96.7256],
                ]
            ],
            [
                'name' => 'Ruta Periférico Circuito',
                'code' => 'OAX-108',
                'description' => 'Circuito por el Periférico de Oaxaca',
                'color' => '#EC4899', // Rosa/Magenta
                'stops' => [
                    ['name' => 'Central de Abastos', 'address' => 'Periférico', 'lat' => 17.0487, 'lng' => -96.7437],
                    ['name' => 'Símbolos Patrios', 'address' => 'Periférico', 'lat' => 17.0423, 'lng' => -96.7289],
                    ['name' => 'Estadio Tecnológico', 'address' => 'Instituto Tecnológico', 'lat' => 17.0542, 'lng' => -96.7023],
                    ['name' => 'Tlalixtac', 'address' => 'Tlalixtac de Cabrera', 'lat' => 17.0612, 'lng' => -96.6834],
                    ['name' => 'Santa Lucía', 'address' => 'Santa Lucía del Camino', 'lat' => 17.0734, 'lng' => -96.6945],
                    ['name' => 'Candiani', 'address' => 'Carretera Cristóbal Colón', 'lat' => 17.0698, 'lng' => -96.7112],
                    ['name' => 'Brenamiel', 'address' => 'Colonia Brenamiel', 'lat' => 17.0567, 'lng' => -96.7334],
                ]
            ],
        ];

        $drivers = User::where('role', 'driver')->get();
        $busModels = ['Mercedes-Benz', 'Volvo', 'Scania', 'MAN', 'Isuzu'];
        $busNumber = Bus::max('id') ?? 0;

        foreach ($routes as $routeData) {
            // Crear ruta
            $route = Route::firstOrCreate(
                ['code' => $routeData['code']],
                [
                    'name' => $routeData['name'],
                    'description' => $routeData['description'],
                    'color' => $routeData['color'],
                    'estimated_duration' => rand(30, 60),
                    'is_active' => true,
                ]
            );

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
                        'estimated_time_from_previous' => $index == 0 ? 0 : rand(5, 15),
                    ]);
                }
            }

            // Crear 2 autobuses por ruta
            for ($i = 1; $i <= 2; $i++) {
                $busNumber++;
                $driver = $drivers->random();

                $bus = Bus::create([
                    'license_plate' => sprintf('OAX-%03d', $busNumber + 100),
                    'model' => $busModels[array_rand($busModels)],
                    'capacity' => rand(40, 60),
                    'current_passengers' => rand(0, 30),
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

        $this->command->info('✅ 3 nuevas rutas de Oaxaca creadas exitosamente!');
        $this->command->info('- Ruta Centro - Monte Albán (OAX-106)');
        $this->command->info('- Ruta Etla - Centro (OAX-107)');
        $this->command->info('- Ruta Periférico Circuito (OAX-108)');
        $this->command->info('- 6 autobuses adicionales creados');
    }
}
