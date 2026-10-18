<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestión de incidencias/emergencias desde el panel de administración.
 */
class EmergencyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function report(array $attrs = []): EmergencyReport
    {
        return EmergencyReport::create(array_merge([
            'reporter_id' => $this->admin->id,
            'type' => 'breakdown',
            'description' => 'Motor con humo en la ruta 1',
            'status' => 'pending',
        ], $attrs));
    }

    public function test_emergency_list_requires_authentication(): void
    {
        $this->get(route('admin.emergencies'))->assertRedirect(route('login'));
    }

    public function test_emergency_list_is_forbidden_for_driver_and_passenger(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.emergencies'))
                ->assertForbidden();
        }
    }

    public function test_admin_can_list_emergencies(): void
    {
        $this->report(['description' => 'Pinchazo en la rueda de atrás']);

        $this->actingAs($this->admin)
            ->get(route('admin.emergencies'))
            ->assertOk()
            ->assertViewIs('admin.emergencies')
            ->assertSee('Pinchazo en la rueda de atrás');
    }

    public function test_admin_can_filter_emergencies_by_status(): void
    {
        $this->report(['description' => 'Pendiente uno']);
        $this->report([
            'description' => 'Resuelto uno',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.emergencies', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Resuelto uno')
            ->assertDontSee('Pendiente uno');
    }

    public function test_admin_can_mark_an_emergency_as_resolved(): void
    {
        $report = $this->report();

        $this->actingAs($this->admin)
            ->post(route('admin.emergencies.resolve', $report))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.emergencies'));

        $fresh = $report->fresh();
        $this->assertSame('resolved', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_resolving_an_emergency_returns_the_bus_to_service(): void
    {
        $bus = Bus::create([
            'license_plate' => 'OAX701',
            'capacity' => 40,
            'status' => 'emergency',
            'is_active' => true,
        ]);
        $report = $this->report(['bus_id' => $bus->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.emergencies.resolve', $report))
            ->assertSessionHasNoErrors();

        $this->assertSame('active', $bus->fresh()->status);
    }

    public function test_resolving_does_not_touch_buses_in_other_statuses(): void
    {
        $bus = Bus::create([
            'license_plate' => 'OAX702',
            'capacity' => 40,
            'status' => 'maintenance',
            'is_active' => true,
        ]);
        $report = $this->report(['bus_id' => $bus->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.emergencies.resolve', $report));

        $this->assertSame('maintenance', $bus->fresh()->status);
    }

    public function test_only_pending_emergencies_can_be_resolved(): void
    {
        $report = $this->report(['status' => 'in_progress']);

        $this->actingAs($this->admin)
            ->post(route('admin.emergencies.resolve', $report))
            ->assertSessionHas('error');

        $fresh = $report->fresh();
        $this->assertSame('in_progress', $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }

    public function test_resolving_an_unknown_emergency_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.emergencies.resolve', 999))
            ->assertNotFound();
    }

    public function test_emergency_reported_by_driver_shows_bus_plate(): void
    {
        $bus = Bus::create([
            'license_plate' => 'OAX999',
            'capacity' => 30,
            'status' => 'emergency',
            'is_active' => true,
        ]);

        $this->report(['bus_id' => $bus->id, 'description' => 'Falla eléctrica']);

        $this->actingAs($this->admin)
            ->get(route('admin.emergencies'))
            ->assertOk()
            ->assertSee('OAX999');
    }
}
