<?php

namespace Tests\Feature\RealTime;

use App\Events\BusLocationUpdated;
use App\Models\Bus;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tiempo real: el evento de ubicación se emite con cada actualización GPS
 * y se publica en el canal privado "buses".
 */
class BusLocationUpdatedTest extends TestCase
{
    use RefreshDatabase;

    private function driverWithBus(): array
    {
        $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
        $bus = Bus::create([
            'license_plate' => 'OAX321',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);

        return [$driver, $bus];
    }

    public function test_updating_bus_location_dispatches_the_event(): void
    {
        Event::fake([BusLocationUpdated::class]);

        [$driver, $bus] = $this->driverWithBus();

        $this->actingAs($driver)
            ->postJson(route('driver.update-location'), [
                'latitude' => 17.0763,
                'longitude' => -96.7446,
            ])
            ->assertOk();

        Event::assertDispatched(
            BusLocationUpdated::class,
            fn (BusLocationUpdated $event) => $event->bus->is($bus)
        );
    }

    public function test_bus_location_update_stores_coordinates_and_timestamp(): void
    {
        [$driver, $bus] = $this->driverWithBus();

        $this->actingAs($driver)
            ->postJson(route('driver.update-location'), [
                'latitude' => 17.0763,
                'longitude' => -96.7446,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $bus->fresh();
        $this->assertEquals(17.0763, (float) $fresh->current_latitude);
        $this->assertEquals(-96.7446, (float) $fresh->current_longitude);
        $this->assertNotNull($fresh->last_location_update);
    }

    public function test_event_is_broadcast_on_the_private_buses_channel(): void
    {
        [, $bus] = $this->driverWithBus();
        $event = new BusLocationUpdated($bus);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);

        $channels = $event->broadcastOn();
        $this->assertContainsOnlyInstancesOf(PrivateChannel::class, $channels);
        $this->assertSame('private-buses', $channels[0]->name);
    }

    public function test_event_payload_contains_the_bus_position(): void
    {
        [, $bus] = $this->driverWithBus();
        $bus->updateLocation(17.0763, -96.7446);

        $payload = (new BusLocationUpdated($bus->fresh()))->broadcastWith();

        $this->assertSame($bus->id, $payload['id']);
        $this->assertEquals(17.0763, $payload['latitude']);
        $this->assertEquals(-96.7446, $payload['longitude']);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('last_update', $payload);
        $this->assertArrayHasKey('current_passengers', $payload);
    }

    public function test_event_broadcast_name_is_stable_for_clients(): void
    {
        [, $bus] = $this->driverWithBus();

        $this->assertSame(
            'bus.location.updated',
            (new BusLocationUpdated($bus))->broadcastAs()
        );
    }

    /**
     * Configura el driver de broadcasting real (reverb) y registra el canal
     * "buses" en ese driver, igual que en producción.
     */
    private function useReverbDriver(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'test-app-key',
                'secret' => 'test-app-secret',
                'app_id' => 'test-app-id',
                'options' => [
                    'host' => '127.0.0.1',
                    'port' => 8080,
                    'scheme' => 'http',
                    'useTLS' => false,
                ],
            ],
        ]);

        Broadcast::channel('buses', fn ($user) => $user !== null);
    }

    public function test_broadcasting_channel_authorizes_any_authenticated_user(): void
    {
        $this->useReverbDriver();

        $user = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-buses',
                'socket_id' => '123.456',
            ])
            ->assertOk();
    }

    public function test_broadcasting_channel_rejects_guests(): void
    {
        $this->useReverbDriver();

        $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-buses',
            'socket_id' => '123.456',
        ])->assertForbidden();
    }
}
