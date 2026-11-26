<?php

namespace Tests\Feature\Passenger;

use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-027: máximo de cinco favoritos, orden persistido y etiqueta opcional.
 */
class FavoriteRoutePreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'passenger'], $attributes));
    }

    private function route(string $name, string $code): Route
    {
        return Route::create([
            'name' => $name,
            'code' => $code,
            'description' => 'Recorrido de prueba',
            'color' => '#6b7bad',
            'is_active' => true,
        ]);
    }

    public function test_passenger_cannot_add_a_sixth_favorite_route(): void
    {
        $user = $this->passenger();

        foreach (range(1, 5) as $index) {
            $route = $this->route('Ruta '.$index, 'FAV-'.$index);
            $this->actingAs($user)
                ->postJson(route('passenger.favorite-routes.add', $route))
                ->assertOk();
        }

        $sixth = $this->route('Ruta sexta', 'FAV-006');
        $this->actingAs($user)
            ->postJson(route('passenger.favorite-routes.add', $sixth))
            ->assertStatus(422)
            ->assertJsonPath('error', 'Solo puedes guardar 5 rutas favoritas.');

        $this->assertDatabaseCount('favorite_routes', 5);
        $this->assertDatabaseMissing('favorite_routes', [
            'user_id' => $user->id,
            'route_id' => $sixth->id,
        ]);
    }

    public function test_favorite_positions_are_assigned_compacted_and_reordered(): void
    {
        $user = $this->passenger();
        $routes = [];

        foreach (range(1, 3) as $index) {
            $routes[$index] = $this->route('Ruta '.$index, 'ORD-'.$index);
            $this->actingAs($user)
                ->postJson(route('passenger.favorite-routes.add', $routes[$index]))
                ->assertOk();
        }

        $this->assertSame(
            [$routes[1]->id => 1, $routes[2]->id => 2, $routes[3]->id => 3],
            $user->favoriteRoutes()->pluck('favorite_routes.position', 'routes.id')->map(fn ($position) => (int) $position)->all()
        );

        $this->actingAs($user)
            ->patchJson(route('passenger.favorite-routes.update', $routes[3]), [
                'position' => 1,
                'label' => 'Mi favorita',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(
            [$routes[3]->id => 1, $routes[1]->id => 2, $routes[2]->id => 3],
            $user->favoriteRoutes()->pluck('favorite_routes.position', 'routes.id')->map(fn ($position) => (int) $position)->all()
        );
        $this->assertSame('Mi favorita', $user->favoriteRoutes()->find($routes[3]->id)->pivot->label);

        $this->actingAs($user)
            ->deleteJson(route('passenger.favorite-routes.remove', $routes[1]))
            ->assertOk();

        $this->assertSame(
            [$routes[3]->id => 1, $routes[2]->id => 2],
            $user->favoriteRoutes()->pluck('favorite_routes.position', 'routes.id')->map(fn ($position) => (int) $position)->all()
        );
    }

    public function test_menu_uses_saved_order_and_label(): void
    {
        $user = $this->passenger();
        $first = $this->route('Ruta primera', 'LBL-1');
        $second = $this->route('Ruta segunda', 'LBL-2');

        $user->favoriteRoutes()->attach($first->id, ['position' => 1, 'label' => 'Mi ruta diaria']);
        $user->favoriteRoutes()->attach($second->id, ['position' => 2]);

        $response = $this->actingAs($user)
            ->get(route('passenger.menu'))
            ->assertOk()
            ->assertSee('Mi ruta diaria')
            ->assertSee('Rutas favoritas');

        $orderedIds = $user->favoriteRoutes()->pluck('routes.id')->all();
        $this->assertSame([$first->id, $second->id], $orderedIds);
        $response->assertSeeInOrder(['Mi ruta diaria', $second->name]);
    }

    public function test_user_cannot_reorder_or_label_another_users_favorite(): void
    {
        $owner = $this->passenger();
        $other = $this->passenger();
        $route = $this->route('Ruta ajena', 'OTH-001');
        $owner->favoriteRoutes()->attach($route->id, ['position' => 1]);

        $this->actingAs($other)
            ->patchJson(route('passenger.favorite-routes.update', $route), [
                'position' => 1,
                'label' => 'Robada',
            ])
            ->assertNotFound();

        $this->assertSame(1, (int) $owner->favoriteRoutes()->first()->pivot->position);
        $this->assertNull($owner->favoriteRoutes()->first()->pivot->label);
    }

    public function test_invalid_position_and_label_are_rejected(): void
    {
        $user = $this->passenger();
        $route = $this->route('Ruta válida', 'VAL-001');
        $user->favoriteRoutes()->attach($route->id, ['position' => 1]);

        $this->actingAs($user)
            ->patchJson(route('passenger.favorite-routes.update', $route), [
                'position' => 9,
                'label' => str_repeat('a', 41),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['position', 'label']);

        $this->assertSame(1, (int) $route->fresh()->favoritedByUsers()->first()->pivot->position);
    }
}
