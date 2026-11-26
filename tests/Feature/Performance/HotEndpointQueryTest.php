<?php

namespace Tests\Feature\Performance;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guardas de consultas para detectar regresiones N+1 en listados calientes.
 */
class HotEndpointQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_buses_endpoint_query_count_does_not_grow_per_bus(): void
    {
        $passenger = User::factory()->create();
        $route = Route::create([
            'name' => 'Ruta desempeño',
            'code' => 'PERF-1',
            'color' => '#2563EB',
            'is_active' => true,
        ]);

        foreach (range(1, 2) as $index) {
            $this->createActiveBus($index, $route);
        }

        $small = $this->countQueries(fn () => $this->actingAs($passenger)->getJson(route('api.buses.active'))->assertOk());

        foreach (range(3, 12) as $index) {
            $this->createActiveBus($index, $route);
        }

        $large = $this->countQueries(fn () => $this->actingAs($passenger)->getJson(route('api.buses.active'))->assertOk());

        $this->assertLessThan(30, $small);
        $this->assertLessThanOrEqual($small + 5, $large);
    }

    private function createActiveBus(int $index, Route $route): void
    {
        $driver = User::factory()->driver()->create();
        $bus = Bus::create([
            'license_plate' => 'PER'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
            'current_latitude' => 17.0732,
            'current_longitude' => -96.7266,
            'last_location_update' => now(),
        ]);
        $bus->routes()->attach($route->id, ['assigned_at' => now()]);
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();
        } finally {
            DB::disableQueryLog();
        }

        return count(DB::getQueryLog());
    }
}
