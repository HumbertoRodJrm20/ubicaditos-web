<?php

namespace Tests\Feature\Passenger;

use App\Models\Bus;
use App\Models\LocationShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-012: el mapa muestra sólo un conteo agregado de pasajeros que comparten
 * su ubicación en un autobús; nunca identidades ni enlaces privados.
 */
class LocationShareAggregateTest extends TestCase
{
    use RefreshDatabase;

    private function makeBus(): Bus
    {
        return Bus::create([
            'license_plate' => 'SHARE-001',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
            'last_location_update' => now()->subMinute(),
        ]);
    }

    private function startShare(User $user, Bus $bus, array $overrides = []): string
    {
        return $this->actingAs($user)
            ->postJson(route('passenger.share-location.start'), array_merge([
                'latitude' => 17.0600000,
                'longitude' => -96.7250000,
                'bus_id' => $bus->id,
            ], $overrides))
            ->assertOk()
            ->json('token');
    }

    public function test_active_shares_on_a_bus_are_counted_without_identities(): void
    {
        $bus = $this->makeBus();
        $passenger = User::factory()->create(['role' => 'passenger']);
        $viewer = User::factory()->create(['role' => 'passenger']);

        $this->startShare($passenger, $bus);

        $response = $this->actingAs($viewer)
            ->getJson(route('api.buses.active'))
            ->assertOk();

        $this->assertSame(1, $response->json('0.sharing_passengers_count'));
        $response->assertJsonMissingPath('0.sharers')
            ->assertJsonMissingPath('0.user_id')
            ->assertJsonMissingPath('0.passenger_id')
            ->assertJsonMissingPath('0.token');
    }

    public function test_expired_and_revoked_shares_are_not_counted(): void
    {
        $bus = $this->makeBus();
        $viewer = User::factory()->create(['role' => 'passenger']);

        $expiredUser = User::factory()->create(['role' => 'passenger']);
        $this->startShare($expiredUser, $bus);
        LocationShare::where('user_id', $expiredUser->id)->first()->update([
            'expires_at' => now()->subMinute(),
        ]);

        $revokedUser = User::factory()->create(['role' => 'passenger']);
        $this->startShare($revokedUser, $bus);
        LocationShare::where('user_id', $revokedUser->id)->first()->revoke();

        $this->actingAs($viewer)
            ->getJson(route('api.buses.active'))
            ->assertOk()
            ->assertJsonPath('0.sharing_passengers_count', 0);
    }

    public function test_shares_without_a_bus_are_not_counted_and_bus_must_exist(): void
    {
        $bus = $this->makeBus();
        $viewer = User::factory()->create(['role' => 'passenger']);

        $this->startShare(User::factory()->create(['role' => 'passenger']), $bus, ['bus_id' => null]);

        $this->actingAs($viewer)
            ->getJson(route('api.buses.active'))
            ->assertOk()
            ->assertJsonPath('0.sharing_passengers_count', 0);

        $this->actingAs(User::factory()->create(['role' => 'passenger']))
            ->postJson(route('passenger.share-location.start'), [
                'latitude' => 17.06,
                'longitude' => -96.72,
                'bus_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bus_id');
    }

    public function test_bus_can_be_updated_in_an_existing_share(): void
    {
        $bus = $this->makeBus();
        $user = User::factory()->create(['role' => 'passenger']);
        $token = $this->startShare($user, $bus, ['bus_id' => null]);

        $share = LocationShare::where('token', $token)->first();

        $this->actingAs($user)
            ->patchJson(route('passenger.share-location.update', $share), [
                'latitude' => 17.06,
                'longitude' => -96.72,
                'bus_id' => $bus->id,
            ])
            ->assertOk();

        $this->assertSame($bus->id, $share->fresh()->bus_id);
    }
}
