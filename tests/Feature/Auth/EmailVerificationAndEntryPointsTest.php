<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cobertura TDD de los accesos por rol y la verificación de pasajeros.
 */
class EmailVerificationAndEntryPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_three_role_entry_points(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Pasajero')
            ->assertSee('Conductor')
            ->assertSee('Administrador')
            ->assertSee('role=passenger')
            ->assertSee('role=driver')
            ->assertSee('role=admin');
    }

    public function test_role_hint_never_elevates_the_account_role(): void
    {
        $passenger = User::factory()->create([
            'email' => 'pasajero@example.com',
            'password' => bcrypt('Clave123!'),
        ]);

        $this->post('/login?role=admin', [
            'email' => 'pasajero@example.com',
            'password' => 'Clave123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('passenger.dashboard'));
        $this->get('/admin')->assertForbidden();
    }

    public function test_passenger_registration_sends_a_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'first_name' => 'Luis',
            'last_name' => 'Ramírez',
            'maternal_last_name' => 'Cruz',
            'phone' => '9511234567',
            'email' => 'nuevo.pasajero@example.com',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertRedirect(route('verification.notice'));

        $passenger = User::query()->where('email', 'nuevo.pasajero@example.com')->firstOrFail();

        $this->assertNull($passenger->email_verified_at);
        Notification::assertSentTo($passenger, VerifyEmail::class);
    }

    public function test_unverified_passenger_is_redirected_to_the_verification_notice(): void
    {
        $passenger = User::factory()->unverified()->create();

        $this->actingAs($passenger)->get('/passenger')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_passenger_can_access_the_passenger_area(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->get('/passenger')->assertOk();
    }

    public function test_verification_resend_is_throttled(): void
    {
        Notification::fake();
        $passenger = User::factory()->unverified()->create();

        $this->actingAs($passenger);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post(route('verification.send'))->assertStatus(302);
        }

        $this->post(route('verification.send'))->assertStatus(429);
    }
}
