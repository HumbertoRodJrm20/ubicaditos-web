<?php

namespace Tests\Feature\Bus;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ocupación del autobús: guardado del conteo de pasajeros (fillables),
 * tope por capacidad y asiento disponible reportado por la API JSON.
 */
class OccupancyTest extends TestCase
{
    use RefreshDatabase;

    private function driverWithBus(array $busAttrs = []): array
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $bus = Bus::create(array_merge([
            'license_plate' => 'OAX123',
            'model' => 'Toyota Coaster',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
        ], $busAttrs));

        return [$driver, $bus];
    }

    public function test_bus_model_persists_current_passengers(): void
    {
        [, $bus] = $this->driverWithBus(['current_passengers' => 12]);

        $this->assertSame(12, $bus->fresh()->current_passengers);
    }

    public function test_driver_can_update_passenger_count(): void
    {
        [$driver, $bus] = $this->driverWithBus();

        $this->actingAs($driver)
            ->post(route('driver.update-passengers'), ['passengers' => 15])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('buses', [
            'id' => $bus->id,
            'current_passengers' => 15,
        ]);
    }

    public function test_passenger_count_is_capped_at_bus_capacity(): void
    {
        [$driver] = $this->driverWithBus(['capacity' => 40]);

        $this->actingAs($driver)
            ->post(route('driver.update-passengers'), ['passengers' => 999]);

        $this->assertDatabaseHas('buses', [
            'driver_id' => $driver->id,
            'current_passengers' => 40,
        ]);
    }

    public function test_passenger_count_rejects_negative_values(): void
    {
        [$driver] = $this->driverWithBus();

        $this->actingAs($driver)
            ->from(route('driver.dashboard'))
            ->post(route('driver.update-passengers'), ['passengers' => -1])
            ->assertSessionHasErrors('passengers');
    }

    public function test_active_buses_api_reports_available_seats(): void
    {
        [$driver, $bus] = $this->driverWithBus(['current_passengers' => 25]);

        $route = Route::create([
            'name' => 'Ruta Oaxaca Centro',
            'code' => 'OAX-C',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $bus->routes()->attach($route->id, ['assigned_at' => now()]);

        $this->actingAs($driver)
            ->getJson(route('api.buses.active'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $bus->id)
            ->assertJsonPath('0.current_passengers', 25)
            ->assertJsonPath('0.available_seats', 15)
            ->assertJsonPath('0.route', 'Ruta Oaxaca Centro');
    }
}
