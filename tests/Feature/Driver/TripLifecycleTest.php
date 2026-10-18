<?php

namespace Tests\Feature\Driver;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ciclo de vida del viaje del conductor: inicio (turno, geocercas),
 * finalización con ocupación real y estados inválidos.
 */
class TripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private Bus $bus;
    private Route $route;
    private Stop $stop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $this->bus = Bus::create([
            'license_plate' => 'OAX456',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $this->driver->id,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
        ]);

        $this->route = Route::create([
            'name' => 'Ruta 1 - Centro',
            'code' => 'R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $this->bus->routes()->attach($this->route->id, ['assigned_at' => now()]);

        $this->stop = Stop::create([
            'name' => 'Central de Abastos',
            'latitude' => 17.0613,
            'longitude' => -96.7252,
            'is_active' => true,
        ]);
        $this->route->stops()->attach($this->stop->id, ['order' => 1]);
    }

    public function test_driver_can_start_a_trip_near_a_stop_of_their_route(): void
    {
        $this->travelTo('2026-10-17 09:00:00');

        $this->actingAs($this->driver)
            ->post(route('driver.start-trip'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trips', [
            'driver_id' => $this->driver->id,
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_driver_cannot_start_a_trip_outside_their_shift(): void
    {
        $this->travelTo('2026-10-17 03:00:00');

        $this->actingAs($this->driver)
            ->post(route('driver.start-trip'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_driver_cannot_start_a_trip_more_than_500m_from_any_stop(): void
    {
        $this->travelTo('2026-10-17 09:00:00');

        $this->bus->update([
            'current_latitude' => 18.0613,
            'current_longitude' => -97.7252,
        ]);

        $this->actingAs($this->driver)
            ->post(route('driver.start-trip'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_a_second_trip_cannot_start_while_one_is_in_progress(): void
    {
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $this->driver->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->driver)
            ->post(route('driver.start-trip'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 1);
    }

    public function test_inactive_driver_cannot_start_a_trip(): void
    {
        $this->driver->update(['is_active' => false]);

        $this->actingAs($this->driver)
            ->post(route('driver.start-trip'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_ending_a_trip_records_the_real_occupancy(): void
    {
        $this->bus->update(['current_passengers' => 22]);

        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $this->driver->id,
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->driver)
            ->post(route('driver.end-trip'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trips', [
            'driver_id' => $this->driver->id,
            'status' => 'completed',
            'total_passengers' => 22,
        ]);
        $this->assertNotNull(Trip::first()->completed_at);
    }

    public function test_driver_cannot_end_a_trip_without_one_in_progress(): void
    {
        $this->actingAs($this->driver)
            ->post(route('driver.end-trip'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 0);
    }
}
