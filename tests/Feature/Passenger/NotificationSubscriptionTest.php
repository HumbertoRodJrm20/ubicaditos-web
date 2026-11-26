<?php

namespace Tests\Feature\Passenger;

use App\Models\Bus;
use App\Models\BusStopSubscription;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-012: avisos in-app "avisame cuando bus X llegue a la parada Y" con
 * suscripción idempotente y estrictamente limitada al propio pasajero.
 */
class NotificationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Bus $bus;

    private Stop $stop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bus = Bus::create([
            'license_plate' => 'NOT-001',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->stop = Stop::create([
            'name' => 'Parada notificación',
            'latitude' => 17.0650,
            'longitude' => -96.7300,
            'is_active' => true,
        ]);

        Route::create([
            'name' => 'Ruta notificación',
            'code' => 'NOT-101',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
    }

    private function passenger(): User
    {
        return User::factory()->create(['role' => 'passenger']);
    }

    private function subscribePayload(): array
    {
        return [
            'bus_id' => $this->bus->id,
            'stop_id' => $this->stop->id,
        ];
    }

    public function test_passenger_can_subscribe_idempotently(): void
    {
        $passenger = $this->passenger();

        $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), $this->subscribePayload())
            ->assertOk()
            ->assertJsonPath('subscribed', true);

        $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), $this->subscribePayload())
            ->assertOk()
            ->assertJsonPath('subscribed', true);

        $this->assertSame(1, BusStopSubscription::count());
    }

    public function test_passenger_can_list_and_remove_only_their_own_subscriptions(): void
    {
        $passenger = $this->passenger();
        $other = $this->passenger();

        $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), $this->subscribePayload())
            ->assertOk();

        $subscription = BusStopSubscription::first();

        $this->actingAs($other)
            ->getJson(route('passenger.notifications.index'))
            ->assertOk()
            ->assertJsonPath('subscriptions', []);

        $this->actingAs($other)
            ->deleteJson(route('passenger.notifications.unsubscribe', $subscription))
            ->assertForbidden();

        $this->actingAs($passenger)
            ->deleteJson(route('passenger.notifications.unsubscribe', $subscription))
            ->assertOk()
            ->assertJsonPath('subscribed', false);

        $this->actingAs($passenger)
            ->deleteJson(route('passenger.notifications.unsubscribe', $subscription))
            ->assertOk()
            ->assertJsonPath('subscribed', false);

        $this->assertSame(0, BusStopSubscription::count());
    }

    public function test_subscription_validation_rejects_unknown_resources(): void
    {
        $passenger = $this->passenger();

        $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), [
                'bus_id' => 999999,
                'stop_id' => $this->stop->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bus_id');

        $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), [
                'bus_id' => $this->bus->id,
                'stop_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stop_id');
    }

    public function test_subscription_endpoints_enforce_roles(): void
    {
        $url = route('passenger.notifications.subscribe');

        $this->postJson($url, $this->subscribePayload())->assertUnauthorized();

        foreach (['admin', 'driver'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->postJson($url, $this->subscribePayload())
                ->assertForbidden();
        }

        $this->actingAs($this->passenger())
            ->postJson($url, $this->subscribePayload())
            ->assertOk();
    }

    public function test_subscription_payload_has_no_user_identity(): void
    {
        $passenger = $this->passenger();

        $response = $this->actingAs($passenger)
            ->postJson(route('passenger.notifications.subscribe'), $this->subscribePayload())
            ->assertOk();

        $response->assertJsonMissingPath('user_id')
            ->assertJsonMissingPath('passenger_id')
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('name');
    }
}
