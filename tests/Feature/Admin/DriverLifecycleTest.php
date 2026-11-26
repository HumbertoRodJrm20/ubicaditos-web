<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Rating;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Caracterización de la eliminación de conductores con historial.
 */
class DriverLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_driver_deactivates_and_preserves_trip_and_rating_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);
        $passenger = User::factory()->create(['role' => 'passenger']);
        $route = Route::create([
            'name' => 'Ruta de conductor',
            'code' => 'RD-1',
            'color' => '#2563EB',
            'is_active' => true,
        ]);
        $bus = Bus::create([
            'license_plate' => 'DRV123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'inactive',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $trip = Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'status' => 'completed',
        ]);
        $rating = Rating::create([
            'passenger_id' => $passenger->id,
            'trip_id' => $trip->id,
            'rating' => 5,
            'comment' => 'Buen servicio',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $driver))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $driver->id, 'is_active' => false]);
        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
        $this->assertDatabaseHas('ratings', ['id' => $rating->id]);
        $this->assertDatabaseHas('buses', ['id' => $bus->id, 'driver_id' => null]);
    }

    public function test_driver_with_an_active_trip_cannot_be_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);
        $route = Route::create([
            'name' => 'Ruta activa conductor',
            'code' => 'RAC-1',
            'color' => '#2563EB',
            'is_active' => true,
        ]);
        $bus = Bus::create([
            'license_plate' => 'DRA123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $trip = Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.drivers'))
            ->delete(route('admin.users.destroy', $driver))
            ->assertRedirect(route('admin.drivers'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $driver->id, 'is_active' => true]);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('buses', ['id' => $bus->id, 'driver_id' => $driver->id]);
    }

    public function test_deactivated_driver_is_hidden_from_the_active_driver_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create([
            'role' => 'driver',
            'first_name' => 'Desactivado',
            'email' => 'deactivated-driver@example.test',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.drivers'))
            ->assertOk()
            ->assertDontSee($driver->email);
    }

    public function test_driver_list_explains_deactivation_preserves_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'driver']);

        $this->actingAs($admin)
            ->get(route('admin.drivers'))
            ->assertOk()
            ->assertSee('Desactivar')
            ->assertSee('Se conservará su historial');
    }
}
