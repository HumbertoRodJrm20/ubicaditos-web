<?php

namespace App\Console\Commands;

use App\Models\Bus;
use Illuminate\Console\Command;

class SimulateBusMovement extends Command
{
    protected $signature = 'buses:simulate {--interval=5 : Interval in seconds between updates}';
    protected $description = 'Simulate bus movement along their routes';

    private array $busPositions = [];

    public function handle()
    {
        $this->info('🚍 Iniciando simulación de movimiento de buses...');
        $interval = (int) $this->option('interval');

        $buses = Bus::with('routes.stops')
            ->where('status', 'active')
            ->get();

        if ($buses->isEmpty()) {
            $this->error('No hay buses activos para simular.');
            return;
        }

        // Initialize bus positions
        foreach ($buses as $bus) {
            $route = $bus->routes->first();
            if ($route) {
                $stops = $route->getOrderedStops();
                if ($stops->isNotEmpty()) {
                    $this->busPositions[$bus->id] = [
                        'current_stop_index' => 0,
                        'stops' => $stops,
                        'progress' => 0, // 0-1 between current and next stop
                    ];
                }
            }
        }

        $this->info("Simulando {$buses->count()} buses. Presiona Ctrl+C para detener.\n");

        while (true) {
            foreach ($buses as $bus) {
                if (!isset($this->busPositions[$bus->id])) continue;

                $position = &$this->busPositions[$bus->id];
                $stops = $position['stops'];
                $currentIndex = $position['current_stop_index'];

                if ($stops->count() <= 1) continue;

                // Get current and next stop
                $currentStop = $stops[$currentIndex];
                $nextIndex = ($currentIndex + 1) % $stops->count();
                $nextStop = $stops[$nextIndex];

                // Calculate interpolated position
                $position['progress'] += 0.1; // Move 10% towards next stop

                if ($position['progress'] >= 1) {
                    // Reached next stop
                    $position['current_stop_index'] = $nextIndex;
                    $position['progress'] = 0;

                    $lat = (float) $nextStop->latitude;
                    $lng = (float) $nextStop->longitude;

                    $this->line("🚏 Bus {$bus->license_plate} llegó a: {$nextStop->name}");
                } else {
                    // Interpolate between current and next stop
                    $lat = $this->interpolate(
                        (float) $currentStop->latitude,
                        (float) $nextStop->latitude,
                        $position['progress']
                    );
                    $lng = $this->interpolate(
                        (float) $currentStop->longitude,
                        (float) $nextStop->longitude,
                        $position['progress']
                    );
                }

                // Update bus location in database
                $bus->updateLocation($lat, $lng);

                // Simulate passenger changes occasionally
                if (rand(1, 10) > 7) {
                    $change = rand(-3, 5);
                    $newPassengers = max(0, min($bus->capacity, $bus->current_passengers + $change));
                    $bus->update(['current_passengers' => $newPassengers]);
                }
            }

            sleep($interval);
        }
    }

    private function interpolate($start, $end, $progress)
    {
        return $start + ($end - $start) * $progress;
    }
}
