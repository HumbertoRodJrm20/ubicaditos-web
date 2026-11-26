<?php

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Búsqueda acotada de paradas para el Estudio de rutas.
 */
class StopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_stop_catalog_requires_an_administrator(): void
    {
        $route = Route::create([
            'name' => 'Ruta de autorización',
            'code' => 'RA-1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $this->getJson('/admin/stops/data')->assertUnauthorized();
        $this->getJson(route('admin.routes.stops.data', $route))->assertUnauthorized();

        foreach (['driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->getJson('/admin/stops/data')
                ->assertForbidden();

            $this->actingAs($user)
                ->getJson(route('admin.routes.stops.data', $route))
                ->assertForbidden();
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/admin/stops/data')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('admin.routes.stops.data', $route))
            ->assertOk();
    }

    public function test_stop_catalog_supports_search_and_bounded_pagination(): void
    {
        foreach (range(0, 56) as $index) {
            Stop::create([
                'name' => sprintf('Parada %02d', $index),
                'address' => $index === 3 ? 'Avenida Central' : 'Calle de prueba',
                'latitude' => 17.0 + ($index / 1000),
                'longitude' => -96.7,
                'is_active' => true,
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/admin/stops/data?q=Central&per_page=3&page=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('data.0.name', 'Parada 03');

        $this->actingAs($admin)
            ->getJson('/admin/stops/data?q=Parada&per_page=3&page=2')
            ->assertOk()
            ->assertJsonPath('total', 57)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('data.0.name', 'Parada 03')
            ->assertJsonCount(3, 'data');

        $this->actingAs($admin)
            ->getJson('/admin/stops/data?stop_id='.Stop::where('name', 'Parada 03')->value('id'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Parada 03');

        $this->actingAs($admin)
            ->getJson('/admin/stops/data?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->actingAs($admin)
            ->getJson('/admin/stops/data')
            ->assertOk()
            ->assertJsonPath('per_page', 50)
            ->assertJsonCount(50, 'data');
    }

    public function test_studio_initial_map_data_contains_only_main_route_stops(): void
    {
        $route = Route::create([
            'name' => 'Ruta Centro',
            'code' => 'RC-1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $mainStop = Stop::create([
            'name' => 'Parada principal',
            'latitude' => 17.0654,
            'longitude' => -96.7256,
            'is_active' => true,
        ]);
        $frequentStop = Stop::create([
            'name' => 'Parada frecuente',
            'latitude' => 17.0664,
            'longitude' => -96.7266,
            'is_active' => true,
        ]);
        $unassignedStop = Stop::create([
            'name' => 'Parada sin asignar',
            'latitude' => 17.0674,
            'longitude' => -96.7276,
            'is_active' => true,
        ]);

        $route->stops()->attach($mainStop->id, [
            'order' => 0,
            'estimated_time_from_previous' => 0,
            'is_frequent' => false,
        ]);
        $route->stops()->attach($frequentStop->id, [
            'order' => 1,
            'estimated_time_from_previous' => 2,
            'is_frequent' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.routes.manage.data'))
            ->assertOk()
            ->assertJsonCount(1, 'stops')
            ->assertJsonPath('stops.0.id', $mainStop->id);

        $stopIds = collect($response->json('stops'))->pluck('id');

        $this->assertNotContains($frequentStop->id, $stopIds);
        $this->assertNotContains($unassignedStop->id, $stopIds);
    }

    public function test_studio_caps_initial_map_stop_markers(): void
    {
        $route = Route::create([
            'name' => 'Ruta con muchas paradas',
            'code' => 'RC-2',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $pivots = [];

        foreach (range(1, 201) as $index) {
            $stop = Stop::create([
                'name' => sprintf('Parada %03d', $index),
                'latitude' => 17.0 + ($index / 10000),
                'longitude' => -96.7,
                'is_active' => true,
            ]);
            $pivots[$stop->id] = [
                'order' => $index,
                'estimated_time_from_previous' => 1,
                'is_frequent' => false,
            ];
        }

        $route->stops()->attach($pivots);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('admin.routes.manage.data'))
            ->assertOk()
            ->assertJsonCount(200, 'stops');
    }

    public function test_frequent_stops_load_only_for_the_selected_route(): void
    {
        $route = Route::create([
            'name' => 'Ruta con parada frecuente',
            'code' => 'RF-1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $mainStop = Stop::create([
            'name' => 'Parada principal',
            'latitude' => 17.0654,
            'longitude' => -96.7256,
            'is_active' => true,
        ]);
        $frequentStop = Stop::create([
            'name' => 'Parada frecuente',
            'latitude' => 17.0664,
            'longitude' => -96.7266,
            'is_active' => true,
        ]);
        $route->stops()->attach($mainStop->id, [
            'order' => 0,
            'estimated_time_from_previous' => 0,
            'is_frequent' => false,
        ]);
        $route->stops()->attach($frequentStop->id, [
            'order' => 1,
            'estimated_time_from_previous' => 2,
            'is_frequent' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('admin.routes.manage.data'))
            ->assertOk()
            ->assertJsonCount(1, 'routes.0.stops')
            ->assertJsonPath('routes.0.stops.0.id', $mainStop->id);

        $this->actingAs($admin)
            ->getJson(route('admin.routes.stops.data', $route))
            ->assertOk()
            ->assertJsonCount(2, 'stops')
            ->assertJsonPath('stops.1.id', $frequentStop->id)
            ->assertJsonPath('stops.1.pivot.is_frequent', true);
    }
}
