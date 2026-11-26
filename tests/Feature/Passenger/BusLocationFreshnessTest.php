<?php

namespace Tests\Feature\Passenger;

use App\Events\BusLocationUpdated;
use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * HU-014/RF-30: una posición sólo es fresca durante cinco minutos y el
 * estado se publica en todas las fuentes que consume el mapa del pasajero.
 */
class BusLocationFreshnessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeBus(array $attributes = []): Bus
    {
        return Bus::create(array_merge([
            'license_plate' => 'FRESH-001',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0613,
            'current_longitude' => -96.7252,
        ], $attributes));
    }

    public function test_location_is_fresh_until_five_minutes_have_elapsed(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');

        $bus = $this->makeBus(['last_location_update' => now()->subMinutes(4)->subSeconds(59)]);
        $this->assertTrue($bus->isLocationFresh());

        $bus = $this->makeBus([
            'license_plate' => 'FRESH-002',
            'last_location_update' => now()->subMinutes(6),
        ]);
        $this->assertFalse($bus->isLocationFresh());
    }

    public function test_a_bus_without_location_timestamp_is_not_fresh(): void
    {
        $bus = $this->makeBus();

        $this->assertFalse($bus->isLocationFresh());
        $this->assertNull($bus->fresh_until);
    }

    public function test_realtime_payloads_expose_freshness_for_fresh_and_stale_buses(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $route = Route::create([
            'name' => 'Ruta frescura',
            'code' => 'FR-101',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $fresh = $this->makeBus([
            'license_plate' => 'FRESH-001',
            'last_location_update' => now()->subMinute(),
        ]);
        $stale = $this->makeBus([
            'license_plate' => 'FRESH-002',
            'last_location_update' => now()->subMinutes(6),
        ]);
        $fresh->routes()->attach($route->id, ['assigned_at' => now()]);
        $stale->routes()->attach($route->id, ['assigned_at' => now()]);

        $activePayload = $this->actingAs($passenger)
            ->getJson(route('api.buses.active'))
            ->assertOk()
            ->json();

        $freshPayload = collect($activePayload)->firstWhere('id', $fresh->id);
        $stalePayload = collect($activePayload)->firstWhere('id', $stale->id);

        $this->assertTrue($freshPayload['fresh']);
        $this->assertNotNull($freshPayload['fresh_until']);
        $this->assertFalse($stalePayload['fresh']);
        $this->assertNotNull($stalePayload['fresh_until']);

        $this->actingAs($passenger)
            ->getJson(route('api.buses.location', $stale))
            ->assertOk()
            ->assertJsonPath('fresh', false)
            ->assertJsonStructure(['fresh_until']);

        $routePayload = $this->actingAs($passenger)
            ->getJson(route('api.routes.buses', $route))
            ->assertOk()
            ->json();

        $routeStale = collect($routePayload)->firstWhere('id', $stale->id);
        $this->assertFalse($routeStale['fresh']);
        $this->assertArrayHasKey('fresh_until', $routeStale);
    }

    public function test_broadcast_payload_contains_the_same_freshness_contract(): void
    {
        $bus = $this->makeBus(['last_location_update' => now()->subMinutes(6)]);

        $payload = (new BusLocationUpdated($bus->fresh()))->broadcastWith();

        $this->assertFalse($payload['fresh']);
        $this->assertArrayHasKey('fresh_until', $payload);
    }
}
