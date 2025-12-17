<?php

namespace Database\Seeders;

use App\Models\{User, Bus, Route, Stop, EmergencyReport, Trip, Rating};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Administrador UbicaditOS',
            'email' => 'admin@ubicaditos.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '9511234567',
            'is_active' => true,
        ]);

        // Create 100 Drivers
        $drivers = [];
        $firstNames = ['Juan', 'Carlos', 'Miguel', 'Roberto', 'Fernando', 'Luis', 'José', 'Antonio', 'Manuel', 'Ricardo', 'Francisco', 'Alberto', 'Diego', 'Javier', 'Sergio', 'Pedro', 'Raúl', 'Arturo', 'Eduardo', 'Jorge'];
        $lastNames = ['Pérez', 'López', 'Hernández', 'García', 'Martínez', 'González', 'Ramírez', 'Flores', 'Torres', 'Morales', 'Cruz', 'Ruiz', 'Jiménez', 'Mendoza', 'Navarro', 'Sánchez', 'Díaz', 'Vargas', 'Castillo', 'Romero'];

        for ($i = 1; $i <= 100; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $drivers[] = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => 'chofer' . $i . '@ubicaditos.com',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'phone' => '951' . rand(1000000, 9999999),
                'is_active' => true,
            ]);
        }

        // Create 600 Passengers
        $passengers = [];
        $passengerFirstNames = ['María', 'Ana', 'José', 'Laura', 'Pedro', 'Carmen', 'Sofía', 'Elena', 'Andrea', 'Daniela', 'Gabriela', 'Isabella', 'Valentina', 'Camila', 'Lucía', 'Mariana', 'Alejandra', 'Natalia', 'Paola', 'Diana'];
        $passengerLastNames = ['García', 'Martínez', 'Rodríguez', 'Sánchez', 'Torres', 'Ramírez', 'López', 'González', 'Hernández', 'Díaz', 'Morales', 'Jiménez', 'Cruz', 'Ortiz', 'Gutiérrez', 'Reyes', 'Flores', 'Mendoza', 'Vega', 'Silva'];

        for ($i = 1; $i <= 600; $i++) {
            $firstName = $passengerFirstNames[array_rand($passengerFirstNames)];
            $lastName = $passengerLastNames[array_rand($passengerLastNames)];
            $passengers[] = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => 'pasajero' . $i . '@ubicaditos.com',
                'password' => Hash::make('password'),
                'role' => 'passenger',
                'phone' => '951' . rand(1000000, 9999999),
                'is_active' => true,
                'current_latitude' => 17.0732 + (rand(-100, 100) / 10000),
                'current_longitude' => -96.7266 + (rand(-100, 100) / 10000),
                'last_location_update' => now(),
            ]);
        }

        // Create MORE Stops (Paradas ampliadas en Oaxaca)
        $stops = [
            ['name' => 'Zócalo', 'address' => 'Plaza de la Constitución', 'latitude' => 17.0654, 'longitude' => -96.7256],
            ['name' => 'Santo Domingo', 'address' => 'Iglesia de Santo Domingo', 'latitude' => 17.0676, 'longitude' => -96.7208],
            ['name' => 'Mercado Benito Juárez', 'address' => '20 de Noviembre y Las Casas', 'latitude' => 17.0636, 'longitude' => -96.7255],
            ['name' => 'ADO', 'address' => 'Terminal ADO Oaxaca', 'latitude' => 17.0696, 'longitude' => -96.7184],
            ['name' => 'Central de Abastos', 'address' => 'Periférico', 'latitude' => 17.0487, 'longitude' => -96.7437],
            ['name' => 'Tule', 'address' => 'Santa María del Tule', 'latitude' => 17.0467, 'longitude' => -96.6364],
            ['name' => 'Cinco Señores', 'address' => 'Av. Universidad', 'latitude' => 17.0729, 'longitude' => -96.7398],
            ['name' => 'Tecnológico', 'address' => 'Instituto Tecnológico', 'latitude' => 17.0542, 'longitude' => -96.7023],
            ['name' => 'San Felipe', 'address' => 'San Felipe del Agua', 'latitude' => 17.0971, 'longitude' => -96.7150],
            ['name' => 'Xoxocotlán', 'address' => 'Santa Cruz Xoxocotlán', 'latitude' => 17.0332, 'longitude' => -96.7301],
            ['name' => 'Plaza del Valle', 'address' => 'Centro Comercial', 'latitude' => 17.0520, 'longitude' => -96.7180],
            ['name' => 'UABJO', 'address' => 'Universidad Autónoma', 'latitude' => 17.0599, 'longitude' => -96.7255],
            ['name' => 'Símbolos Patrios', 'address' => 'Monumento Símbolos Patrios', 'latitude' => 17.0851, 'longitude' => -96.7210],
            ['name' => 'Estadio', 'address' => 'Estadio Tecnológico', 'latitude' => 17.0485, 'longitude' => -96.7098],
            ['name' => 'Ixcotel', 'address' => 'Santa Lucía del Camino', 'latitude' => 17.0789, 'longitude' => -96.6980],
            // Nuevas paradas
            ['name' => 'Reforma', 'address' => 'Colonia Reforma', 'latitude' => 17.0612, 'longitude' => -96.7123],
            ['name' => 'Tlalixtac', 'address' => 'Tlalixtac de Cabrera', 'latitude' => 17.0612, 'longitude' => -96.6834],
            ['name' => 'Aeropuerto', 'address' => 'Aeropuerto Internacional', 'latitude' => 16.9999, 'longitude' => -96.7266],
            ['name' => 'Tlacolula', 'address' => 'Tlacolula de Matamoros', 'latitude' => 16.9574, 'longitude' => -96.4765],
            ['name' => 'Etla', 'address' => 'Villa de Etla', 'latitude' => 17.2012, 'longitude' => -96.7834],
            ['name' => 'Atzompa', 'address' => 'Santa María Atzompa', 'latitude' => 17.0678, 'longitude' => -96.7712],
            ['name' => 'San Agustín', 'address' => 'San Agustín de las Juntas', 'latitude' => 17.0234, 'longitude' => -96.6123],
            ['name' => 'Zaachila', 'address' => 'Villa de Zaachila', 'latitude' => 16.9534, 'longitude' => -96.7489],
            ['name' => 'Cuilápam', 'address' => 'Cuilápam de Guerrero', 'latitude' => 16.9823, 'longitude' => -96.7612],
            ['name' => 'Jalatlaco', 'address' => 'Barrio de Jalatlaco', 'latitude' => 17.0689, 'longitude' => -96.7189],
        ];

        $stopModels = [];
        foreach ($stops as $stop) {
            $stopModels[] = Stop::create(array_merge($stop, ['is_active' => true]));
        }

        // Create Routes (18 rutas diferentes)
        $routes = [
            [
                'name' => 'Ruta 1 - Centro - Tule',
                'code' => 'OAX-101',
                'color' => '#3B82F6', // Azul
                'estimated_duration' => 45,
                'stops' => [0, 1, 3, 7, 5], // Zócalo -> Santo Domingo -> ADO -> Tecnológico -> Tule
            ],
            [
                'name' => 'Ruta 2 - Centro - Xoxocotlán',
                'code' => 'OAX-102',
                'color' => '#EF4444', // Rojo
                'estimated_duration' => 30,
                'stops' => [0, 2, 4, 9], // Zócalo -> Mercado -> Central -> Xoxocotlán
            ],
            [
                'name' => 'Ruta 3 - San Felipe Express',
                'code' => 'OAX-103',
                'color' => '#10B981', // Verde
                'estimated_duration' => 35,
                'stops' => [0, 1, 6, 12, 8], // Zócalo -> Santo Domingo -> Cinco Señores -> Símbolos -> San Felipe
            ],
            [
                'name' => 'Ruta 4 - Universidad',
                'code' => 'OAX-104',
                'color' => '#F59E0B', // Amarillo
                'estimated_duration' => 25,
                'stops' => [0, 11, 10, 13], // Zócalo -> UABJO -> Plaza Valle -> Estadio
            ],
            [
                'name' => 'Ruta 5 - Ixcotel',
                'code' => 'OAX-105',
                'color' => '#8B5CF6', // Morado
                'estimated_duration' => 40,
                'stops' => [0, 3, 7, 14], // Zócalo -> ADO -> Tecnológico -> Ixcotel
            ],
            [
                'name' => 'Ruta 6 - Circular Norte',
                'code' => 'OAX-106',
                'color' => '#EC4899', // Rosa
                'estimated_duration' => 50,
                'stops' => [0, 1, 8, 12, 6], // Zócalo -> Santo Domingo -> San Felipe -> Símbolos -> Cinco Señores
            ],
            [
                'name' => 'Ruta 7 - Sur Periférico',
                'code' => 'OAX-107',
                'color' => '#06B6D4', // Cian
                'estimated_duration' => 35,
                'stops' => [0, 2, 4, 9, 13], // Zócalo -> Mercado -> Central -> Xoxo -> Estadio
            ],
            [
                'name' => 'Ruta 8 - Express Tule',
                'code' => 'OAX-108',
                'color' => '#84CC16', // Verde Lima
                'estimated_duration' => 30,
                'stops' => [0, 7, 14, 5], // Zócalo -> Tecnológico -> Ixcotel -> Tule
            ],
            // NUEVAS RUTAS
            [
                'name' => 'Ruta 9 - Reforma - Tlalixtac',
                'code' => 'OAX-109',
                'color' => '#F472B6', // Rosa claro
                'estimated_duration' => 40,
                'stops' => [0, 15, 7, 16, 5], // Zócalo -> Reforma -> Tecnológico -> Tlalixtac -> Tule
            ],
            [
                'name' => 'Ruta 10 - Centro - Aeropuerto',
                'code' => 'OAX-110',
                'color' => '#22D3EE', // Cyan claro
                'estimated_duration' => 35,
                'stops' => [0, 9, 17], // Zócalo -> Xoxocotlán -> Aeropuerto
            ],
            [
                'name' => 'Ruta 11 - Etla Express',
                'code' => 'OAX-111',
                'color' => '#A78BFA', // Púrpura claro
                'estimated_duration' => 60,
                'stops' => [0, 6, 20, 19], // Zócalo -> Cinco Señores -> Atzompa -> Etla
            ],
            [
                'name' => 'Ruta 12 - Tlacolula Directo',
                'code' => 'OAX-112',
                'color' => '#FB923C', // Naranja
                'estimated_duration' => 55,
                'stops' => [0, 5, 16, 18], // Zócalo -> Tule -> Tlalixtac -> Tlacolula
            ],
            [
                'name' => 'Ruta 13 - Zaachila',
                'code' => 'OAX-113',
                'color' => '#34D399', // Verde esmeralda
                'estimated_duration' => 45,
                'stops' => [0, 2, 9, 23, 22], // Zócalo -> Mercado -> Xoxo -> Cuilápam -> Zaachila
            ],
            [
                'name' => 'Ruta 14 - Jalatlaco - Atzompa',
                'code' => 'OAX-114',
                'color' => '#FCD34D', // Amarillo claro
                'estimated_duration' => 40,
                'stops' => [0, 24, 6, 20], // Zócalo -> Jalatlaco -> Cinco Señores -> Atzompa
            ],
            [
                'name' => 'Ruta 15 - San Agustín',
                'code' => 'OAX-115',
                'color' => '#C084FC', // Lavanda
                'estimated_duration' => 50,
                'stops' => [0, 7, 16, 21], // Zócalo -> Tecnológico -> Tlalixtac -> San Agustín
            ],
            [
                'name' => 'Ruta 16 - Periférico Completo',
                'code' => 'OAX-116',
                'color' => '#FB7185', // Rosa fuerte
                'estimated_duration' => 60,
                'stops' => [0, 4, 9, 17, 13, 10], // Zócalo -> Central -> Xoxo -> Aeropuerto -> Estadio -> Plaza Valle
            ],
            [
                'name' => 'Ruta 17 - Norte - Sur',
                'code' => 'OAX-117',
                'color' => '#4ADE80', // Verde brillante
                'estimated_duration' => 55,
                'stops' => [8, 12, 0, 9, 22], // San Felipe -> Símbolos -> Zócalo -> Xoxo -> Zaachila
            ],
            [
                'name' => 'Ruta 18 - Express Diagonal',
                'code' => 'OAX-118',
                'color' => '#60A5FA', // Azul brillante
                'estimated_duration' => 45,
                'stops' => [20, 6, 0, 10, 13], // Atzompa -> Cinco Señores -> Zócalo -> Plaza Valle -> Estadio
            ],
        ];

        $routeModels = [];
        foreach ($routes as $routeData) {
            $stopIndices = $routeData['stops'];
            unset($routeData['stops']);

            // Generate path coordinates with intermediate points for smoother routes
            $pathCoordinates = [];
            for ($i = 0; $i < count($stopIndices); $i++) {
                $currentStop = $stopModels[$stopIndices[$i]];
                $pathCoordinates[] = [
                    'lat' => (float) $currentStop->latitude,
                    'lng' => (float) $currentStop->longitude,
                ];

                // Add intermediate points between stops (except for last stop)
                if ($i < count($stopIndices) - 1) {
                    $nextStop = $stopModels[$stopIndices[$i + 1]];
                    $lat1 = (float) $currentStop->latitude;
                    $lng1 = (float) $currentStop->longitude;
                    $lat2 = (float) $nextStop->latitude;
                    $lng2 = (float) $nextStop->longitude;

                    // Add 3-5 intermediate points between each pair of stops
                    $numPoints = rand(3, 5);
                    for ($j = 1; $j <= $numPoints; $j++) {
                        $ratio = $j / ($numPoints + 1);
                        // Add some random offset to simulate following streets
                        $latOffset = (rand(-20, 20) / 10000);
                        $lngOffset = (rand(-20, 20) / 10000);
                        $pathCoordinates[] = [
                            'lat' => $lat1 + ($lat2 - $lat1) * $ratio + $latOffset,
                            'lng' => $lng1 + ($lng2 - $lng1) * $ratio + $lngOffset,
                        ];
                    }
                }
            }

            $route = Route::create(array_merge($routeData, [
                'path_coordinates' => $pathCoordinates,
                'description' => 'Ruta de transporte público en Oaxaca',
                'is_active' => true,
            ]));

            // Attach stops to route with order
            foreach ($stopIndices as $order => $stopIndex) {
                $route->stops()->attach($stopModels[$stopIndex]->id, [
                    'order' => $order,
                    'estimated_time_from_previous' => $order === 0 ? 0 : rand(5, 15),
                ]);
            }

            $routeModels[] = $route;
        }

        // Create 54 Buses (3 por cada ruta)
        $busModels = ['Mercedes-Benz Sprinter', 'Ford Transit', 'Volkswagen Crafter', 'Iveco Daily', 'Peugeot Boxer', 'Mercedes-Benz O500', 'Volvo 9700', 'Scania K360', 'Hyundai Universe', 'Man Lion\'s City'];
        $busNumber = 1;

        foreach ($routeModels as $route) {
            // 3 autobuses por ruta
            for ($j = 0; $j < 3; $j++) {
                $driver = $drivers[($busNumber - 1) % count($drivers)];
                $model = $busModels[array_rand($busModels)];

                // Primera parada de la ruta
                $firstStop = $route->stops()->first();

                $bus = Bus::create([
                    'license_plate' => sprintf('OAX-%03d-%s', $busNumber, chr(65 + ($j % 26))),
                    'model' => $model,
                    'capacity' => rand(38, 55),
                    'driver_id' => $driver->id,
                    'current_latitude' => $firstStop ? $firstStop->latitude : 17.0654,
                    'current_longitude' => $firstStop ? $firstStop->longitude : -96.7256,
                    'last_location_update' => now(),
                    'status' => 'active',
                    'is_active' => true,
                ]);

                // Attach bus to route
                $bus->routes()->attach($route->id, ['assigned_at' => now()]);

                $busNumber++;
            }
        }

        // Create Trip records (viajes históricos)
        $buses = Bus::with(['routes', 'driver'])->get();
        $tripModels = [];

        foreach ($buses as $bus) {
            if (!$bus->routes->first() || !$bus->driver) continue;

            $route = $bus->routes->first();

            // Crear entre 20 y 50 viajes por autobús (distribuidos en los últimos 60 días)
            $tripCount = rand(20, 50);

            for ($i = 0; $i < $tripCount; $i++) {
                // Viajes distribuidos en los últimos 60 días
                $daysAgo = rand(0, 60);
                $hour = rand(6, 22); // Horario de servicio 6am-10pm
                $minute = rand(0, 59);

                $startedAt = Carbon::now()->subDays($daysAgo)->setHour($hour)->setMinute($minute);
                $duration = rand(25, 90); // Duración en minutos
                $completedAt = $startedAt->copy()->addMinutes($duration);

                $trip = Trip::create([
                    'bus_id' => $bus->id,
                    'route_id' => $route->id,
                    'driver_id' => $bus->driver_id,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'total_passengers' => rand(5, $bus->capacity),
                    'distance_km' => rand(10, 50),
                    'status' => 'completed',
                ]);

                $tripModels[] = $trip;
            }
        }

        // Create Rating records (calificaciones de pasajeros a conductores)
        $ratingComments = [
            'Excelente servicio, muy puntual',
            'Conductor amable y respetuoso',
            'Viaje cómodo y seguro',
            'Buen conductor, maneja con cuidado',
            'Muy profesional',
            'Llegó a tiempo',
            'Autobús limpio',
            'Ruta eficiente',
            'Conductor cortés',
            'Todo bien',
            'Podría mejorar la puntualidad',
            'Buen servicio en general',
            'Recomendado',
            'Muy buena experiencia',
            'Conductor experimentado',
        ];

        foreach ($tripModels as $trip) {
            // 70% de probabilidad de que un viaje tenga calificación
            if (rand(1, 100) <= 70) {
                // Seleccionar un pasajero aleatorio
                $passenger = $passengers[array_rand($passengers)];

                // Generar calificación (más probabilidad de buenas calificaciones)
                $ratingValue = rand(1, 100);
                if ($ratingValue <= 40) {
                    $rating = 5; // 40% probabilidad
                } elseif ($ratingValue <= 70) {
                    $rating = 4; // 30% probabilidad
                } elseif ($ratingValue <= 85) {
                    $rating = 3; // 15% probabilidad
                } elseif ($ratingValue <= 95) {
                    $rating = 2; // 10% probabilidad
                } else {
                    $rating = 1; // 5% probabilidad
                }

                Rating::create([
                    'passenger_id' => $passenger->id,
                    'driver_id' => $trip->driver_id,
                    'trip_id' => $trip->id,
                    'rating' => $rating,
                    'comment' => $rating >= 3 ? $ratingComments[array_rand($ratingComments)] : 'Puede mejorar',
                ]);
            }
        }

        $this->command->info('✅ Base de datos poblada con datos ampliados de Oaxaca');
        $this->command->info('📊 Estadísticas:');
        $this->command->info('   - Rutas creadas: ' . Route::count());
        $this->command->info('   - Paradas creadas: ' . Stop::count());
        $this->command->info('   - Autobuses creados: ' . Bus::count());
        $this->command->info('   - Conductores: ' . count($drivers));
        $this->command->info('   - Pasajeros: ' . count($passengers));
        $this->command->info('   - Viajes registrados: ' . Trip::count());
        $this->command->info('   - Calificaciones: ' . Rating::count());
        $this->command->info('');
        $this->command->info('📧 Credenciales:');
        $this->command->info('   Admin: admin@ubicaditos.com / password');
        $this->command->info('   Choferes: chofer1@ubicaditos.com - chofer100@ubicaditos.com / password');
        $this->command->info('   Pasajeros: pasajero1@ubicaditos.com - pasajero600@ubicaditos.com / password');
    }
}
