<?php

namespace Tests\Feature\Admin;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de caracterización del CRUD actual de rutas (Admin\RouteController).
 *
 * Fijan el comportamiento existente antes de construir el Estudio de rutas.
 * Las pruebas "known_*" documentan defectos o riesgos conocidos: se invierten
 * cuando se corrigen, no antes.
 */
class RouteCrudCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
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

    private function makeRoute(string $code = 'RB-1', string $name = 'Ruta Base'): Route
    {
        return Route::create([
            'name' => $name,
            'code' => $code,
            'color' => '#3B82F6',
            'estimated_duration' => 60,
            'is_active' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ruta Nueva',
            'code' => 'RN-1',
            'color' => '#10B981',
            'estimated_duration' => 60,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_store_saves_the_submitted_stop_order(): void
    {
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $c = $this->makeStop('Parada C', 17.062);

        $this->actingAs($this->admin)
            ->post(route('admin.routes.store'), $this->validPayload([
                'stops' => [$c->id, $a->id, $b->id],
            ]))
            ->assertRedirect(route('admin.routes.index'));

        $route = Route::where('code', 'RN-1')->firstOrFail();

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            $route->getOrderedStops()->pluck('id')->all()
        );
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->makeRoute('RB-1', 'Ruta Base');

        $cases = [
            ['name', ['name' => 'Ruta Base']],
            ['name', ['name' => str_repeat('A', 31)]],
            ['code', ['code' => 'RB-1']],
            ['estimated_duration', ['estimated_duration' => 39]],
            ['estimated_duration', ['estimated_duration' => 91]],
        ];

        foreach ($cases as [$field, $overrides]) {
            $this->actingAs($this->admin)
                ->post(route('admin.routes.store'), $this->validPayload($overrides))
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(1, Route::count());
    }

    public function test_update_keeps_pivot_metadata_of_retained_stops(): void
    {
        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $c = $this->makeStop('Parada C', 17.062);

        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 7, 'is_frequent' => false]);
        $route->stops()->attach($b->id, ['order' => 1, 'estimated_time_from_previous' => 3, 'is_frequent' => true]);
        $route->stops()->attach($c->id, ['order' => 2, 'estimated_time_from_previous' => 5, 'is_frequent' => false]);

        $this->actingAs($this->admin)
            ->put(route('admin.routes.update', $route), [
                'name' => $route->name,
                'code' => $route->code,
                'is_active' => 1,
                'stops' => [$b->id, $a->id],
            ])
            ->assertRedirect(route('admin.routes.index'));

        // Al reordenar, la primera parada queda en 0 y la siguiente se
        // recalcula porque cambió su predecesora.
        $this->assertSame(0, (int) DB::table('route_stop')
            ->where('route_id', $route->id)->where('stop_id', $b->id)
            ->value('estimated_time_from_previous'));
        $this->assertGreaterThan(0, (int) DB::table('route_stop')
            ->where('route_id', $route->id)->where('stop_id', $a->id)
            ->value('estimated_time_from_previous'));
        $this->assertDatabaseMissing('route_stop', [
            'route_id' => $route->id,
            'stop_id' => $c->id,
        ]);
    }

    public function test_update_without_stops_field_cannot_empty_a_route(): void
    {
        $route = $this->makeRoute();
        $a = $this->makeStop('Parada A', 17.060);
        $route->stops()->attach($a->id, ['order' => 0, 'estimated_time_from_previous' => 7, 'is_frequent' => false]);

        $this->actingAs($this->admin)
            ->put(route('admin.routes.update', $route), [
                'name' => $route->name,
                'code' => $route->code,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.routes.index'));

        $this->assertSame(1, $route->stops()->count());
    }

    public function test_database_rejects_duplicate_route_stop_pairs(): void
    {
        $stop = $this->makeStop('Parada duplicada', 17.060);
        $route = $this->makeRoute();
        $pivot = ['order' => 0, 'estimated_time_from_previous' => 0, 'is_frequent' => false];

        $route->stops()->attach($stop->id, $pivot);

        $this->expectException(QueryException::class);
        $route->stops()->attach($stop->id, $pivot);
    }

    public function test_selected_route_stops_endpoint_preserves_combined_order(): void
    {
        $a = $this->makeStop('Parada A', 17.060);
        $b = $this->makeStop('Parada B', 17.061);
        $c = $this->makeStop('Parada C', 17.062);

        $route = $this->makeRoute();
        $route->stops()->attach($c->id, ['order' => 0, 'estimated_time_from_previous' => 1, 'is_frequent' => false]);
        $route->stops()->attach($a->id, ['order' => 1, 'estimated_time_from_previous' => 1, 'is_frequent' => true]);
        $route->stops()->attach($b->id, ['order' => 2, 'estimated_time_from_previous' => 1, 'is_frequent' => false]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.routes.stops.data', $route))
            ->assertOk()
            ->assertJsonPath('stops.0.name', 'Parada C')
            ->assertJsonPath('stops.1.name', 'Parada A')
            ->assertJsonPath('stops.2.name', 'Parada B');
    }

    public function test_legacy_route_forms_do_not_load_or_render_the_stop_catalog(): void
    {
        $attached = $this->makeStop('Parada asignada', 17.060);
        $this->makeStop('Parada no asignada', 17.061);
        $route = $this->makeRoute();
        $route->stops()->attach($attached->id, [
            'order' => 0,
            'estimated_time_from_previous' => 0,
            'is_frequent' => false,
        ]);

        $this->actingAs($this->admin);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $createResponse = $this->get(route('admin.routes.create'));
        $createQueries = DB::getQueryLog();

        DB::flushQueryLog();
        $editResponse = $this->get(route('admin.routes.edit', $route));
        $editQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $createResponse
            ->assertOk()
            ->assertDontSee('name="stops[]"', false)
            ->assertSee(route('admin.routes.manage'));

        $editResponse
            ->assertOk()
            ->assertDontSee('name="stops[]"', false)
            ->assertDontSee('Parada no asignada')
            ->assertSee(route('admin.routes.manage'));

        $stopQueries = array_filter(
            [...$createQueries, ...$editQueries],
            fn (array $query) => str_contains(strtolower($query['query']), 'from "stops"')
        );

        $this->assertSame([], array_values($stopQueries));
    }

    public function test_destroy_deactivates_a_route_with_trips(): void
    {
        $route = $this->makeRoute();
        $bus = Bus::create([
            'license_plate' => 'OAX777',
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

        $this->actingAs($this->admin)
            ->delete(route('admin.routes.destroy', $route))
            ->assertRedirect();

        $this->assertDatabaseHas('routes', ['id' => $route->id, 'is_active' => false]);
        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
    }

    public function test_destroy_deactivates_a_favorited_route_and_preserves_the_favorite(): void
    {
        $route = $this->makeRoute();
        $passenger = User::factory()->create(['role' => 'passenger']);
        $passenger->favoriteRoutes()->attach($route->id);

        $this->actingAs($this->admin)
            ->delete(route('admin.routes.destroy', $route))
            ->assertRedirect();

        $this->assertDatabaseHas('routes', ['id' => $route->id, 'is_active' => false]);
        $this->assertDatabaseHas('favorite_routes', [
            'user_id' => $passenger->id,
            'route_id' => $route->id,
        ]);
    }

    public function test_destroy_deactivates_a_route_with_a_bus_assignment(): void
    {
        $route = $this->makeRoute();
        $bus = Bus::create([
            'license_plate' => 'ASN777',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ]);
        $route->buses()->attach($bus->id);

        $this->actingAs($this->admin)
            ->delete(route('admin.routes.destroy', $route))
            ->assertRedirect();

        $this->assertDatabaseHas('routes', ['id' => $route->id, 'is_active' => false]);
        $this->assertDatabaseHas('bus_route', ['route_id' => $route->id, 'bus_id' => $bus->id]);
    }

    public function test_destroy_deactivates_a_liked_route_and_preserves_the_like(): void
    {
        $route = $this->makeRoute();
        $passenger = User::factory()->create(['role' => 'passenger']);
        $like = $route->likes()->create(['user_id' => $passenger->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.routes.destroy', $route))
            ->assertRedirect();

        $this->assertDatabaseHas('routes', ['id' => $route->id, 'is_active' => false]);
        $this->assertDatabaseHas('likes', ['id' => $like->id]);
    }

    public function test_color_must_be_a_valid_hex_value(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.routes.store'), $this->validPayload(['color' => 'rojo']))
            ->assertSessionHasErrors('color');

        $this->assertSame(0, Route::count());

        $this->actingAs($this->admin)
            ->post(route('admin.routes.store'), $this->validPayload(['color' => '#10B981']))
            ->assertRedirect(route('admin.routes.index'));

        $this->assertSame('#10B981', Route::where('code', 'RN-1')->value('color'));
    }
}
