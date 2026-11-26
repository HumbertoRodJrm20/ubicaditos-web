<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\Incident;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard administrativo: contadores de flota y reportes de la iteración 7.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function bus(string $plate, string $status): Bus
    {
        return Bus::create([
            'license_plate' => $plate,
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => $status,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_only_admins_can_access_admin_dashboard(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.dashboard'))
                ->assertForbidden();
        }
    }

    public function test_dashboard_exposes_fleet_and_report_counters(): void
    {
        $this->bus('AAA111', 'active');
        $this->bus('BBB222', 'active');
        $this->bus('CCC333', 'maintenance');
        $this->bus('DDD444', 'emergency');
        $this->bus('EEE555', 'inactive');

        $driver = User::factory()->create(['role' => 'driver']);
        $passenger = User::factory()->create(['role' => 'passenger']);

        Incident::create([
            'user_id' => $passenger->id,
            'category' => Incident::CATEGORY_ROUTE,
            'description' => 'Incidencia pendiente',
            'status' => Incident::STATUS_PENDING,
        ]);
        Incident::create([
            'user_id' => $passenger->id,
            'category' => Incident::CATEGORY_BUS,
            'description' => 'Incidencia resuelta',
            'status' => Incident::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        EmergencyReport::create([
            'reporter_id' => $driver->id,
            'bus_id' => Bus::where('license_plate', 'DDD444')->value('id'),
            'type' => 'breakdown',
            'description' => 'Unidad detenida',
            'status' => 'pending',
        ]);
        EmergencyReport::create([
            'reporter_id' => $driver->id,
            'type' => 'other',
            'description' => 'Reporte ya resuelto',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('stats', function (array $stats) {
                return $stats['total_buses'] === 5
                    && $stats['active_buses'] === 2
                    && $stats['maintenance_buses'] === 1
                    && $stats['emergency_buses'] === 1
                    && $stats['inactive_buses'] === 1
                    && $stats['pending_incidents'] === 1
                    && $stats['pending_emergencies'] === 1;
            })
            ->assertSee('Estado de la flota y reportes')
            ->assertSee('Mantenimiento')
            ->assertSee('Incidencias');
    }

    public function test_dashboard_renders_with_empty_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No hay autobuses activos en este momento.')
            ->assertSee('No hay viajes registrados todavía.');
    }

    public function test_dashboard_shows_platform_average_rating_with_empty_state(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $driver = User::factory()->create(['role' => 'driver']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['average_rating'] === null && $stats['ratings_count'] === 0;
            })
            ->assertSee('Calificación promedio')
            ->assertSee('—');

        Rating::create([
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'rating' => 5,
            'comment' => 'Excelente servicio',
        ]);
        Rating::create([
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'rating' => 4,
            'comment' => 'Buena atención',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['average_rating'] === 4.5 && $stats['ratings_count'] === 2;
            })
            ->assertSee('4.5')
            ->assertSee('2 calificaciones');
    }
}
