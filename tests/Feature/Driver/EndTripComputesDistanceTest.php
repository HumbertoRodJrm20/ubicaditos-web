<?php

namespace Tests\Feature\Driver;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * RF-12: distancia y trayectoria reales del viaje al finalizarlo.
 */
class EndTripComputesDistanceTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    private Bus $bus;

    private Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $this->bus = Bus::create([
            'license_plate' => 'DST-001',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $this->driver->id,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
            'current_passengers' => 12,
        ]);

        $route = Route::create([
            'name' => 'Ruta DST',
            'code' => 'DST-R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $this->trip = Trip::create([
            'driver_id' => $this->driver->id,
            'bus_id' => $this->bus->id,
            'route_id' => $route->id,
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);
    }

    public function test_trips_already_include_nullable_distance_and_path_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('trips', 'distance_km'));
        $this->assertTrue(Schema::hasColumn('trips', 'path_taken'));
        $this->assertContains('distance_km', (new Trip)->getFillable());
        $this->assertContains('path_taken', (new Trip)->getFillable());

        $this->assertDatabaseHas('trips', [
            'id' => $this->trip->id,
            'distance_km' => null,
            'path_taken' => null,
        ]);
    }

    public function test_end_trip_computes_haversine_distance_from_recorded_positions(): void
    {
        $this->postLocation(17.0613, -96.7252);
        $this->postLocation(17.0623, -96.7262);
        $this->postLocation(17.0643, -96.7292);

        $this->actingAs($this->driver)
            ->post(route('driver.end-trip'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->trip->refresh();

        $this->assertSame('completed', $this->trip->status);
        $this->assertNotNull($this->trip->completed_at);
        $this->assertSame(12, $this->trip->total_passengers);
        $this->assertSame('0.54', $this->trip->distance_km);
        $this->assertCount(3, $this->trip->path_taken);
        $this->assertSame(17.0613, round((float) $this->trip->path_taken[0]['latitude'], 4));
    }

    public function test_end_trip_without_recorded_positions_records_zero_distance(): void
    {
        $this->actingAs($this->driver)
            ->post(route('driver.end-trip'))
            ->assertSessionHas('success');

        $this->trip->refresh();

        $this->assertSame('0.00', $this->trip->distance_km);
        $this->assertNull($this->trip->path_taken);
    }

    public function test_position_updates_do_not_mix_positions_from_other_trips(): void
    {
        $otherDriver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $otherBus = Bus::create([
            'license_plate' => 'DST-002',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $otherDriver->id,
        ]);
        $otherTrip = Trip::create([
            'driver_id' => $otherDriver->id,
            'bus_id' => $otherBus->id,
            'route_id' => $this->trip->route_id,
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $this->postLocation(17.0613, -96.7252);
        $this->postLocation(17.0623, -96.7262);

        $this->actingAs($this->driver)->post(route('driver.end-trip'));

        $this->trip->refresh();
        $otherTrip->refresh();

        $this->assertCount(2, $this->trip->path_taken);
        $this->assertSame('0.15', $this->trip->distance_km);
        $this->assertNull($otherTrip->path_taken);
        $this->assertNull($otherTrip->distance_km);
        $this->assertSame('in_progress', $otherTrip->status);
    }

    private function postLocation(float $latitude, float $longitude): void
    {
        $this->actingAs($this->driver)
            ->postJson(route('driver.update-location'), [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ])
            ->assertOk();
    }
}
