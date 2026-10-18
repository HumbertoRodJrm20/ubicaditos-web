<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detalle de un autobús desde el panel de administración.
 */
class BusShowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->bus = Bus::create([
            'license_plate' => 'OAX555',
            'model' => 'Volkswagen 9.160',
            'capacity' => 35,
            'status' => 'maintenance',
            'is_active' => true,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
            'current_passengers' => 7,
        ]);
    }

    public function test_bus_details_require_authentication(): void
    {
        $this->get(route('admin.buses.show', $this->bus))->assertRedirect(route('login'));
    }

    public function test_bus_details_are_forbidden_for_non_admins(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.buses.show', $this->bus))
                ->assertForbidden();
        }
    }

    public function test_admin_can_view_bus_details(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.buses.show', $this->bus))
            ->assertOk()
            ->assertViewIs('admin.buses.show')
            ->assertViewHas('bus', fn (Bus $bus) => $bus->id === $this->bus->id)
            ->assertSee('OAX555')
            ->assertSee('Volkswagen 9.160');
    }

    public function test_unknown_bus_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.buses.show', 999))
            ->assertNotFound();
    }

    public function test_bus_details_include_recent_trips(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        Trip::create([
            'bus_id' => $this->bus->id,
            'route_id' => \App\Models\Route::create([
                'name' => 'Ruta Detalle',
                'code' => 'RD',
                'color' => '#F59E0B',
                'is_active' => true,
            ])->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.buses.show', $this->bus))
            ->assertOk()
            ->assertViewHas('recentTrips', fn ($trips) => $trips->count() === 1);
    }
}
