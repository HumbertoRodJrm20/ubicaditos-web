<?php

namespace Tests\Feature\Admin;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestión de los reportes de incidencias de usuarios desde el panel admin.
 */
class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function incident(array $attributes = []): Incident
    {
        $user = isset($attributes['user_id'])
            ? User::find($attributes['user_id'])
            : User::factory()->create(['role' => 'passenger']);

        return Incident::create(array_merge([
            'user_id' => $user->id,
            'category' => Incident::CATEGORY_ROUTE,
            'description' => 'La ruta no respeta el horario',
            'status' => Incident::STATUS_PENDING,
        ], $attributes));
    }

    public function test_guest_cannot_access_incident_management(): void
    {
        $this->get(route('admin.incidents.index'))->assertRedirect(route('login'));
    }

    public function test_only_admins_can_access_incident_management(): void
    {
        foreach (['driver', 'passenger'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.incidents.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_can_list_incidents_from_all_users(): void
    {
        $this->incident(['description' => 'Reporte del pasajero uno']);
        $this->incident(['description' => 'Reporte del pasajero dos']);

        $this->actingAs($this->admin)
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertViewIs('admin.incidents.index')
            ->assertSee('Reporte del pasajero uno')
            ->assertSee('Reporte del pasajero dos')
            ->assertSee('Gestión de reportes');
    }

    public function test_admin_can_filter_incidents_by_status_and_category(): void
    {
        $this->incident([
            'description' => 'Pendiente de ruta',
            'category' => Incident::CATEGORY_ROUTE,
            'status' => Incident::STATUS_PENDING,
        ]);
        $this->incident([
            'description' => 'Resuelto de autobús',
            'category' => Incident::CATEGORY_BUS,
            'status' => Incident::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.incidents.index', ['status' => Incident::STATUS_RESOLVED]))
            ->assertOk()
            ->assertSee('Resuelto de autobús')
            ->assertDontSee('Pendiente de ruta');

        $this->actingAs($this->admin)
            ->get(route('admin.incidents.index', ['category' => Incident::CATEGORY_ROUTE]))
            ->assertOk()
            ->assertSee('Pendiente de ruta')
            ->assertDontSee('Resuelto de autobús');
    }

    public function test_admin_can_search_incidents_by_description(): void
    {
        $this->incident(['description' => 'El autobús no se detuvo en la parada']);
        $this->incident(['description' => 'Cobro indebido del pasaje']);

        $this->actingAs($this->admin)
            ->get(route('admin.incidents.index', ['q' => 'no se detuvo']))
            ->assertOk()
            ->assertSee('El autobús no se detuvo en la parada')
            ->assertDontSee('Cobro indebido del pasaje');
    }

    public function test_admin_can_move_an_incident_to_in_review(): void
    {
        $incident = $this->incident();

        $this->actingAs($this->admin)
            ->patch(route('admin.incidents.update', $incident), [
                'status' => Incident::STATUS_IN_REVIEW,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $fresh = $incident->fresh();
        $this->assertSame(Incident::STATUS_IN_REVIEW, $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }

    public function test_admin_can_resolve_an_incident_and_resolved_at_is_set(): void
    {
        $incident = $this->incident();

        $this->actingAs($this->admin)
            ->patch(route('admin.incidents.update', $incident), [
                'status' => Incident::STATUS_RESOLVED,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $incident->fresh();
        $this->assertSame(Incident::STATUS_RESOLVED, $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_reopening_a_resolved_incident_clears_resolved_at(): void
    {
        $incident = $this->incident([
            'status' => Incident::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.incidents.update', $incident), [
                'status' => Incident::STATUS_PENDING,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $incident->fresh();
        $this->assertSame(Incident::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $incident = $this->incident();

        $this->actingAs($this->admin)
            ->from(route('admin.incidents.index'))
            ->patch(route('admin.incidents.update', $incident), [
                'status' => 'inventado',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Incident::STATUS_PENDING, $incident->fresh()->status);
    }

    public function test_updating_an_unknown_incident_returns_not_found(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.incidents.update', 999), [
                'status' => Incident::STATUS_RESOLVED,
            ])
            ->assertNotFound();
    }

    public function test_incident_status_change_is_visible_to_the_author(): void
    {
        $author = User::factory()->create(['role' => 'passenger']);
        $incident = $this->incident([
            'user_id' => $author->id,
            'description' => 'Reporte con seguimiento',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.incidents.update', $incident), [
                'status' => Incident::STATUS_IN_REVIEW,
            ]);

        $this->actingAs($author)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('Reporte con seguimiento')
            ->assertSee('En revisión');
    }
}
