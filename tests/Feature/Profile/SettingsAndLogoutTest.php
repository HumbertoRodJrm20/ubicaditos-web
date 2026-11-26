<?php

namespace Tests\Feature\Profile;

use App\Models\Bus;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura TDD de preferencias de cuenta y cierre de sesión seguro.
 */
class SettingsAndLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_persists_locale_and_in_app_notification_preferences(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->patch(route('profile.update'), [
            'first_name' => 'María',
            'last_name' => 'Sánchez',
            'maternal_last_name' => 'López',
            'email' => $passenger->email,
            'phone' => '9511234567',
            'locale' => 'en',
            'notification_preferences' => [
                'emergency' => true,
                'incident' => false,
                'location_share' => true,
            ],
        ])->assertRedirect(route('profile.edit'));

        $passenger->refresh();

        $this->assertSame('en', $passenger->locale);
        $this->assertSame([
            'emergency' => true,
            'incident' => false,
            'location_share' => true,
        ], $passenger->notification_preferences);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->patch(route('profile.update'), [
            'first_name' => 'María',
            'last_name' => 'Sánchez',
            'maternal_last_name' => 'López',
            'email' => $passenger->email,
            'phone' => '9511234567',
            'locale' => 'fr',
        ])->assertSessionHasErrors('locale');
    }

    public function test_user_locale_is_applied_by_middleware(): void
    {
        $passenger = User::factory()->create(['locale' => 'en']);

        $this->actingAs($passenger)->get('/passenger/menu')->assertOk();

        $this->assertSame('en', $this->app->getLocale());
    }

    public function test_logout_with_an_active_driver_trip_ends_the_trip_and_frees_the_bus(): void
    {
        $route = Route::create([
            'name' => 'Ruta logout',
            'code' => 'LO-1',
            'color' => '#2563EB',
            'is_active' => true,
        ]);
        $driver = User::factory()->driver()->create();
        $bus = Bus::create([
            'license_plate' => 'OUT123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'started_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $this->actingAs($driver)->post(route('logout'))->assertRedirect('/');

        $this->assertNotSame('in_progress', $trip->fresh()->status);
        $this->assertNull($bus->fresh()->driver_id);
    }

    public function test_passenger_logout_does_not_touch_driver_trips(): void
    {
        $route = Route::create([
            'name' => 'Ruta pasajero logout',
            'code' => 'LO-2',
            'color' => '#16A34A',
            'is_active' => true,
        ]);
        $driver = User::factory()->driver()->create();
        $bus = Bus::create([
            'license_plate' => 'OUT456',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'started_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $passenger = User::factory()->create();

        $this->actingAs($passenger)->post(route('logout'))->assertRedirect('/');

        $this->assertSame('in_progress', $trip->fresh()->status);
        $this->assertSame($driver->id, $bus->fresh()->driver_id);
    }

    public function test_logout_control_asks_for_confirmation(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('¿Cerrar sesión?')
            ->assertSee('confirm-logout');
    }
}
