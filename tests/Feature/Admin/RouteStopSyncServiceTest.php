<?php

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\Stop;
use App\Services\RouteStopSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Escritura de la secuencia de paradas de una ruta (route_stop).
 */
class RouteStopSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private RouteStopSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RouteStopSyncService();
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
            'is_active' => true,
        ]);
    }

    /**
     * @param array<int, array{0: Stop, 1: bool, 2: int}> $items [parada, es_frecuente, minutos], en orden
     */
    private function attachSequence(Route $route, array $items): void
    {
        foreach ($items as $order => [$stop, $frequent, $minutes]) {
            $route->stops()->attach($stop->id, [
                'order' => $order,
                'estimated_time_from_previous' => $minutes,
                'is_frequent' => $frequent,
            ]);
        }
    }

    private function sequence(Route $route): string
    {
        return DB::table('route_stop')
            ->where('route_id', $route->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => ($row->is_frequent ? 'F' : 'M') . $row->stop_id)
            ->implode(' ');
    }

    private function minutes(Route $route, Stop $stop): ?int
    {
        $value = DB::table('route_stop')
            ->where('route_id', $route->id)
            ->where('stop_id', $stop->id)
            ->value('estimated_time_from_previous');

        return $value === null ? null : (int) $value;
    }

    public function test_sync_writes_the_combined_sequence_with_anchored_frequent_stops(): void
    {
        $route = $this->makeRoute();
        $s1 = $this->makeStop('S1', 17.00);
        $s2 = $this->makeStop('S2', 17.01);
        $s3 = $this->makeStop('S3', 17.02);
        $f1 = $this->makeStop('F1', 17.005);
        $f2 = $this->makeStop('F2', 17.015);

        $this->attachSequence($route, [[$s1, false, 0], [$f1, true, 2], [$s2, false, 9], [$f2, true, 3]]);

        $result = $this->service->syncMainStops($route, [$s2->id, $s1->id, $s3->id]);

        $this->assertSame(
            "M{$s2->id} F{$f2->id} M{$s1->id} F{$f1->id} M{$s3->id}",
            $this->sequence($route)
        );
        $this->assertSame(2, $this->minutes($route, $f1));
        $this->assertSame(3, $this->minutes($route, $f2));
        $this->assertSame([$s3->id], $result['added']);
        $this->assertSame([], $result['removed']);
    }

    public function test_main_stop_minutes_are_kept_recalculated_or_overridden(): void
    {
        $route = $this->makeRoute();
        $s1 = $this->makeStop('S1', 17.00);
        $s2 = $this->makeStop('S2', 17.01);
        $s3 = $this->makeStop('S3', 17.02);

        $this->attachSequence($route, [[$s1, false, 0], [$s2, false, 9]]);

        $this->service->syncMainStops($route, [$s1->id, $s2->id, $s3->id], [$s3->id => 12]);

        $this->assertSame(0, $this->minutes($route, $s1));
        $this->assertSame(9, $this->minutes($route, $s2));   // misma predecesora: se conserva
        $this->assertSame(12, $this->minutes($route, $s3));  // valor indicado por el administrador

        // S2 pasa a ser la primera y S1 cambia de predecesora (≈ 1.1 km → 4 min).
        $this->service->syncMainStops($route, [$s2->id, $s1->id]);

        $this->assertSame(0, $this->minutes($route, $s2));
        $this->assertSame(4, $this->minutes($route, $s1));
    }

    public function test_removed_main_stop_is_detached_only_from_this_route(): void
    {
        $routeA = $this->makeRoute('RA-1');
        $routeB = $this->makeRoute('RB-2');
        $s1 = $this->makeStop('S1', 17.00);
        $s2 = $this->makeStop('S2', 17.01);

        $this->attachSequence($routeA, [[$s1, false, 0], [$s2, false, 4]]);
        $this->attachSequence($routeB, [[$s1, false, 0]]);

        $result = $this->service->syncMainStops($routeA, [$s2->id]);

        $this->assertSame("M{$s2->id}", $this->sequence($routeA));
        $this->assertSame("M{$s1->id}", $this->sequence($routeB));
        $this->assertSame([$s1->id], $result['removed']);
        $this->assertDatabaseHas('stops', ['id' => $s1->id]);
    }

    public function test_unknown_stop_aborts_without_changing_the_route(): void
    {
        $route = $this->makeRoute();
        $s1 = $this->makeStop('S1', 17.00);
        $f1 = $this->makeStop('F1', 17.005);

        $this->attachSequence($route, [[$s1, false, 0], [$f1, true, 2]]);
        $before = $this->sequence($route);

        try {
            $this->service->syncMainStops($route, [$s1->id, 999999]);
            $this->fail('Debió lanzar InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            // esperado
        }

        $this->assertSame($before, $this->sequence($route));
    }

    public function test_reports_when_frequent_stops_need_review(): void
    {
        $withFrequent = $this->makeRoute('RF-1');
        $withoutFrequent = $this->makeRoute('RF-2');
        $s1 = $this->makeStop('S1', 17.00);
        $s2 = $this->makeStop('S2', 17.01);
        $f1 = $this->makeStop('F1', 17.005);

        $this->attachSequence($withFrequent, [[$s1, false, 0], [$f1, true, 2], [$s2, false, 4]]);
        $this->attachSequence($withoutFrequent, [[$s1, false, 0], [$s2, false, 4]]);

        $unchanged = $this->service->syncMainStops($withFrequent, [$s1->id, $s2->id]);
        $this->assertFalse($unchanged['main_sequence_changed']);
        $this->assertFalse($unchanged['frequent_review_needed']);

        $reordered = $this->service->syncMainStops($withFrequent, [$s2->id, $s1->id]);
        $this->assertTrue($reordered['main_sequence_changed']);
        $this->assertTrue($reordered['frequent_review_needed']);

        $plain = $this->service->syncMainStops($withoutFrequent, [$s2->id, $s1->id]);
        $this->assertTrue($plain['main_sequence_changed']);
        $this->assertFalse($plain['frequent_review_needed']);
    }

    public function test_promoting_a_frequent_stop_does_not_duplicate_its_row(): void
    {
        $route = $this->makeRoute();
        $s1 = $this->makeStop('S1', 17.00);
        $f1 = $this->makeStop('F1', 17.005);

        $this->attachSequence($route, [[$s1, false, 0], [$f1, true, 3]]);

        $this->service->syncMainStops($route, [$s1->id, $f1->id]);

        $this->assertSame("M{$s1->id} M{$f1->id}", $this->sequence($route));
        $this->assertSame(2, DB::table('route_stop')->where('route_id', $route->id)->count());
    }
}
