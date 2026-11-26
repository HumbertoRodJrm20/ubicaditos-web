<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cobertura TDD del interruptor de verificación de correo por configuración.
 */
class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_passenger_can_navigate_when_verification_is_disabled(): void
    {
        config(['auth.email_verification_enabled' => false]);
        $passenger = User::factory()->unverified()->create();

        $this->actingAs($passenger)->get(route('passenger.dashboard'))
            ->assertOk();
    }

    public function test_registration_skips_the_verification_flow_when_disabled(): void
    {
        config(['auth.email_verification_enabled' => false]);
        Notification::fake();

        $this->post(route('register'), [
            'first_name' => 'Luis',
            'last_name' => 'Ramírez',
            'maternal_last_name' => 'Cruz',
            'phone' => '9511234567',
            'email' => 'sin.verificacion@example.com',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertRedirect(route('dashboard'));

        $passenger = User::query()->where('email', 'sin.verificacion@example.com')->firstOrFail();

        $this->assertNotNull($passenger->email_verified_at);
        Notification::assertNotSentTo($passenger, VerifyEmail::class);
    }

    public function test_verification_prompt_redirects_when_disabled(): void
    {
        config(['auth.email_verification_enabled' => false]);
        $passenger = User::factory()->unverified()->create();

        $this->actingAs($passenger)->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_verification_remains_enforced_when_enabled(): void
    {
        config(['auth.email_verification_enabled' => true]);
        $passenger = User::factory()->unverified()->create();

        $this->actingAs($passenger)->get(route('passenger.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
