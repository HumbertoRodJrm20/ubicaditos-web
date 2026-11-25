<?php

namespace Tests\Feature\Passenger;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Búsqueda de rutas del mapa del pasajero por nombre, calle y colonia.
 */
class RouteSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $passenger;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->passenger = User::factory()->create(['role' => 'passenger']);
    }

    private function route(string $name, string $code, array $attributes = []): Route
    {
        return Route::create(array_merge([
            'name' => $name,
            'code' => $code,
            'description' => 'Recorrido de prueba',
            'color' => '#3B82F6',
            'is_active' => true,
        ], $attributes));
    }

    private function attachStop(Route $route, string $name, string $address, float $lat, float $lng, int $order = 1): Stop
    {
        $stop = Stop::create([
            'name' => $name,
            'address' => $address,
            'latitude' => $lat,
            'longitude' => $lng,
            'is_active' => true,
        ]);

        $route->stops()->attach($stop->id, [
            'order' => $order,
            'estimated_time_from_previous' => 5,
            'is_frequent' => false,
        ]);

        return $stop;
    }

    public function test_search_finds_routes_by_street_and_colony_without_guessing_route_names(): void
    {
        $universidad = $this->route('Ruta 4 - Universidad', 'OAX-104');
        $this->attachStop(
            $universidad,
            'Parada Avenida Universidad',
            'Avenida Universidad, Oaxaca de Juárez',
            17.0729,
            -96.7398
        );

        $reforma = $this->route('Ruta 9 - Reforma', 'OAX-109');
        $this->attachStop(
            $reforma,
            'Parada Calle Nezahualcóyotl',
            'Calle Nezahualcóyotl, Colonia Reforma, Oaxaca',
            17.0744,
            -96.73
        );

        $response = $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Avenida Universidad']));

        $response->assertOk()
            ->assertJsonPath('results.0.id', $universidad->id)
            ->assertJsonPath('results.0.stop.name', 'Parada Avenida Universidad');

        $this->assertStringContainsString('Avenida Universidad', $response->json('results.0.reason'));
    }

    public function test_search_is_accent_insensitive_and_matches_route_names(): void
    {
        $route = $this->route('Ruta Centro - Zócalo', 'OAX-101');
        $this->attachStop($route, 'Zócalo', 'Plaza de la Constitución', 17.0654, -96.7256);

        $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'zocalo']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $route->id);

        $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Ruta Centro']))
            ->assertOk()
            ->assertJsonPath('results.0.match_type', 'route');
    }

    public function test_search_understands_street_abbreviations(): void
    {
        $route = $this->route('Ruta 4 - Universidad', 'OAX-104');
        $this->attachStop(
            $route,
            'Cinco Señores',
            'Av. Universidad, Oaxaca de Juárez',
            17.0729,
            -96.7398
        );

        $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Avenida Universidad']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $route->id)
            ->assertJsonPath('results.0.stop.name', 'Cinco Señores');
    }

    public function test_route_number_search_does_not_match_longer_numbers(): void
    {
        $four = $this->route('Ruta 4 - Universidad', 'OAX-104');
        $this->attachStop($four, 'Universidad', 'Avenida Universidad', 17.0729, -96.7398);

        $fourteen = $this->route('Ruta 14 - Jalatlaco', 'OAX-114');
        $this->attachStop($fourteen, 'Jalatlaco', 'Jalatlaco', 17.0710, -96.7200);

        $response = $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Ruta 4']));

        $response->assertOk();

        $ids = collect($response->json('results'))->pluck('id');

        $this->assertTrue($ids->contains($four->id));
        $this->assertFalse($ids->contains($fourteen->id));
    }

    public function test_search_uses_openstreetmap_when_the_place_is_not_in_the_local_data(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '17.0700',
                    'lon' => '-96.7200',
                    'display_name' => 'Parque El Llano, Oaxaca de Juárez, Oaxaca, México',
                ],
            ], 200),
        ]);

        $route = $this->route('Ruta Centro', 'OAX-101');
        $this->attachStop($route, 'Parada cercana', 'Calle Genérica', 17.0703, -96.7202);

        $response = $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Parque El Llano']));

        $response->assertOk()
            ->assertJsonPath('results.0.id', $route->id)
            ->assertJsonPath('results.0.match_type', 'location_stop');

        $this->assertStringContainsString('Parque El Llano', $response->json('results.0.reason'));
    }

    public function test_search_returns_distance_from_the_passenger_location(): void
    {
        $route = $this->route('Ruta Universidad', 'OAX-104');
        $this->attachStop(
            $route,
            'Parada Avenida Universidad',
            'Avenida Universidad, Oaxaca',
            17.0729,
            -96.7398
        );

        $response = $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', [
                'q' => 'Avenida Universidad',
                'lat' => 17.0700,
                'lng' => -96.7400,
            ]));

        $response->assertOk();

        $distance = $response->json('results.0.distance_km');

        $this->assertIsNumeric($distance);
        $this->assertLessThan(1, $distance);
    }

    public function test_search_ignores_inactive_routes(): void
    {
        $active = $this->route('Ruta Activa Universidad', 'OAX-104');
        $this->attachStop($active, 'Avenida Universidad', 'Avenida Universidad', 17.0729, -96.7398);

        $inactive = $this->route('Ruta Inactiva Universidad', 'OAX-999', ['is_active' => false]);
        $this->attachStop($inactive, 'Avenida Universidad', 'Avenida Universidad', 17.0729, -96.7398);

        $response = $this->actingAs($this->passenger)
            ->getJson(route('passenger.search', ['q' => 'Avenida Universidad']));

        $ids = collect($response->json('results'))->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }
}
