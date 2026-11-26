<?php

namespace Tests\Feature\Driver;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-10: el conductor puede elegir autobús y ruta válidos al iniciar un viaje.
 */
class StartTripSelectionTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    private Bus $bus;

    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-17 09:00:00');

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $this->bus = $this->makeTripBus($this->driver->id, 'SEL-001', 17.0613, -96.7252);
        $this->route = $this->makeTripRoute('SEL-R1');
        $this->bus->routes()->attach($this->route->id, ['assigned_at' => now()]);
        $this->makeStopFor($this->route, 17.0613, -96.7252);
    }

    public function test_start_trip_uses_the_assigned_bus_and_first_route_by_default(): void
    {
        $this->postStartTrip()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trips', [
            'driver_id' => $this->driver->id,
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_start_trip_persists_the_selected_bus_and_route(): void
    {
        $selectedBus = $this->makeTripBus(null, 'SEL-002', 17.0620, -96.7260);
        $selectedRoute = $this->makeTripRoute('SEL-R2');
        $selectedBus->routes()->attach($selectedRoute->id, ['assigned_at' => now()]);
        $this->makeStopFor($selectedRoute, 17.0620, -96.7260);

        $this->postStartTrip([
            'bus_id' => $selectedBus->id,
            'route_id' => $selectedRoute->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trips', [
            'driver_id' => $this->driver->id,
            'bus_id' => $selectedBus->id,
            'route_id' => $selectedRoute->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_start_trip_rejects_a_route_not_linked_to_the_selected_bus(): void
    {
        $foreignRoute = $this->makeTripRoute('SEL-R3');

        $this->postStartTrip([
            'bus_id' => $this->bus->id,
            'route_id' => $foreignRoute->id,
        ])->assertSessionHasErrors('route_id');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_start_trip_rejects_a_bus_assigned_to_another_driver(): void
    {
        $otherDriver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $foreignBus = $this->makeTripBus($otherDriver->id, 'SEL-004', 17.0613, -96.7252);
        $foreignBus->routes()->attach($this->route->id, ['assigned_at' => now()]);

        $this->postStartTrip(['bus_id' => $foreignBus->id])
            ->assertSessionHasErrors('bus_id');

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_start_trip_rejects_a_bus_already_used_in_an_active_trip(): void
    {
        $otherDriver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $busyBus = $this->makeTripBus(null, 'SEL-005', 17.0613, -96.7252);
        $busyBus->routes()->attach($this->route->id, ['assigned_at' => now()]);
        Trip::create([
            'driver_id' => $otherDriver->id,
            'bus_id' => $busyBus->id,
            'route_id' => $this->route->id,
            'started_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $this->postStartTrip(['bus_id' => $busyBus->id])
            ->assertSessionHasErrors('bus_id');

        $this->assertDatabaseCount('trips', 1);
    }

    public function test_driver_cannot_start_a_second_active_trip(): void
    {
        Trip::create([
            'driver_id' => $this->driver->id,
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'started_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $this->postStartTrip()
            ->assertSessionHasErrors('trip')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trips', 1);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('driver.start-trip'))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('forbiddenRoles')]
    public function test_non_driver_roles_cannot_start_a_trip(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->post(route('driver.start-trip'))
            ->assertForbidden();
    }

    public static function forbiddenRoles(): array
    {
        return [
            ['passenger'],
            ['admin'],
        ];
    }

    public function test_driver_map_offers_bus_and_route_selectors(): void
    {
        $otherBus = $this->makeTripBus(null, 'SEL-006', 17.0630, -96.7270);
        $otherRoute = $this->makeTripRoute('SEL-R6');
        $otherBus->routes()->attach($otherRoute->id, ['assigned_at' => now()]);

        $this->actingAs($this->driver)
            ->get(route('driver.map'))
            ->assertOk()
            ->assertSee('name="bus_id"', false)
            ->assertSee('name="route_id"', false)
            ->assertSee('SEL-001')
            ->assertSee($this->route->name);
    }

    public function test_driver_map_can_start_from_an_available_bus_without_a_permanent_assignment(): void
    {
        $this->bus->update(['driver_id' => null]);

        $this->actingAs($this->driver)
            ->get(route('driver.map'))
            ->assertOk()
            ->assertSee('name="bus_id"', false)
            ->assertSee('SEL-001')
            ->assertSee($this->route->name);
    }

    private function postStartTrip(array $payload = [])
    {
        return $this->actingAs($this->driver)->post(route('driver.start-trip'), $payload);
    }

    private function makeTripBus(?int $driverId, string $licensePlate, float $latitude, float $longitude): Bus
    {
        return Bus::create([
            'license_plate' => $licensePlate,
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driverId,
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
        ]);
    }

    private function makeTripRoute(string $code): Route
    {
        return Route::create([
            'name' => 'Ruta '.$code,
            'code' => $code,
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
    }

    private function makeStopFor(Route $route, float $latitude, float $longitude): void
    {
        $stop = Stop::create([
            'name' => 'Parada '.$route->code,
            'address' => 'Oaxaca',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_active' => true,
        ]);

        $route->stops()->attach($stop->id, [
            'order' => 1,
            'estimated_time_from_previous' => 0,
        ]);
    }
}
