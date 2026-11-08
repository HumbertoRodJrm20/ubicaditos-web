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
