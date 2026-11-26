<?php

namespace Tests\Feature\Passenger;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HU-009/HU-30/RF-30: ETA por autobús con una única definición de frescura
 * y avisos explícitos cuando el dato no permite mostrar un tiempo preciso.
 */
class ArrivalEstimateTest extends TestCase
{
    use RefreshDatabase;

    private Route $route;

    private Stop $stop;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->route = Route::create([
            'name' => 'Ruta ETA',
            'code' => 'ETA-101',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $this->stop = Stop::create([
            'name' => 'Parada ETA',
            'address' => 'Centro',
            'latitude' => 17.0650,
            'longitude' => -96.7300,
            'is_active' => true,
        ]);

        $this->route->stops()->attach($this->stop->id, [
            'order' => 1,
            'estimated_time_from_previous' => 0,
            'is_frequent' => false,
        ]);
    }

    private function makeBus(string $plate, array $attributes = []): Bus
    {
        $bus = Bus::create(array_merge([
            'license_plate' => $plate,
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0600,
            'current_longitude' => -96.7250,
            'last_location_update' => now()->subMinute(),
        ], $attributes));

        $bus->routes()->attach($this->route->id, ['assigned_at' => now()]);

        return $bus;
    }

    public function test_eta_endpoint_returns_an_estimate_for_a_fresh_bus(): void
    {
        $bus = $this->makeBus('ETA-001');
        $passenger = User::factory()->create(['role' => 'passenger']);

        $response = $this->actingAs($passenger)
            ->getJson(route('api.stops.eta', $this->stop))
            ->assertOk()
            ->assertJsonPath('stop_id', $this->stop->id)
            ->assertJsonPath('estimated', true)
            ->assertJsonPath('uncertain', false)
            ->assertJsonPath('buses.0.id', $bus->id)
            ->assertJsonPath('buses.0.fresh', true)
            ->assertJsonPath('buses.0.estimated', true)
            ->assertJsonPath('buses.0.uncertain', false);

        $this->assertNotNull($response->json('buses.0.eta_minutes'));
        $this->assertIsInt($response->json('buses.0.eta_minutes'));
        $this->assertNull($response->json('notice'));
        $response->assertJsonMissingPath('driver')
            ->assertJsonMissingPath('driver_name')
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('user_id');

        Http::assertNothingSent();
    }

    public function test_stale_bus_is_flagged_uncertain_without_a_precise_time(): void
    {
        $bus = $this->makeBus('ETA-002', ['last_location_update' => now()->subMinutes(6)]);
        $passenger = User::factory()->create(['role' => 'passenger']);

        $response = $this->actingAs($passenger)
            ->getJson(route('api.stops.eta', $this->stop))
            ->assertOk()
            ->assertJsonPath('estimated', true)
            ->assertJsonPath('uncertain', true)
            ->assertJsonPath('buses.0.id', $bus->id)
            ->assertJsonPath('buses.0.fresh', false)
            ->assertJsonPath('buses.0.uncertain', true)
            ->assertJsonPath('buses.0.eta_minutes', null);

        $this->assertStringContainsString('aproximad', (string) $response->json('notice'));
    }

    public function test_no_active_bus_serving_the_stop_is_uncertain(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $response = $this->actingAs($passenger)
            ->getJson(route('api.stops.eta', $this->stop))
            ->assertOk()
            ->assertJsonPath('estimated', false)
            ->assertJsonPath('uncertain', true)
            ->assertJsonPath('buses', []);

        $this->assertStringContainsString('aproximad', (string) $response->json('notice'));
    }

    public function test_eta_endpoint_enforces_the_realtime_role_matrix(): void
    {
        $url = route('api.stops.eta', $this->stop);

        $this->get($url)->assertRedirect(route('login'));

        foreach (['admin', 'driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->getJson($url)->assertOk();
        }

        $user = User::factory()->create(['role' => 'passenger']);
        $user->role = 'auditor';

        $this->actingAs($user)->getJson($url)->assertForbidden();
    }

    public function test_passenger_map_exposes_the_eta_endpoint_and_uncertainty_notice(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)
            ->get(route('passenger.dashboard'))
            ->assertOk()
            ->assertSee('id="eta-uncertainty-notice"', false)
            ->assertSee('aproximad', false)
            ->assertSee(route('api.stops.eta', ['stop' => 1]), false);
    }
}
