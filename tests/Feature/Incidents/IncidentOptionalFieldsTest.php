<?php

namespace Tests\Feature\Incidents;

use App\Models\Bus;
use App\Models\Incident;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-025: incidencias con referencias opcionales y visibilidad pública sin identidad.
 */
class IncidentOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'passenger'], $attributes));
    }

    private function route(string $code = 'OAX-901'): Route
    {
        return Route::create([
            'name' => 'Ruta de prueba',
            'code' => $code,
            'description' => 'Recorrido de prueba',
            'color' => '#6b7bad',
            'is_active' => true,
        ]);
    }

    private function bus(string $plate = 'INC001'): Bus
    {
        return Bus::create([
            'license_plate' => $plate,
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_report_form_offers_route_bus_coordinates_and_public_visibility(): void
    {
        $route = $this->route();
        $bus = $this->bus();

        $this->actingAs($this->passenger())
            ->get(route('incidents.create'))
            ->assertOk()
            ->assertSee('name="route_id"', false)
            ->assertSee('name="bus_id"', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('name="is_public"', false)
            ->assertSee($route->name)
            ->assertSee($bus->license_plate);
    }

    public function test_incident_can_store_every_optional_field(): void
    {
        $passenger = $this->passenger();
        $route = $this->route();
        $bus = $this->bus();

        $this->actingAs($passenger)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_BUS,
                'description' => 'Unidad fuera de ruta',
                'route_id' => $route->id,
                'bus_id' => $bus->id,
                'latitude' => 17.0623456,
                'longitude' => -96.7234567,
                'is_public' => '1',
            ])
            ->assertRedirect(route('incidents.index'));

        $incident = Incident::first();
        $this->assertSame($route->id, $incident->route_id);
        $this->assertSame($bus->id, $incident->bus_id);
        $this->assertSame('17.0623456', (string) $incident->latitude);
        $this->assertSame('-96.7234567', (string) $incident->longitude);
        $this->assertTrue($incident->is_public);
    }

    public function test_incident_without_optional_fields_remains_private(): void
    {
        $this->actingAs($this->passenger())
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_OTHER,
                'description' => 'Reporte sin referencias',
            ])
            ->assertRedirect(route('incidents.index'));

        $incident = Incident::first();
        $this->assertNull($incident->route_id);
        $this->assertNull($incident->bus_id);
        $this->assertNull($incident->latitude);
        $this->assertNull($incident->longitude);
        $this->assertFalse($incident->is_public);
    }

    public function test_invalid_optional_references_and_coordinates_are_rejected(): void
    {
        $user = $this->passenger();
        $bus = $this->bus();

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_ROUTE,
                'description' => 'Ruta inexistente',
                'route_id' => 9999,
            ])
            ->assertSessionHasErrors('route_id');

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_BUS,
                'description' => 'Unidad inexistente',
                'bus_id' => 9999,
            ])
            ->assertSessionHasErrors('bus_id');

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_STOP,
                'description' => 'Coordenadas inválidas',
                'latitude' => 91,
                'longitude' => -181,
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'category' => Incident::CATEGORY_STOP,
                'description' => 'Sólo longitud',
                'longitude' => -96.72,
            ])
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('incidents', 0);
        $this->assertSame('active', $bus->fresh()->status);
    }

    public function test_public_listing_hides_private_incidents_and_reporter_identity(): void
    {
        $author = $this->passenger([
            'first_name' => 'Sofía',
            'last_name' => 'Privada',
            'email' => 'sofia.privada@example.test',
        ]);
        $route = $this->route();
        $bus = $this->bus();

        Incident::create([
            'user_id' => $author->id,
            'category' => Incident::CATEGORY_ROUTE,
            'description' => 'Reporte público de prueba',
            'status' => Incident::STATUS_PENDING,
            'route_id' => $route->id,
            'bus_id' => $bus->id,
            'latitude' => 17.06,
            'longitude' => -96.72,
            'is_public' => true,
        ]);
        Incident::create([
            'user_id' => $author->id,
            'category' => Incident::CATEGORY_OTHER,
            'description' => 'Reporte privado de prueba',
            'status' => Incident::STATUS_PENDING,
            'is_public' => false,
        ]);

        $response = $this->get(route('incidents.public'))
            ->assertOk()
            ->assertSee('Reporte público de prueba')
            ->assertSee($route->name)
            ->assertSee($bus->license_plate)
            ->assertDontSee('Reporte privado de prueba')
            ->assertDontSee('sofia.privada@example.test')
            ->assertDontSee('Sofía')
            ->assertDontSee('Privada')
            ->assertDontSee('Reportó');

        $this->assertFalse($response->viewData('incidents')->first()->relationLoaded('user'));
    }

    public function test_admin_listing_shows_public_badge_and_references(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $passenger = $this->passenger();
        $route = $this->route();
        $bus = $this->bus();

        Incident::create([
            'user_id' => $passenger->id,
            'category' => Incident::CATEGORY_BUS,
            'description' => 'Reporte con contexto para el admin',
            'status' => Incident::STATUS_PENDING,
            'route_id' => $route->id,
            'bus_id' => $bus->id,
            'is_public' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertSee('Reporte con contexto para el admin')
            ->assertSee('Público')
            ->assertSee($route->name)
            ->assertSee($bus->license_plate);
    }
}
