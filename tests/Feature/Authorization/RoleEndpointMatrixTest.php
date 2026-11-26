<?php

namespace Tests\Feature\Authorization;

use App\Models\Bus;
use App\Models\LocationShare;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RNF-02: matriz explícita de roles para los endpoints JSON en tiempo real.
 */
class RoleEndpointMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Bus $bus;

    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);

        $this->bus = Bus::create([
            'license_plate' => 'API-001',
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
        ]);

        $this->route = Route::create([
            'name' => 'Ruta API',
            'code' => 'API-R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $this->bus->routes()->attach($this->route->id, ['assigned_at' => now()]);

        Stop::create([
            'name' => 'Parada API',
            'latitude' => 17.0613,
            'longitude' => -96.7252,
            'is_active' => true,
        ]);
    }

    #[DataProvider('allowedRoles')]
    public function test_every_allowed_role_can_consume_the_realtime_endpoints(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        foreach ($this->realtimeEndpoints() as $url) {
            $this->actingAs($user)->getJson($url)->assertOk();
        }
    }

    public static function allowedRoles(): array
    {
        return [
            ['admin'],
            ['driver'],
            ['passenger'],
        ];
    }

    public function test_an_unsupported_role_is_rejected_on_every_realtime_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);
        $user->role = 'auditor';

        foreach ($this->realtimeEndpoints() as $url) {
            $this->actingAs($user)->getJson($url)->assertForbidden();
        }
    }

    public function test_guests_are_redirected_to_login_on_every_realtime_endpoint(): void
    {
        foreach ($this->realtimeEndpoints() as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_public_share_endpoint_does_not_expose_driver_or_passenger_identity(): void
    {
        $passenger = User::factory()->create([
            'role' => 'passenger',
            'first_name' => 'Identidad',
            'last_name' => 'Privada',
        ]);
        $share = LocationShare::startFor($passenger, [
            'latitude' => 17.0613,
            'longitude' => -96.7252,
            'accuracy' => 12,
        ]);

        $response = $this->getJson(route('api.share.data', $share->token));

        $response->assertOk()
            ->assertJsonMissingPath('name')
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('user_id')
            ->assertJsonMissingPath('passenger_id')
            ->assertJsonMissingPath('driver_id');
    }

    /**
     * @return array<int, string>
     */
    private function realtimeEndpoints(): array
    {
        return [
            route('api.buses.active'),
            route('api.buses.location', $this->bus),
            route('api.routes.buses', $this->route),
            route('api.stops.eta', Stop::firstOrFail()),
        ];
    }
}
