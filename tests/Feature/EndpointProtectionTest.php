<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que todos los endpoints privados estén protegidos por la
 * autenticación de sesión de Laravel Breeze y por los middleware de rol.
 */
class EndpointProtectionTest extends TestCase
{
    use RefreshDatabase;

    private array $privateWebEndpoints = [
        '/dashboard',
        '/profile',
        // Admin
        '/admin',
        '/admin/buses',
        '/admin/buses/create',
        '/admin/routes',
        '/admin/statistics',
        '/admin/reports',
        '/admin/drivers',
        '/admin/passengers',
        '/admin/users/create',
        // Driver
        '/driver',
        '/driver/map',
        // Passenger
        '/passenger',
        '/passenger/menu',
        '/passenger/routes',
        '/passenger/buses',
    ];

    private array $privateApiEndpoints = [
        '/api/buses/active',
        '/api/buses/1/location',
        '/api/routes/1/buses',
    ];

    public function test_unauthenticated_requests_are_redirected_to_login(): void
    {
        foreach ([...$this->privateWebEndpoints, ...$this->privateApiEndpoints] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_any_authenticated_user_can_consume_the_json_api(): void
    {
        $this->seedMinimalTransportData();

        foreach (['admin', 'driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->getJson('/api/buses/active')->assertOk();
            $this->actingAs($user)->getJson('/api/buses/1/location')->assertOk();
            $this->actingAs($user)->getJson('/api/routes/1/buses')->assertOk();
        }
    }

    public function test_driver_write_endpoints_require_driver_session(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->seedMinimalTransportData();
        $bus = Bus::first();
        $bus->update(['driver_id' => $driver->id]);

        // El pasajero no puede ejecutar acciones de conductor.
        $this->actingAs($passenger)
            ->post('/driver/update-location', ['latitude' => 17.06, 'longitude' => -96.72])
            ->assertForbidden();

        $this->actingAs($passenger)
            ->post('/driver/emergency', ['type' => 'other', 'description' => 'Prueba'])
            ->assertForbidden();

        // El conductor puede registrar su ubicación (response JSON).
        $this->actingAs($driver)
            ->post('/driver/update-location', ['latitude' => 17.06, 'longitude' => -96.72])
            ->assertOk();

        // Reportar una emergencia redirige de vuelta y crea el registro.
        $this->actingAs($driver)
            ->from('/driver')
            ->post('/driver/emergency', ['type' => 'other', 'description' => 'Falla menor'])
            ->assertRedirect('/driver');

        $this->assertDatabaseHas('emergency_reports', [
            'type' => 'other',
            'status' => 'pending',
        ]);
    }

    private function seedMinimalTransportData(): void
    {
        $route = Route::create([
            'name' => 'Ruta Test',
            'code' => 'T-001',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $bus = Bus::create([
            'license_plate' => 'ABC123',
            'model' => 'Toyota',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'current_latitude' => 17.0654,
            'current_longitude' => -96.7256,
        ]);

        $bus->routes()->attach($route->id, ['assigned_at' => now()]);
    }
}