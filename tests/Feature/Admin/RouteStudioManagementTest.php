<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Operaciones del Estudio de rutas y paradas.
 */
class RouteStudioManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function routePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ruta Estudio',
            'code' => 'RE-1',
            'color' => '#F59E0B',
            'estimated_duration' => 60,
            'is_active' => 1,
        ], $overrides);
    }

    /**
     * Blade @js escapa las barras de las URLs; el HTML contiene `\/`.
     */
    private function jsUrl(string $url): string
    {
        return str_replace('/', str_repeat(chr(92), 3).'/', $url);
    }

    private function makeStop(string $name, float $latitude): Stop
    {
        return Stop::create([
            'name' => $name,
            'address' => 'Calle de prueba',
            'latitude' => $latitude,
            'longitude' => -96.7256,
            'is_active' => true,
        ]);
    }

    private function makeRoute(string $code = 'RB-1'): Route
    {
        return Route::create([
            'name' => "Ruta {$code}",
            'code' => $code,
            'color' => '#3B82F6',
            'estimated_duration' => 60,
            'is_active' => true,
        ]);
    }

    public function test_studio_sends_the_data_needed_by_the_map(): void
    {
        $route = $this->makeRoute();
        $stop = $this->makeStop('Parada Centro', 17.0654);
        $route->stops()->attach($stop->id, ['order' => 0, 'estimated_time_from_previous' => 0]);

        $this->actingAs($this->admin())
            ->get(route('admin.routes.manage'))
            ->assertOk()
            ->assertSee('Estudio de rutas y paradas')
            ->assertSee('route-studio-map', false)
            ->assertSee('data-csrf', false)
            ->assertSee($this->jsUrl(route('admin.routes.manage.data')), false)
            ->assertSee($this->jsUrl(route('admin.stops.store')), false)
            ->assertSee($this->jsUrl(route('admin.stops.data')), false)
            ->assertSee($this->jsUrl(route('admin.routes.stops.data', $route)), false)
            ->assertSee($this->jsUrl(route('admin.routes.stops.sync', $route)), false)
            ->assertSee($this->jsUrl(route('admin.routes.geometry.regenerate', $route)), false);

        $this->actingAs($this->admin())
            ->getJson(route('admin.routes.manage.data'))
            ->assertOk()
            ->assertJsonPath('routes.0.code', 'RB-1')
            ->assertJsonPath('stops.0.name', 'Parada Centro')
            ->assertJsonMissingPath('stops.0.email')
            ->assertJsonMissingPath('routes.0.users');
    }

    public function test_studio_load_does_not_call_osrm_and_exposes_unsaved_change_status(): void
    {
        Http::fake();
        $route = $this->makeRoute();
        $firstStop = $this->makeStop('Primera parada', 17.060);
        $secondStop = $this->makeStop('Segunda parada', 17.061);
        $route->stops()->attach($firstStop->id, ['order' => 0, 'estimated_time_from_previous' => 0]);
        $route->stops()->attach($secondStop->id, ['order' => 1, 'estimated_time_from_previous' => 3]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.routes.manage'))
            ->assertOk()
            ->assertSee('Cambios sin guardar')
            ->assertSee('x-show="hasUnsavedChanges"', false);

        $this->actingAs($admin)
            ->getJson(route('admin.routes.manage.data'))
            ->assertOk();

        Http::assertNothingSent();
    }

    public function test_write_endpoints_are_protected_from_other_roles(): void
    {
        $route = $this->makeRoute();

        foreach (['driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->postJson(route('admin.routes.stops.sync', $route), ['stops' => []])
                ->assertForbidden();

            $this->actingAs($user)
                ->postJson(route('admin.routes.geometry.regenerate', $route))
                ->assertForbidden();

            $this->actingAs($user)
                ->patch(route('admin.routes.status', $route))
                ->assertForbidden();
        }
    }

    public function test_admin_can_change_route_status(): void
    {
        $route = $this->makeRoute();

        $this->actingAs($this->admin())
            ->patch(route('admin.routes.status', $route))
            ->assertRedirect(route('admin.routes.manage'));

        $this->assertFalse($route->fresh()->is_active);
    }

    public function test_route_with_trips_is_deactivated_instead_of_deleted(): void
    {
        $route = $this->makeRoute();
        $bus = Bus::create([
            'license_plate' => 'OAX778',
            'model' => 'Volkswagen 9.160',
            'capacity' => 35,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
            'current_passengers' => 0,
        ]);
        $driver = User::factory()->create(['role' => 'driver']);
        $trip = Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.routes.destroy', $route))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('routes', ['id' => $route->id, 'is_active' => false]);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'route_id' => $route->id]);
    }

    public function test_sync_endpoint_attaches_stops_in_the_submitted_order(): void
    {
        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $c = $this->makeStop('Parada C', 17.062);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.stops.sync', $route), [
                'stops' => [$c->id, $a->id, $b->id],
                'minutes' => [$a->id => 11, $b->id => 4],
            ])
            ->assertOk()
            ->assertJsonPath('added', [$c->id, $a->id, $b->id]);

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            $route->getOrderedStops()->pluck('id')->all()
        );
        $this->assertSame(0, (int) DB::table('route_stop')->where('stop_id', $c->id)->value('estimated_time_from_previous'));
        $this->assertSame(11, (int) DB::table('route_stop')->where('stop_id', $a->id)->value('estimated_time_from_previous'));
        $this->assertSame(4, (int) DB::table('route_stop')->where('stop_id', $b->id)->value('estimated_time_from_previous'));
    }

    public function test_sync_endpoint_rejects_duplicate_stops_without_changes(): void
    {
        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.stops.sync', $route), [
                'stops' => [$b->id, $b->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stops.0');

        $this->assertSame([$a->id], $route->stops()->pluck('stops.id')->all());
    }

    public function test_sync_endpoint_rolls_back_when_a_stop_does_not_exist(): void
    {
        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.stops.sync', $route), [
                'stops' => [$b->id, 999999],
            ])
            ->assertUnprocessable();

        $this->assertSame([$a->id], $route->stops()->pluck('stops.id')->all());
    }

    public function test_sync_endpoint_can_detach_a_stop_from_one_route_only(): void
    {
        $routeA = $this->makeRoute('RA-1');
        $routeB = $this->makeRoute('RB-2');
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);

        $routeA->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);
        $routeA->stops()->attach($b->id, ['order' => 1, 'estimated_time_from_previous' => 3]);
        $routeB->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.stops.sync', $routeA), ['stops' => [$b->id]])
            ->assertOk()
            ->assertJsonPath('removed', [$a->id]);

        $this->assertSame([$b->id], $routeA->stops()->pluck('stops.id')->all());
        $this->assertSame([$a->id], $routeB->stops()->pluck('stops.id')->all());
        $this->assertDatabaseHas('stops', ['id' => $a->id]);
    }

    public function test_regenerate_endpoint_persists_valid_osrm_geometry(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'geometry' => ['coordinates' => [
                        [-96.7256, 17.0654],
                        [-96.7266, 17.0664],
                        [-96.7276, 17.0674],
                    ]],
                ]],
            ]),
        ]);

        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);
        $route->stops()->attach($b->id, ['order' => 1, 'estimated_time_from_previous' => 3]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.geometry.regenerate', $route))
            ->assertOk()
            ->assertJsonPath('status', 'generated')
            ->assertJsonPath('points', 3);

        $this->assertSame(3, count($route->fresh()->path_coordinates));
        $this->assertSame(17.0654, $route->fresh()->path_coordinates[0]['lat']);
        Http::assertSentCount(1);
    }

    public function test_regenerate_endpoint_keeps_previous_geometry_when_osrm_fails(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 500),
        ]);

        $previous = [['lat' => 17.05, 'lng' => -96.70], ['lat' => 17.06, 'lng' => -96.71]];
        $route = $this->makeRoute();
        $route->update(['path_coordinates' => $previous]);
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 0]);
        $route->stops()->attach($b->id, ['order' => 1, 'estimated_time_from_previous' => 3]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.routes.geometry.regenerate', $route))
            ->assertStatus(502)
            ->assertJsonPath('status', 'failed');

        $this->assertSame($previous, $route->fresh()->path_coordinates);
    }

    public function test_route_status_endpoint_returns_json_for_ajax_requests(): void
    {
        $route = $this->makeRoute();

        $this->actingAs($this->admin())
            ->patchJson(route('admin.routes.status', $route))
            ->assertOk()
            ->assertJsonPath('is_active', false)
            ->assertJsonPath('message', 'Ruta desactivada.');

        $this->assertFalse($route->fresh()->is_active);
    }

    public function test_stop_endpoints_return_json_for_ajax_requests(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.stops.store'), [
                'name' => 'Parada AJAX',
                'address' => 'Avenida Principal',
                'latitude' => 17.066,
                'longitude' => -96.726,
            ])
            ->assertCreated()
            ->assertJsonPath('stop.name', 'Parada AJAX')
            ->assertJsonPath('stop.latitude', 17.066);

        $stopId = DB::table('stops')->where('name', 'Parada AJAX')->value('id');

        $this->actingAs($this->admin())
            ->putJson(route('admin.stops.update', $stopId), [
                'name' => 'Parada editada',
                'address' => 'Calle Segunda',
                'latitude' => 17.067,
                'longitude' => -96.727,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('stop.name', 'Parada editada');

        $this->actingAs($this->admin())
            ->patchJson(route('admin.stops.status', $stopId))
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.stops.destroy', $stopId))
            ->assertOk()
            ->assertJsonPath('message', 'Parada eliminada exitosamente');

        $this->assertDatabaseMissing('stops', ['id' => $stopId]);
    }
}
