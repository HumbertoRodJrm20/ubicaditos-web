<?php

namespace Tests\Feature\Passenger;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado de rutas del pasajero: rutas activas, recorrido completo y favoritos.
 */
class RouteListingTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(): User
    {
        return User::factory()->create(['role' => 'passenger']);
    }

    private function route(array $attributes = []): Route
    {
        return Route::create(array_merge([
            'name' => 'Ruta Centro',
            'code' => 'R-' . fake()->unique()->numerify('###'),
            'description' => 'Recorrido de prueba',
            'color' => '#6b7bad',
            'is_active' => true,
        ], $attributes));
    }

    private function stop(string $name): Stop
    {
        return Stop::create([
            'name' => $name,
            'address' => 'Oaxaca de Juárez',
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
            'is_active' => true,
        ]);
    }

    public function test_routes_page_lists_active_routes_with_their_complete_stop_list(): void
    {
        $route = $this->route(['name' => 'Ruta Completa']);
        $names = [
            'Parada Uno',
            'Parada Dos',
            'Parada Tres',
            'Parada Cuatro',
            'Parada Cinco',
        ];

        foreach ($names as $index => $name) {
            $route->stops()->attach($this->stop($name)->id, [
                'order' => $index + 1,
                'estimated_time_from_previous' => 4,
                'is_frequent' => false,
            ]);
        }

        $response = $this->actingAs($this->passenger())
            ->get(route('passenger.routes'));

        $response->assertOk()
            ->assertSee('Ruta Completa')
            ->assertSee('Ver las 5 paradas')
            ->assertSee('Favoritas');

        foreach ($names as $name) {
            $response->assertSee($name);
        }
    }

    public function test_routes_page_hides_inactive_routes(): void
    {
        $this->route(['name' => 'Ruta Disponible']);
        $this->route(['name' => 'Ruta Fuera de Servicio', 'is_active' => false]);

        $this->actingAs($this->passenger())
            ->get(route('passenger.routes'))
            ->assertOk()
            ->assertSee('Ruta Disponible')
            ->assertDontSee('Ruta Fuera de Servicio');
    }

    public function test_routes_page_marks_the_user_favorite_routes(): void
    {
        $user = $this->passenger();
        $favorite = $this->route(['name' => 'Ruta Preferida']);
        $this->route(['name' => 'Ruta Alterna']);

        $user->favoriteRoutes()->attach($favorite->id);

        $response = $this->actingAs($user)->get(route('passenger.routes'));

        $response->assertOk();

        $favoriteRouteIds = collect($response->viewData('favoriteRouteIds'));
        $this->assertTrue($favoriteRouteIds->contains($favorite->id));
    }
}
