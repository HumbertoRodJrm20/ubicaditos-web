<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Bus, Route, Stop, Trip, Rating, EmergencyReport};
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing routes and stops
        $routes = Route::all();
        $stops = Stop::all();

        if ($routes->isEmpty() || $stops->isEmpty()) {
            $this->command->error('Por favor ejecuta primero los seeders de rutas y paradas');
            return;
        }

        // Create 100 drivers
        $this->command->info('Creando 100 conductores...');
        $drivers = [];
        for ($i = 1; $i <= 100; $i++) {
            $drivers[] = User::create([
                'name' => 'Conductor ' . $i,
                'email' => 'driver' . $i . '@ubicaditos.test',
                'password' => Hash::make('password'),
                'phone' => '951' . rand(1000000, 9999999),
                'role' => 'driver',
            ]);
        }

        // Create 150 passengers
        $this->command->info('Creando 150 pasajeros...');
        $passengers = [];
        for ($i = 1; $i <= 150; $i++) {
            $passengers[] = User::create([
                'name' => 'Pasajero ' . $i,
                'email' => 'passenger' . $i . '@ubicaditos.test',
                'password' => Hash::make('password'),
                'phone' => '951' . rand(1000000, 9999999),
                'role' => 'passenger',
            ]);
        }

        // Create 50 additional buses
        $this->command->info('Creando 50 autobuses...');
        $buses = [];
        $models = ['Mercedes-Benz O500', 'Volvo 9700', 'Scania K360', 'Irizar i6', 'MAN Lion\'s Coach'];

        for ($i = 1; $i <= 50; $i++) {
            $bus = Bus::create([
                'license_plate' => 'OAX-' . rand(100, 999) . '-' . chr(rand(65, 90)),
                'model' => $models[array_rand($models)],
                'capacity' => rand(40, 60),
                'current_passengers' => 0,
                'status' => rand(0, 100) > 20 ? 'active' : 'inactive',
                'is_active' => rand(0, 100) > 20,
            ]);

            // Assign to a random route
            $randomRoute = $routes->random();
            $bus->routes()->attach($randomRoute->id);

            // Assign to a random driver (70% chance)
            if (rand(0, 100) > 30 && !empty($drivers)) {
                $randomDriver = $drivers[array_rand($drivers)];
                $bus->driver_id = $randomDriver->id;
                $bus->save();
            }

            $buses[] = $bus;
        }

        // Get all buses (including existing ones)
        $allBuses = Bus::all();

        // Create historical trips (last 90 days)
        $this->command->info('Creando viajes históricos...');
        $tripCount = 0;

        for ($day = 90; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);

            // Skip if it's a future date
            if ($date->isFuture()) continue;

            // More trips on weekdays
            $tripsPerDay = $date->isWeekday() ? rand(15, 30) : rand(8, 15);

            for ($i = 0; $i < $tripsPerDay; $i++) {
                $driver = $drivers[array_rand($drivers)];
                $bus = $allBuses->random();
                $route = $routes->random();

                // Random time during the day (6 AM to 10 PM)
                $startTime = $date->copy()->setHour(rand(6, 22))->setMinute(rand(0, 59));
                $duration = rand(20, 120); // 20 to 120 minutes
                $endTime = $startTime->copy()->addMinutes($duration);

                // Only create completed trips for past dates
                if ($endTime->isPast()) {
                    $trip = Trip::create([
                        'driver_id' => $driver->id,
                        'bus_id' => $bus->id,
                        'route_id' => $route->id,
                        'started_at' => $startTime,
                        'completed_at' => $endTime,
                        'status' => 'completed',
                    ]);
                    $tripCount++;
                }
            }
        }
        $this->command->info("Creados {$tripCount} viajes completados");

        // Create some ongoing trips
        $this->command->info('Creando viajes en progreso...');
        for ($i = 0; $i < 10; $i++) {
            $driver = $drivers[array_rand($drivers)];
            $bus = $allBuses->random();
            $route = $routes->random();

            Trip::create([
                'driver_id' => $driver->id,
                'bus_id' => $bus->id,
                'route_id' => $route->id,
                'started_at' => Carbon::now()->subMinutes(rand(5, 60)),
                'status' => 'in_progress',
            ]);
        }

        // Create ratings for completed trips
        $this->command->info('Creando calificaciones...');
        $completedTrips = Trip::where('status', 'completed')->get();
        $ratingCount = 0;

        foreach ($completedTrips as $trip) {
            // 60% chance of being rated
            if (rand(0, 100) < 60) {
                $passenger = $passengers[array_rand($passengers)];

                Rating::create([
                    'trip_id' => $trip->id,
                    'passenger_id' => $passenger->id,
                    'driver_id' => $trip->driver_id,
                    'rating' => rand(3, 5), // Mostly positive ratings
                    'comment' => $this->getRandomComment(),
                ]);
                $ratingCount++;
            }
        }
        $this->command->info("Creadas {$ratingCount} calificaciones");

        // Create favorite stops for passengers
        $this->command->info('Creando paradas favoritas...');
        $favoriteCount = 0;
        foreach ($passengers as $passenger) {
            // Each passenger has 1-4 favorite stops
            $favoriteStopsCount = rand(1, 4);
            $randomStops = $stops->random($favoriteStopsCount);

            foreach ($randomStops as $stop) {
                $passenger->favoriteStops()->attach($stop->id, [
                    'nickname' => $this->getRandomNickname(),
                ]);
                $favoriteCount++;
            }
        }
        $this->command->info("Creadas {$favoriteCount} paradas favoritas");

        // Create emergency reports
        $this->command->info('Creando reportes de emergencia...');
        $emergencyTypes = ['accident', 'breakdown', 'medical', 'security', 'other'];
        $emergencyCount = 0;

        for ($i = 0; $i < 30; $i++) {
            $trip = $completedTrips->random();
            $resolvedAt = rand(0, 100) > 20 ? Carbon::now()->subDays(rand(0, 60)) : null;

            EmergencyReport::create([
                'reporter_id' => $trip->driver_id,
                'bus_id' => $trip->bus_id,
                'type' => $emergencyTypes[array_rand($emergencyTypes)],
                'description' => $this->getRandomEmergencyDescription(),
                'latitude' => 17.0 + (rand(-1000, 1000) / 10000),
                'longitude' => -96.7 + (rand(-1000, 1000) / 10000),
                'status' => $resolvedAt ? 'resolved' : 'pending',
                'resolved_at' => $resolvedAt,
            ]);
            $emergencyCount++;
        }
        $this->command->info("Creados {$emergencyCount} reportes de emergencia");

        $this->command->info('✅ Seeder completado exitosamente!');
        $this->command->info("Total de registros creados:");
        $this->command->info("  - 100 conductores");
        $this->command->info("  - 150 pasajeros");
        $this->command->info("  - 50 autobuses");
        $this->command->info("  - {$tripCount} viajes completados + 10 en progreso");
        $this->command->info("  - {$ratingCount} calificaciones");
        $this->command->info("  - {$favoriteCount} paradas favoritas");
        $this->command->info("  - {$emergencyCount} reportes de emergencia");
    }

    private function getRandomComment(): string
    {
        $comments = [
            'Excelente conductor, muy amable',
            'Buen servicio, puntual',
            'Muy profesional',
            'Conducción segura y cómoda',
            'Llegó a tiempo',
            'Muy atento con los pasajeros',
            'Buena actitud',
            'Servicio satisfactorio',
            'Recomendable',
            'Todo bien',
        ];

        return $comments[array_rand($comments)];
    }

    private function getRandomNickname(): string
    {
        $nicknames = [
            'Casa',
            'Trabajo',
            'Escuela',
            'Mercado',
            'Centro',
            'Gimnasio',
            'Parque',
            'Hospital',
            'Universidad',
            'Plaza',
        ];

        return $nicknames[array_rand($nicknames)];
    }

    private function getRandomEmergencyDescription(): string
    {
        $descriptions = [
            'Problema mecánico en el motor',
            'Accidente menor con otro vehículo',
            'Pasajero requiere atención médica',
            'Situación de seguridad en la ruta',
            'Falla en el sistema de frenos',
            'Neumático ponchado',
            'Problema eléctrico',
            'Incidente con peatón',
            'Obstrucción en la ruta',
            'Emergencia médica del conductor',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
