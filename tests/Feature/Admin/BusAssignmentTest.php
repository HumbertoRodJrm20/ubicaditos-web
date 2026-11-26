<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-02: la asignación de autobuses sólo admite cuentas de conductor.
 */
class BusAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_an_available_bus_to_a_driver(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
        $bus = Bus::create([
            'license_plate' => 'ASG-001',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.drivers'))
            ->post(route('admin.drivers.assign-bus', $driver), ['bus_id' => $bus->id])
            ->assertRedirect(route('admin.drivers'))
            ->assertSessionHasNoErrors();

        $this->assertSame($driver->id, $bus->fresh()->driver_id);
    }

    public function test_admin_cannot_assign_a_bus_to_a_non_driver(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $passenger = User::factory()->create(['role' => 'passenger']);
        $bus = Bus::create([
            'license_plate' => 'ASG-002',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.drivers.assign-bus', $passenger), ['bus_id' => $bus->id])
            ->assertForbidden();

        $this->assertNull($bus->fresh()->driver_id);
    }
}
