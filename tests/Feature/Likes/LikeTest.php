<?php

namespace Tests\Feature\Likes;

use App\Models\Like;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sistema de likes sobre rutas y paradas.
 *
 * Un like es público (cualquiera ve el contador) pero personal (cada usuario
 * puede dar o quitar el suyo una sola vez por entidad).
 */
class LikeTest extends TestCase
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

    private function stop(array $attributes = []): Stop
    {
        return Stop::create(array_merge([
            'name' => 'Parada Zócalo',
            'address' => 'Oaxaca de Juárez',
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_cannot_toggle_a_like(): void
    {
        $route = $this->route();

        $this->post(route('likes.routes.toggle', $route))->assertRedirect(route('login'));

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_authenticated_user_can_like_a_route(): void
    {
        $user = $this->passenger();
        $route = $this->route();

        $this->actingAs($user)
            ->postJson(route('likes.routes.toggle', $route))
            ->assertOk()
            ->assertJson([
                'liked' => true,
                'count' => 1,
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Route::class,
            'likeable_id' => $route->id,
        ]);
    }

    public function test_liking_the_same_route_twice_removes_the_like(): void
    {
        $user = $this->passenger();
        $route = $this->route();

        $this->actingAs($user)
            ->postJson(route('likes.routes.toggle', $route))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->actingAs($user)
            ->postJson(route('likes.routes.toggle', $route))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_likes_are_personal_but_the_counter_is_shared(): void
    {
        $first = $this->passenger();
        $second = $this->passenger();
        $route = $this->route();

        $this->actingAs($first)->postJson(route('likes.routes.toggle', $route))->assertOk();
        $this->actingAs($second)->postJson(route('likes.routes.toggle', $route))->assertOk();

        $this->assertSame(2, Like::where('likeable_id', $route->id)->count());

        $this->actingAs($first)
            ->postJson(route('likes.routes.toggle', $route))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $second->id,
            'likeable_id' => $route->id,
        ]);
    }

    public function test_authenticated_user_can_like_a_stop(): void
    {
        $user = $this->passenger();
        $stop = $this->stop();

        $this->actingAs($user)
            ->postJson(route('likes.stops.toggle', $stop))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Stop::class,
            'likeable_id' => $stop->id,
        ]);
    }

    public function test_the_routes_page_exposes_like_counters_and_the_current_user_likes(): void
    {
        $user = $this->passenger();
        $route = $this->route();
        $otherRoute = $this->route(['name' => 'Ruta Mercado']);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Route::class,
            'likeable_id' => $route->id,
        ]);
        Like::create([
            'user_id' => $this->passenger()->id,
            'likeable_type' => Route::class,
            'likeable_id' => $route->id,
        ]);
        Like::create([
            'user_id' => $this->passenger()->id,
            'likeable_type' => Route::class,
            'likeable_id' => $otherRoute->id,
        ]);

        $response = $this->actingAs($user)->get(route('passenger.routes'));

        $response->assertOk();

        $routes = $response->viewData('routes');
        $this->assertSame(2, (int) $routes->firstWhere('id', $route->id)->likes_count);
        $this->assertSame(1, (int) $routes->firstWhere('id', $otherRoute->id)->likes_count);

        $likedRouteIds = collect($response->viewData('likedRouteIds'));
        $this->assertTrue($likedRouteIds->contains($route->id));
        $this->assertFalse($likedRouteIds->contains($otherRoute->id));
    }

    public function test_liking_an_unknown_route_returns_not_found(): void
    {
        $this->actingAs($this->passenger())
            ->postJson(route('likes.routes.toggle', 999))
            ->assertNotFound();
    }

    public function test_any_authenticated_role_can_like(): void
    {
        foreach (['admin', 'driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $stop = $this->stop(['name' => 'Parada ' . $role]);

            $this->actingAs($user)
                ->postJson(route('likes.stops.toggle', $stop))
                ->assertOk()
                ->assertJson(['liked' => true]);
        }

        $this->assertDatabaseCount('likes', 3);
    }
}
