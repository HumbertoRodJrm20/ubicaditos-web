<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\TemporaryPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cobertura TDD del alta administrativa con contraseña temporal y reset (HU-041/HU-042).
 */
class UserPasswordLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_driver_with_a_temporary_password_and_notifies_them(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'maternal_last_name' => 'Ruiz',
            'email' => 'carlos.conductor@example.com',
            'role' => 'driver',
            'work_shift' => 'morning',
            'phone' => '9511234567',
        ]);

        $response->assertRedirect(route('admin.drivers'));

        $driver = User::query()->where('email', 'carlos.conductor@example.com')->firstOrFail();

        $this->assertTrue($driver->must_change_password);
        $this->assertSame('approved', $driver->approval_status);
        $this->assertNotNull($driver->email_verified_at);
        $response->assertSessionMissing('password');

        Notification::assertSentTo($driver, TemporaryPasswordNotification::class);
    }

    public function test_user_with_must_change_password_is_redirected_to_profile(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('warning', 'Debes actualizar tu contraseña antes de continuar.');
    }

    public function test_updating_the_password_clears_the_must_change_flag(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NuevaClave123!', $user->password));
    }

    public function test_admin_can_trigger_a_password_reset_email_for_a_driver(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $this->actingAs($admin)
            ->post(route('admin.drivers.send-password-reset', $driver))
            ->assertRedirect(route('admin.drivers'))
            ->assertSessionHas('success', 'Se envió el correo de restablecimiento al conductor.');

        Notification::assertSentTo($driver, ResetPassword::class);
    }
}
