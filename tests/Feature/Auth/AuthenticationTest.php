<?php

namespace Tests\Feature\Auth;

use App\Models\LocationShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['sensitive_data' => 'should-be-flushed'])
            ->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $response->assertSessionMissing('sensitive_data');
    }

    public function test_admin_can_logout(): void
    {
        $this->assertRoleCanLogout('admin');
    }

    public function test_driver_can_logout(): void
    {
        $this->assertRoleCanLogout('driver');
    }

    public function test_passenger_can_logout(): void
    {
        $this->assertRoleCanLogout('passenger');
    }

    public function test_each_role_page_shows_the_shared_logout_form(): void
    {
        $pages = [
            'admin' => '/admin',
            'driver' => '/driver',
            'passenger' => '/passenger',
        ];

        foreach ($pages as $role => $page) {
            $user = User::factory()->create([
                'role' => $role,
                'email_verified_at' => now(),
            ]);

            $response = $this->actingAs($user)->get($page);

            $response->assertOk();
            $response->assertSee('action="'.route('logout').'"', false);
            $response->assertSee('Cerrar sesión');

            $this->app['auth']->forgetGuards();
        }
    }

    private function assertRoleCanLogout(string $role): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logout_revokes_active_location_shares(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);
        $share = LocationShare::startFor($user, [
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotNull($share->fresh()->revoked_at);
        $this->assertFalse($share->fresh()->isActive());
    }
}
