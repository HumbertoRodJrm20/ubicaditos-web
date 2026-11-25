<?php

namespace Tests\Feature\Passenger;

use App\Models\LocationShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Compartir ubicación en vivo mediante un enlace temporal.
 *
 * El pasajero inicia una compartición (token aleatorio + caducidad), puede
 * actualizar o detener su posición, y cualquier persona con el enlace puede
 * consultar la última ubicación mientras siga activa.
 */
class LocationShareTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'passenger'], $attributes));
    }

    private function startShare(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->postJson(route('passenger.share-location.start'), array_merge([
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
            'accuracy' => 12,
        ], $overrides));
    }

    public function test_guest_cannot_start_sharing_location(): void
    {
        $this->post(route('passenger.share-location.start'), [
            'latitude' => 17.06,
            'longitude' => -96.72,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('location_shares', 0);
    }

    public function test_passenger_can_start_sharing_location(): void
    {
        $user = $this->passenger();

        $response = $this->startShare($user);

        $response->assertOk()
            ->assertJsonStructure(['token', 'url', 'expires_at'])
            ->assertJsonPath('active', true);

        $share = LocationShare::first();

        $this->assertNotNull($share);
        $this->assertSame($user->id, $share->user_id);
        $this->assertSame(48, strlen($share->token));
        $this->assertTrue($share->isActive());
        $this->assertNull($share->revoked_at);
        $this->assertTrue($share->expires_at->isFuture());
        $this->assertSame('17.0600000', $share->latitude);
        $this->assertSame('-96.7250000', $share->longitude);
        $this->assertSame(12, $share->accuracy);
    }

    public function test_starting_a_new_share_revokes_the_previous_active_one(): void
    {
        $user = $this->passenger();

        $first = $this->startShare($user)->json('token');
        $second = $this->startShare($user, ['latitude' => 17.07])->json('token');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, LocationShare::count());
        $this->assertFalse(LocationShare::where('token', $first)->first()->isActive());
        $this->assertTrue(LocationShare::where('token', $second)->first()->isActive());
    }

    public function test_owner_can_update_the_shared_position(): void
    {
        $user = $this->passenger();
        $token = $this->startShare($user)->json('token');
        $share = LocationShare::where('token', $token)->first();

        $this->actingAs($user)
            ->patchJson(route('passenger.share-location.update', $share), [
                'latitude' => 17.0654321,
                'longitude' => -96.7412345,
                'accuracy' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('active', true);

        $fresh = $share->fresh();
        $this->assertSame('17.0654321', $fresh->latitude);
        $this->assertSame('-96.7412345', $fresh->longitude);
        $this->assertSame(8, $fresh->accuracy);
    }

    public function test_another_user_cannot_update_or_stop_the_share(): void
    {
        $owner = $this->passenger();
        $intruder = $this->passenger();
        $token = $this->startShare($owner)->json('token');
        $share = LocationShare::where('token', $token)->first();

        $this->actingAs($intruder)
            ->patchJson(route('passenger.share-location.update', $share), [
                'latitude' => 1,
                'longitude' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->deleteJson(route('passenger.share-location.stop', $share))
            ->assertForbidden();

        $this->assertTrue($share->fresh()->isActive());
    }

    public function test_the_public_data_endpoint_returns_the_last_position_without_auth(): void
    {
        $user = $this->passenger();
        $token = $this->startShare($user)->json('token');

        $response = $this->getJson(route('api.share.data', $token));

        $response->assertOk()
            ->assertJsonPath('token', $token)
            ->assertJsonPath('latitude', 17.06)
            ->assertJsonPath('longitude', -96.725)
            ->assertJsonPath('accuracy', 12)
            ->assertJsonPath('active', true);
    }

    public function test_the_public_share_page_is_available_without_auth(): void
    {
        $user = $this->passenger();
        $token = $this->startShare($user)->json('token');

        $this->get(route('share.show', $token))
            ->assertOk()
            ->assertViewIs('share.show')
            ->assertViewHas('token', $token)
            ->assertSee('Ubicación compartida');
    }

    public function test_expired_shares_are_not_publicly_accessible(): void
    {
        $user = $this->passenger();
        $token = $this->startShare($user)->json('token');
        $share = LocationShare::where('token', $token)->first();
        $share->update(['expires_at' => now()->subMinute()]);

        $this->getJson(route('api.share.data', $token))->assertNotFound();
        $this->get(route('share.show', $token))->assertNotFound();
    }

    public function test_revoked_shares_are_not_publicly_accessible(): void
    {
        $user = $this->passenger();
        $token = $this->startShare($user)->json('token');
        $share = LocationShare::where('token', $token)->first();

        $this->actingAs($user)
            ->deleteJson(route('passenger.share-location.stop', $share))
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->assertNotNull($share->fresh()->revoked_at);
        $this->getJson(route('api.share.data', $token))->assertNotFound();
    }

    public function test_current_endpoint_returns_the_active_share_or_null(): void
    {
        $user = $this->passenger();

        $this->actingAs($user)
            ->getJson(route('passenger.share-location.current'))
            ->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('share', null);

        $token = $this->startShare($user)->json('token');

        $this->actingAs($user)
            ->getJson(route('passenger.share-location.current'))
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('share.token', $token);
    }

    public function test_coordinates_are_required_and_validated(): void
    {
        $user = $this->passenger();

        $this->actingAs($user)
            ->postJson(route('passenger.share-location.start'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->actingAs($user)
            ->postJson(route('passenger.share-location.start'), [
                'latitude' => 120,
                'longitude' => -300,
                'accuracy' => 'no-es-numero',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude', 'accuracy']);

        $this->assertDatabaseCount('location_shares', 0);
    }
}
