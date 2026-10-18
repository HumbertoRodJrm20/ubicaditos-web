<?php

namespace Tests\Feature\Driver;

use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\Rating;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estadísticas del perfil del conductor.
 */
class DriverStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private Bus $bus;
    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $this->bus = Bus::create([
            'license_plate' => 'OAX789',
            'model' => 'Toyota Coaster',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $this->driver->id,
        ]);

        $this->route = Route::create([
            'name' => 'Ruta Juárez',
            'code' => 'RJ',
            'color' => '#10B981',
            'is_active' => true,
        ]);
        $this->bus->routes()->attach($this->route->id, ['assigned_at' => now()]);
    }

    public function test_statistics_page_requires_authentication(): void
    {
        $this->get(route('driver.statistics'))->assertRedirect(route('login'));
    }

    public function test_statistics_page_is_forbidden_for_other_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'passenger']))
            ->get(route('driver.statistics'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('driver.statistics'))
            ->assertForbidden();
    }

    public function test_driver_can_view_their_statistics_page(): void
    {
        $this->actingAs($this->driver)
            ->get(route('driver.statistics'))
            ->assertOk()
            ->assertViewIs('driver.statistics');
    }

    public function test_statistics_aggregate_trips_occupancy_and_ratings(): void
    {
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $this->driver->id,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
            'total_passengers' => 20,
            'distance_km' => 8.50,
            'status' => 'completed',
        ]);
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $this->driver->id,
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(3),
            'total_passengers' => 40,
            'distance_km' => 8.50,
            'status' => 'completed',
        ]);
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $this->driver->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Rating::create([
            'passenger_id' => User::factory()->create(['role' => 'passenger'])->id,
            'driver_id' => $this->driver->id,
            'rating' => 5,
        ]);
        Rating::create([
            'passenger_id' => User::factory()->create(['role' => 'passenger'])->id,
            'driver_id' => $this->driver->id,
            'rating' => 3,
        ]);

        $this->actingAs($this->driver)
            ->get(route('driver.statistics'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['total_trips'] === 3
                    && $stats['completed_trips'] === 2
                    && $stats['active_trips'] === 1
                    && $stats['total_passengers'] === 60
                    && $stats['avg_occupancy'] == 75.0
                    && $stats['avg_rating'] == 4.0
                    && $stats['ratings_count'] === 2
                    && $stats['total_distance_km'] == 17.00
                    && $stats['total_minutes'] === 120;
            });
    }

    public function test_statistics_include_emergencies_reported_by_the_driver(): void
    {
        EmergencyReport::create([
            'reporter_id' => $this->driver->id,
            'bus_id' => $this->bus->id,
            'type' => 'breakdown',
            'description' => 'Freno con falla',
            'status' => 'resolved',
        ]);

        $this->actingAs($this->driver)
            ->get(route('driver.statistics'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['emergencies_reported'] === 1);
    }

    public function test_statistics_do_not_leak_data_from_other_drivers(): void
    {
        $other = User::factory()->create(['role' => 'driver']);
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => $this->route->id,
            'driver_id' => $other->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->driver)
            ->get(route('driver.statistics'))
            ->assertViewHas('stats', fn (array $stats) => $stats['total_trips'] === 0);
    }
}
