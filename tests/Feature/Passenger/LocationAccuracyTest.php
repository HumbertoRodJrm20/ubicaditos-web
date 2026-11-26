<?php

namespace Tests\Feature\Passenger;

use App\Events\BusLocationUpdated;
use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-08: precisión GPS opcional en metros, validada y expuesta sin romper a
 * los clientes que no envían el campo.
 */
class LocationAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private function driverWithBus(): array
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'is_active' => true,
            'work_shift' => 'morning',
        ]);
        $bus = Bus::create([
            'license_plate' => 'ACC-001',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);

        return [$driver, $bus];
    }

    public function test_driver_location_update_stores_valid_accuracy(): void
    {
        [$driver, $bus] = $this->driverWithBus();

        $this->actingAs($driver)
            ->postJson(route('driver.update-location'), [
                'latitude' => 17.0763,
                'longitude' => -96.7446,
                'accuracy' => 12,
            ])
            ->assertOk();

        $this->assertSame(12, $bus->fresh()->accuracy);
    }

    public function test_accuracy_is_optional_for_legacy_clients(): void
    {
        [$driver, $bus] = $this->driverWithBus();

        $this->actingAs($driver)
            ->postJson(route('driver.update-location'), [
                'latitude' => 17.0763,
                'longitude' => -96.7446,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($bus->fresh()->accuracy);
    }

    public function test_invalid_accuracy_is_rejected(): void
    {
        [$driver] = $this->driverWithBus();

        foreach ([-1, 'doce', 70000] as $accuracy) {
            $this->actingAs($driver)
                ->postJson(route('driver.update-location'), [
                    'latitude' => 17.0763,
                    'longitude' => -96.7446,
                    'accuracy' => $accuracy,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('accuracy');
        }
    }

    public function test_driver_cannot_update_a_foreign_bus_by_sending_bus_id(): void
    {
        [$driver] = $this->driverWithBus();
        $other = Bus::create([
            'license_plate' => 'ACC-999',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => null,
        ]);

        $this->actingAs($driver)
            ->postJson(route('driver.update-location'), [
                'bus_id' => $other->id,
                'latitude' => 17.0763,
                'longitude' => -96.7446,
                'accuracy' => 10,
            ])
            ->assertOk();

        $this->assertNull($other->fresh()->last_location_update);
    }

    public function test_position_payloads_and_event_expose_accuracy(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $route = Route::create([
            'name' => 'Ruta accuracy',
            'code' => 'ACC-R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $bus = Bus::create([
            'license_plate' => 'ACC-002',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
            'last_location_update' => now(),
            'accuracy' => 25,
        ]);
        $bus->routes()->attach($route->id, ['assigned_at' => now()]);

        $this->actingAs($passenger)
            ->getJson(route('api.buses.active'))
            ->assertOk()
            ->assertJsonPath('0.accuracy', 25);

        $this->actingAs($passenger)
            ->getJson(route('api.buses.location', $bus))
            ->assertOk()
            ->assertJsonPath('accuracy', 25);

        $this->actingAs($passenger)
            ->getJson(route('api.routes.buses', $route))
            ->assertOk()
            ->assertJsonPath('0.accuracy', 25);

        $this->assertSame(
            25,
            (new BusLocationUpdated($bus->fresh()))->broadcastWith()['accuracy']
        );
    }
}
