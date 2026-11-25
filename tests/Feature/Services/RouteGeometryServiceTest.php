<?php

namespace Tests\Feature\Services;

use App\Models\Route;
use App\Models\Stop;
use App\Services\RouteGeometryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteGeometryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function routeWithTwoStops(array $path = []): Route
    {
        $route = Route::create([
            'name' => 'Ruta Geometría',
            'code' => 'RG-1',
            'color' => '#3B82F6',
            'estimated_duration' => 45,
            'path_coordinates' => $path,
            'is_active' => true,
        ]);

        foreach ([17.060, 17.061] as $order => $latitude) {
            $stop = Stop::create([
                'name' => "Parada {$order}",
                'latitude' => $latitude,
                'longitude' => -96.7256 + $order,
                'is_active' => true,
            ]);
            $route->stops()->attach($stop->id, [
                'order' => $order,
                'estimated_time_from_previous' => $order === 0 ? 0 : 3,
                'is_frequent' => false,
            ]);
        }

        return $route;
    }

    private function osrmResponse(array $coordinates): array
    {
        return [
            'code' => 'Ok',
            'routes' => [[
                'geometry' => ['coordinates' => $coordinates],
            ]],
        ];
    }

    public function test_generates_and_persists_street_geometry(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response($this->osrmResponse([
                [-96.7256, 17.0600],
                [-96.7260, 17.0604],
                [-96.7264, 17.0610],
            ])),
        ]);

        $route = $this->routeWithTwoStops();

        $result = app(RouteGeometryService::class)->generate($route);

        $this->assertSame('generated', $result['status']);
        $this->assertSame(3, $result['points']);
        $this->assertSame(
            [['lat' => 17.06, 'lng' => -96.7256], ['lat' => 17.0604, 'lng' => -96.726], ['lat' => 17.061, 'lng' => -96.7264]],
            $route->fresh()->path_coordinates
        );
    }

    public function test_does_not_request_osrm_when_valid_geometry_already_exists(): void
    {
        Http::fake();

        $previous = [['lat' => 17.05, 'lng' => -96.70], ['lat' => 17.06, 'lng' => -96.71]];
        $route = $this->routeWithTwoStops($previous);

        $result = app(RouteGeometryService::class)->generate($route);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame($previous, $route->fresh()->path_coordinates);
        Http::assertNothingSent();
    }

    public function test_force_regenerates_even_when_geometry_exists(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response($this->osrmResponse([
                [-96.7256, 17.0600],
                [-96.7276, 17.0620],
            ])),
        ]);

        $route = $this->routeWithTwoStops([
            ['lat' => 17.05, 'lng' => -96.70],
            ['lat' => 17.06, 'lng' => -96.71],
        ]);

        $result = app(RouteGeometryService::class)->generate($route, true);

        $this->assertSame('generated', $result['status']);
        $this->assertSame(2, count($route->fresh()->path_coordinates));
        Http::assertSentCount(1);
    }

    public function test_invalid_geometry_never_replaces_previous_path(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response($this->osrmResponse([])),
        ]);

        $previous = [['lat' => 17.05, 'lng' => -96.70], ['lat' => 17.06, 'lng' => -96.71]];
        $route = $this->routeWithTwoStops($previous);

        $result = app(RouteGeometryService::class)->generate($route, true);

        $this->assertSame('failed', $result['status']);
        $this->assertSame($previous, $route->fresh()->path_coordinates);
    }

    public function test_route_with_fewer_than_two_stops_is_skipped_without_request(): void
    {
        Http::fake();

        $route = Route::create([
            'name' => 'Ruta Corta',
            'code' => 'RC-1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $result = app(RouteGeometryService::class)->generate($route, true);

        $this->assertSame('skipped', $result['status']);
        Http::assertNothingSent();
    }
}
