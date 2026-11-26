<?php

namespace Tests\Feature\Driver;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-014/RNF-08: el aviso de límites del GPS del navegador y el envío de
 * precisión deben estar presentes en el cliente del conductor.
 */
class DriverMapClientWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_map_warns_about_background_gps_and_sends_accuracy(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $bus = Bus::create([
            'license_plate' => 'WAKE-001',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $route = Route::create([
            'name' => 'Ruta wake lock',
            'code' => 'WAKE-R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $bus->routes()->attach($route->id, ['assigned_at' => now()]);

        $this->actingAs($driver)
            ->get(route('driver.map'))
            ->assertOk()
            ->assertSee('Mantén esta pestaña abierta', false)
            ->assertSee('no puede enviar GPS en segundo plano', false)
            ->assertSee('navigator.wakeLock.request', false)
            ->assertSee('payload.accuracy', false);
    }
}
