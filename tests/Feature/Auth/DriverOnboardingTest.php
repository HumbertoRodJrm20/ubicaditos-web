<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_driver_registration_form(): void
    {
        $this->get('/register-driver')->assertOk()->assertSee('Registro de conductores');
    }

    public function test_driver_registration_creates_a_pending_account_with_a_private_license_photo(): void
    {
        Storage::fake('local');

        $this->post('/register-driver', [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'phone' => '9511234567',
            'email' => 'ana.conductor@example.com',
            'license_number' => 'LIC-2026-001',
            'license_photo' => UploadedFile::fake()->create('licencia.jpg', 1024, 'image/jpeg'),
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertRedirect(route('login'));

        $driver = User::query()->where('email', 'ana.conductor@example.com')->firstOrFail();

        $this->assertSame('driver', $driver->role);
        $this->assertSame('pending', $driver->approval_status);
        $this->assertNotNull($driver->email_verified_at);
        $this->assertSame('LIC-2026-001', $driver->license_number);
        $this->assertStringStartsWith('license-photos/', $driver->license_photo_path);
        Storage::disk('local')->assertExists($driver->license_photo_path);
        Storage::disk('public')->assertMissing($driver->license_photo_path);
    }

    public function test_driver_registration_rejects_an_invalid_license_photo(): void
    {
        Storage::fake('local');

        $this->post('/register-driver', [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'phone' => '9511234567',
            'email' => 'ana.pdf@example.com',
            'license_number' => 'LIC-PDF',
            'license_photo' => UploadedFile::fake()->create('licencia.pdf', 100, 'application/pdf'),
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertSessionHasErrors('license_photo');
    }

    public function test_pending_and_rejected_drivers_are_blocked_with_a_clear_message(): void
    {
        $pending = User::factory()->driver()->create(['approval_status' => 'pending']);
        $rejected = User::factory()->driver()->create(['approval_status' => 'rejected']);

        $this->actingAs($pending)->get('/driver')->assertRedirect(route('driver.pending'));
        $this->get(route('driver.pending'))->assertSee('pendiente de aprobación');

        $this->actingAs($rejected)->get('/driver')->assertRedirect(route('driver.pending'));
        $this->get(route('driver.pending'))->assertSee('rechazada');
    }

    public function test_approved_drivers_can_access_the_driver_area(): void
    {
        $driver = User::factory()->driver()->create(['approval_status' => 'approved']);

        $this->actingAs($driver)->get('/driver')->assertOk();
    }

    public function test_existing_drivers_default_to_approved(): void
    {
        $driver = User::factory()->driver()->create();

        $this->assertSame('approved', $driver->fresh()->approval_status);
    }

    public function test_admin_can_approve_and_reject_a_driver(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create(['approval_status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.drivers.approve', $driver))->assertRedirect(route('admin.drivers'));
        $this->assertSame('approved', $driver->fresh()->approval_status);

        $this->actingAs($admin)->post(route('admin.drivers.reject', $driver))->assertRedirect(route('admin.drivers'));
        $this->assertSame('rejected', $driver->fresh()->approval_status);
    }
}
