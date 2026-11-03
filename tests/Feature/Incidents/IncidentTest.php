<?php

namespace Tests\Feature\Incidents;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'passenger',
        ], $attributes));
    }

    public function test_guest_cannot_access_the_incident_module(): void
    {
        $this->get(route('incidents.index'))->assertRedirect(route('login'));
        $this->get(route('incidents.create'))->assertRedirect(route('login'));

        $this->post(route('incidents.store'), [
            'category' => Incident::CATEGORY_ROUTE,
            'description' => 'Intento sin sesión',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_authenticated_user_can_see_the_report_form(): void
    {
        $this->actingAs($this->passenger())
            ->get(route('incidents.create'))
            ->assertOk()
            ->assertSee('Reportar una incidencia')
            ->assertSee('Problemas con una ruta')
            ->assertSee('Problemas con un conductor')
            ->assertSee('Otro');
    }

    public function test_user_can_submit_an_incident_with_pending_status(): void
    {
        $user = $this->passenger();

        $response = $this->actingAs($user)->post(route('incidents.store'), [
            'category' => Incident::CATEGORY_BUS,
            'description' => 'La unidad mostraba una placa que no corresponde a la ruta.',
        ]);

        $response->assertRedirect(route('incidents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('incidents', [
            'user_id' => $user->id,
            'category' => Incident::CATEGORY_BUS,
            'description' => 'La unidad mostraba una placa que no corresponde a la ruta.',
            'status' => Incident::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('La unidad mostraba una placa que no corresponde a la ruta.')
            ->assertSee('Información incorrecta de un autobús')
            ->assertSee('Pendiente');
    }

    public function test_description_is_required_and_input_is_preserved(): void
    {
        $user = $this->passenger();

        $response = $this->actingAs($user)
            ->from(route('incidents.create'))
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_STOP,
                'description' => '',
            ]);

        $response->assertRedirect(route('incidents.create'));
        $response->assertSessionHasErrors('description');
        $response->assertSessionHasInput('category', Incident::CATEGORY_STOP);

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_category_is_required_and_must_be_known(): void
    {
        $user = $this->passenger();

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'description' => 'Reporte sin categoría',
            ])
            ->assertSessionHasErrors('category');

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => 'categoria_inventada',
                'description' => 'Reporte con categoría inválida',
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_description_cannot_exceed_the_maximum_length(): void
    {
        $user = $this->passenger();

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_SERVICE,
                'description' => str_repeat('a', Incident::MAX_DESCRIPTION_LENGTH + 1),
            ])
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_user_only_sees_their_own_incidents(): void
    {
        $user = $this->passenger();
        $other = $this->passenger();

        Incident::create([
            'user_id' => $user->id,
            'category' => Incident::CATEGORY_ROUTE,
            'description' => 'Reporte visible solo para el autor',
            'status' => Incident::STATUS_PENDING,
        ]);

        Incident::create([
            'user_id' => $other->id,
            'category' => Incident::CATEGORY_DRIVER,
            'description' => 'Reporte privado de otro usuario',
            'status' => Incident::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('Reporte visible solo para el autor')
            ->assertDontSee('Reporte privado de otro usuario');
    }

    public function test_user_cannot_override_the_status_or_owner_from_the_form(): void
    {
        $user = $this->passenger();
        $other = $this->passenger();

        $this->actingAs($user)->post(route('incidents.store'), [
            'category' => Incident::CATEGORY_OTHER,
            'description' => 'Intento de manipular el estado',
            'status' => Incident::STATUS_RESOLVED,
            'user_id' => $other->id,
        ])->assertRedirect(route('incidents.index'));

        $this->assertDatabaseHas('incidents', [
            'user_id' => $user->id,
            'description' => 'Intento de manipular el estado',
            'status' => Incident::STATUS_PENDING,
        ]);

        $this->assertDatabaseMissing('incidents', [
            'user_id' => $other->id,
            'description' => 'Intento de manipular el estado',
        ]);
    }

    public function test_reports_persist_after_reloading_the_list(): void
    {
        $user = $this->passenger();

        $this->actingAs($user)->post(route('incidents.store'), [
            'category' => Incident::CATEGORY_STOP,
            'description' => 'La parada no tiene señalización.',
        ]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('La parada no tiene señalización.');

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('La parada no tiene señalización.');

        $this->assertDatabaseCount('incidents', 1);
    }
}
