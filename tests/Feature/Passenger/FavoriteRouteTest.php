<?php

namespace Tests\Feature\Passenger;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rutas favoritas del pasajero, integradas con el menú y el perfil.
 */
class FavoriteRouteTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'passenger'], $attributes));
    }

    private function route(array $attributes = []): Route
    {
        return Route::create(array_merge([
            'name' => 'Ruta Universidad',
            'code' => 'R-' . fake()->unique()->numerify('###'),
            'description' => 'Recorrido de prueba',
            'color' => '#6b7bad',
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_cannot_manage_favorite_routes(): void
    {
        $route = $this->route();

        $this->post(route('passenger.favorite-routes.add', $route))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorite_routes', 0);
    }

    public function test_passenger_can_add_and_remove_a_favorite_route(): void
    {
        $user = $this->passenger();
        $route = $this->route();

        $this->actingAs($user)
            ->postJson(route('passenger.favorite-routes.add', $route))
            ->assertOk()
            ->assertJson(['success' => true, 'favorite' => true]);

        $this->assertDatabaseHas('favorite_routes', [
            'user_id' => $user->id,
            'route_id' => $route->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('passenger.favorite-routes.remove', $route))
            ->assertOk()
            ->assertJson(['success' => true, 'favorite' => false]);

        $this->assertDatabaseCount('favorite_routes', 0);
    }

    public function test_adding_the_same_favorite_route_twice_is_rejected(): void
    {
        $user = $this->passenger();
        $route = $this->route();

        $this->actingAs($user)
            ->postJson(route('passenger.favorite-routes.add', $route))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('passenger.favorite-routes.add', $route))
            ->assertStatus(400)
            ->assertJson(['error' => 'Esta ruta ya está en tus favoritos']);

        $this->assertDatabaseCount('favorite_routes', 1);
    }

    public function test_routes_page_exposes_the_user_favorites(): void
    {
        $user = $this->passenger();
        $favorite = $this->route(['name' => 'Ruta Favorita']);
        $other = $this->route(['name' => 'Ruta Normal']);

        $user->favoriteRoutes()->attach($favorite->id);

        $response = $this->actingAs($user)->get(route('passenger.routes'));

        $response->assertOk();

        $favoriteRouteIds = collect($response->viewData('favoriteRouteIds'));
        $this->assertTrue($favoriteRouteIds->contains($favorite->id));
        $this->assertFalse($favoriteRouteIds->contains($other->id));
    }

    public function test_menu_lists_favorite_routes_and_stops(): void
    {
        $user = $this->passenger();
        $route = $this->route(['name' => 'Ruta Centro']);
        $stop = Stop::create([
            'name' => 'Parada Santo Domingo',
            'address' => 'Centro',
            'latitude' => 17.0610000,
            'longitude' => -96.7260000,
            'is_active' => true,
        ]);

        $user->favoriteRoutes()->attach($route->id);
        $user->favoriteStops()->attach($stop->id, ['nickname' => 'Mi parada']);

        $this->actingAs($user)
            ->get(route('passenger.menu'))
            ->assertOk()
            ->assertSee('Ruta Centro')
            ->assertSee('Mi parada')
            ->assertSee('Rutas favoritas');
    }

    public function test_profile_shows_favorite_routes_real_metrics_and_lists(): void
    {
        $user = $this->passenger();
        $route = $this->route(['name' => 'Ruta Perfil']);
        $stop = Stop::create([
            'name' => 'Parada Perfil',
            'address' => 'Centro',
            'latitude' => 17.0610000,
            'longitude' => -96.7260000,
            'is_active' => true,
        ]);

        $user->favoriteRoutes()->attach($route->id);
        $user->favoriteStops()->attach($stop->id, ['nickname' => 'Parada Perfil']);

        // Dos calificaciones emitidas reales (no un número fijo en la vista).
        $driver = User::factory()->create(['role' => 'driver']);
        \App\Models\Rating::create([
            'passenger_id' => $user->id,
            'driver_id' => $driver->id,
            'rating' => 5,
            'comment' => 'Muy amable',
        ]);
        \App\Models\Rating::create([
            'passenger_id' => $user->id,
            'driver_id' => $driver->id,
            'rating' => 4,
            'comment' => 'Puntual',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Ruta Perfil')
            ->assertSee('Parada Perfil')
            ->assertSee('1 ruta favorita')
            ->assertSee('1 parada favorita')
            ->assertSee('2 calificaciones emitidas')
            ->assertDontSee('5 calificaciones emitidas');
    }

    public function test_only_passengers_can_manage_favorite_routes(): void
    {
        $route = $this->route();

        foreach (['driver', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->postJson(route('passenger.favorite-routes.add', $route))
                ->assertForbidden();
        }
    }
}
