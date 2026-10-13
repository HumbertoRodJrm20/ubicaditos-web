<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_role_areas(): void
    {
        foreach (['/dashboard', '/admin', '/driver', '/passenger'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_access_admin_area_but_not_driver_or_passenger_areas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/buses')->assertOk();
        $this->actingAs($admin)->get('/admin/routes')->assertOk();
        $this->actingAs($admin)->get('/driver')->assertForbidden();
        $this->actingAs($admin)->get('/passenger')->assertForbidden();
    }

    public function test_driver_can_access_driver_area_but_not_admin_or_passenger_areas(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $this->actingAs($driver)->get('/driver')->assertOk();
        $this->actingAs($driver)->get('/admin')->assertForbidden();
        $this->actingAs($driver)->get('/passenger')->assertForbidden();
    }

    public function test_passenger_can_access_passenger_area_but_not_admin_or_driver_areas(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)->get('/passenger')->assertOk();
        $this->actingAs($passenger)->get('/passenger/menu')->assertOk();
        $this->actingAs($passenger)->get('/admin')->assertForbidden();
        $this->actingAs($passenger)->get('/driver')->assertForbidden();
    }

    public function test_dashboard_redirects_to_the_panel_of_the_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $driver = User::factory()->create(['role' => 'driver']);
        $this->actingAs($driver)->get('/dashboard')->assertRedirect(route('driver.dashboard'));

        $passenger = User::factory()->create(['role' => 'passenger']);
        $this->actingAs($passenger)->get('/dashboard')->assertRedirect(route('passenger.dashboard'));
    }

    public function test_user_factory_creates_a_valid_passenger_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isPassenger());
        $this->assertNotEmpty($user->first_name);
        $this->assertNotEmpty($user->last_name);
        $this->assertNotEmpty($user->maternal_last_name);
        $this->assertMatchesRegularExpression('/^\d{10}$/', $user->phone);
    }
}