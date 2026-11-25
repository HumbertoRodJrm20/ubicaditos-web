<?php

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD de paradas del Estudio de rutas.
 */
class StopManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Parada Centro',
            'address' => 'Calle Zaragoza 1, Oaxaca',
            'latitude' => 17.0654,
            'longitude' => -96.7256,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_stop_endpoints_require_an_administrator(): void
    {
        $stop = Stop::create($this->payload());

        $endpoints = [
            ['POST', route('admin.stops.store')],
            ['PUT', route('admin.stops.update', $stop)],
            ['DELETE', route('admin.stops.destroy', $stop)],
            ['PATCH', route('admin.stops.status', $stop)],
        ];

        $this->call('POST', route('admin.stops.store'))
            ->assertRedirect(route('login'));

        foreach (['driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($endpoints as [$method, $url]) {
                $this->actingAs($user)->call($method, $url)->assertForbidden();
            }
        }
    }

    public function test_admin_can_create_a_stop_with_valid_coordinates(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.stops.store'), $this->payload())
            ->assertRedirect(route('admin.routes.manage'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stops', [
            'name' => 'Parada Centro',
            'latitude' => 17.0654,
            'longitude' => -96.7256,
            'is_active' => true,
        ]);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $cases = [
            ['latitude' => -90.0000001],
            ['latitude' => 90.0000001],
            ['longitude' => -180.0000001],
            ['longitude' => 180.0000001],
            ['latitude' => 'no-es-un-numero'],
            ['latitude' => null],
        ];

        foreach ($cases as $overrides) {
            $this->actingAs($this->admin())
                ->post(route('admin.stops.store'), $this->payload($overrides))
                ->assertSessionHasErrors(array_key_first($overrides));
        }

        $this->assertSame(0, Stop::count());
    }

    public function test_admin_can_update_stop_metadata_and_coordinates(): void
    {
        $stop = Stop::create($this->payload());

        $this->actingAs($this->admin())
            ->put(route('admin.stops.update', $stop), $this->payload([
                'name' => 'Parada Actualizada',
                'address' => 'Avenida Independencia 22',
                'latitude' => 17.08,
                'longitude' => -96.74,
            ]))
            ->assertRedirect(route('admin.routes.manage'));

        $stop->refresh();

        $this->assertSame('Parada Actualizada', $stop->name);
        $this->assertSame('Avenida Independencia 22', $stop->address);
        $this->assertSame(17.08, (float) $stop->latitude);
        $this->assertSame(-96.74, (float) $stop->longitude);
    }

    public function test_admin_can_activate_and_deactivate_a_stop(): void
    {
        $stop = Stop::create($this->payload());

        $this->actingAs($this->admin())
            ->patch(route('admin.stops.status', $stop))
            ->assertRedirect(route('admin.routes.manage'));

        $this->assertFalse($stop->fresh()->is_active);

        $this->actingAs($this->admin())
            ->patch(route('admin.stops.status', $stop))
            ->assertRedirect(route('admin.routes.manage'));

        $this->assertTrue($stop->fresh()->is_active);
    }

    public function test_shared_stop_is_deactivated_instead_of_deleted(): void
    {
        $stop = Stop::create($this->payload());
        $route = Route::create([
            'name' => 'Ruta Compartida',
            'code' => 'SC-1',
            'color' => '#10B981',
            'is_active' => true,
        ]);
        $route->stops()->attach($stop->id, ['order' => 0, 'estimated_time_from_previous' => 0]);

        $this->actingAs($this->admin())
            ->delete(route('admin.stops.destroy', $stop))
            ->assertRedirect(route('admin.routes.manage'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stops', ['id' => $stop->id, 'is_active' => false]);
        $this->assertDatabaseHas('route_stop', ['route_id' => $route->id, 'stop_id' => $stop->id]);
    }

    public function test_unused_stop_can_be_deleted(): void
    {
        $stop = Stop::create($this->payload());

        $this->actingAs($this->admin())
            ->delete(route('admin.stops.destroy', $stop))
            ->assertRedirect(route('admin.routes.manage'));

        $this->assertDatabaseMissing('stops', ['id' => $stop->id]);
    }
}
