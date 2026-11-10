<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado y gestión inicial de autobuses desde el panel de administración.
 */
class BusManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function bus(array $attributes = []): Bus
    {
        static $plate = 100;

        return Bus::create(array_merge([
            'license_plate' => 'BUS' . $plate++,
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_cannot_access_bus_management(): void
    {
        $this->get(route('admin.buses.index'))->assertRedirect(route('login'));
    }

    public function test_only_admins_can_access_bus_management(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.buses.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_can_list_buses_with_fleet_summary(): void
    {
        $this->bus(['license_plate' => 'AAA111', 'status' => 'active']);
        $this->bus(['license_plate' => 'BBB222', 'status' => 'active']);
        $this->bus(['license_plate' => 'CCC333', 'status' => 'maintenance']);
        $this->bus(['license_plate' => 'DDD444', 'status' => 'emergency']);
        $this->bus(['license_plate' => 'EEE555', 'status' => 'inactive']);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index'))
            ->assertOk()
            ->assertViewIs('admin.buses.index')
            ->assertSee('AAA111')
            ->assertSee('EEE555')
            ->assertViewHas('summary', function (array $summary) {
                return $summary['total'] === 5
                    && $summary['active'] === 2
                    && $summary['maintenance'] === 1
                    && $summary['emergency'] === 1
                    && $summary['inactive'] === 1;
            });
    }

    public function test_admin_can_filter_buses_by_status(): void
    {
        $this->bus(['license_plate' => 'ACT111', 'status' => 'active']);
        $this->bus(['license_plate' => 'MNT222', 'status' => 'maintenance']);
        $this->bus(['license_plate' => 'EMR333', 'status' => 'emergency']);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['status' => 'maintenance']))
            ->assertOk()
            ->assertSee('MNT222')
            ->assertDontSee('ACT111')
            ->assertDontSee('EMR333')
            ->assertViewHas('status', 'maintenance');
    }

    public function test_unknown_status_filter_is_ignored(): void
    {
        $this->bus(['license_plate' => 'ACT111', 'status' => 'active']);
        $this->bus(['license_plate' => 'MNT222', 'status' => 'maintenance']);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['status' => 'inventado']))
            ->assertOk()
            ->assertSee('ACT111')
            ->assertSee('MNT222')
            ->assertViewHas('status', null);
    }

    public function test_admin_can_search_buses_by_plate_model_or_driver(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'first_name' => 'Ramiro',
            'last_name' => 'Cruz',
        ]);

        $this->bus([
            'license_plate' => 'ABC123',
            'model' => 'Yutong',
            'driver_id' => $driver->id,
        ]);
        $this->bus([
            'license_plate' => 'XYZ987',
            'model' => 'Dina',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['search' => 'ABC123']))
            ->assertOk()
            ->assertSee('ABC123')
            ->assertDontSee('XYZ987');

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['search' => 'Dina']))
            ->assertOk()
            ->assertSee('XYZ987')
            ->assertDontSee('ABC123');

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['search' => 'Ramiro']))
            ->assertOk()
            ->assertSee('ABC123')
            ->assertDontSee('XYZ987');
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        $this->bus();

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('buses', fn ($buses) => $buses->perPage() === 15);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.index', ['per_page' => 50]))
            ->assertOk()
            ->assertViewHas('buses', fn ($buses) => $buses->perPage() === 50);
    }

    public function test_admin_can_create_a_bus_with_routes(): void
    {
        $route = Route::create([
            'name' => 'Ruta Centro',
            'code' => 'RC',
            'color' => '#2563EB',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.buses.store'), [
                'license_plate' => 'NEW123',
                'model' => 'Yutong',
                'capacity' => 45,
                'status' => 'active',
                'routes' => [$route->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.buses.index'));

        $bus = Bus::where('license_plate', 'NEW123')->first();
        $this->assertNotNull($bus);
        $this->assertSame('active', $bus->status);
        $this->assertTrue($bus->routes->contains($route));
    }

    public function test_bus_creation_rejects_emergency_status_and_invalid_plate(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.buses.create'))
            ->post(route('admin.buses.store'), [
                'license_plate' => 'abc123',
                'model' => 'Yutong',
                'capacity' => 45,
                'status' => 'emergency',
            ])
            ->assertSessionHasErrors(['license_plate', 'status']);

        $this->assertDatabaseCount('buses', 0);
    }

    public function test_admin_can_update_a_bus(): void
    {
        $bus = $this->bus(['license_plate' => 'UPD123', 'status' => 'active']);

        $this->actingAs($this->admin)
            ->put(route('admin.buses.update', $bus), [
                'license_plate' => 'UPD123',
                'model' => 'Dina',
                'capacity' => 50,
                'status' => 'maintenance',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.buses.index'));

        $fresh = $bus->fresh();
        $this->assertSame('Dina', $fresh->model);
        $this->assertSame(50, $fresh->capacity);
        $this->assertSame('maintenance', $fresh->status);
    }

    public function test_admin_can_delete_a_bus(): void
    {
        $bus = $this->bus(['license_plate' => 'DEL123']);

        $this->actingAs($this->admin)
            ->delete(route('admin.buses.destroy', $bus))
            ->assertRedirect();

        $this->assertDatabaseMissing('buses', ['id' => $bus->id]);
    }

    public function test_non_admin_cannot_create_update_or_delete_buses(): void
    {
        $bus = $this->bus(['license_plate' => 'SEC123']);

        foreach (['driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('admin.buses.store'), [])
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('admin.buses.update', $bus), [])
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('admin.buses.destroy', $bus))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('buses', ['id' => $bus->id]);
    }
}
