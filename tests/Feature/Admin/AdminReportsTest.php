<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vista única de reportes y estadísticas del panel administrativo.
 */
class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_access_admin_reports(): void
    {
        $this->get(route('admin.reports'))->assertRedirect(route('login'));
    }

    public function test_only_admins_can_access_admin_reports(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.reports'))
                ->assertForbidden();
        }
    }

    public function test_statistics_route_redirects_to_the_merged_reports_view(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.statistics'))
            ->assertRedirect(route('admin.reports'));
    }

    public function test_admin_can_view_the_merged_reports_and_statistics_page(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        User::factory()->create(['role' => 'passenger']);
        $route = Route::create([
            'name' => 'Ruta Reportes',
            'code' => 'RR',
            'color' => '#2563EB',
            'is_active' => true,
        ]);
        $bus = Bus::create([
            'license_plate' => 'REP123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ]);
        Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'status' => 'completed',
            'total_passengers' => 20,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertViewIs('admin.reports')
            ->assertSee('Reportes y Estadísticas')
            ->assertSee('Exportar PDF')
            ->assertSee('Exportar CSV')
            ->assertSee('Rutas más utilizadas')
            ->assertSee('Emergencias por tipo')
            ->assertSee('REP123')
            ->assertViewHas('tripsByDay', function ($tripsByDay) {
                return $tripsByDay->count() === 1
                    && (int) $tripsByDay->first()->count === 1;
            })
            ->assertViewHas('busUsage', fn ($busUsage) => $busUsage->contains('license_plate', 'REP123'))
            ->assertViewHas('topDrivers', fn ($topDrivers) => $topDrivers->contains('id', $driver->id))
            ->assertViewHas('topRoutes', fn ($topRoutes) => $topRoutes->contains('id', $route->id))
            ->assertViewHas('emergenciesByType', fn ($emergenciesByType) => is_array($emergenciesByType))
            ->assertViewHas('stats', function (array $stats) {
                return $stats['total_trips'] === 1
                    && $stats['total_buses'] === 1
                    && $stats['total_drivers'] === 1
                    && $stats['total_passengers'] === 1;
            });
    }
}
