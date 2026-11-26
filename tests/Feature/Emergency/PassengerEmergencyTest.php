<?php

namespace Tests\Feature\Emergency;

use App\Models\Bus;
use App\Models\EmergencyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * RF-19: pasajeros y conductores pueden reportar emergencias con datos opcionales.
 */
class PassengerEmergencyTest extends TestCase
{
    use RefreshDatabase;

    private function passenger(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'passenger'], $attributes));
    }

    private function bus(string $plate = 'EMG001'): Bus
    {
        return Bus::create([
            'license_plate' => $plate,
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_submit_a_passenger_emergency(): void
    {
        $this->get(route('passenger.emergency.create'))->assertRedirect(route('login'));
        $this->post(route('passenger.emergency.store'), [
            'type' => 'medical',
            'description' => 'Intento sin sesión',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('emergency_reports', 0);
    }

    public function test_only_passengers_can_use_the_passenger_emergency_form(): void
    {
        foreach (['driver', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('passenger.emergency.create'))
                ->assertForbidden();

            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('passenger.emergency.store'), [
                    'type' => 'medical',
                    'description' => 'Intento con rol inválido',
                ])
                ->assertForbidden();
        }
    }

    public function test_passenger_can_submit_an_emergency_with_bus_and_coordinates(): void
    {
        $passenger = $this->passenger();
        $bus = $this->bus();

        $this->actingAs($passenger)
            ->post(route('passenger.emergency.store'), [
                'type' => 'medical',
                'description' => 'Pasajero con malestar a bordo',
                'bus_id' => $bus->id,
                'latitude' => 17.0612345,
                'longitude' => -96.7256789,
            ])
            ->assertRedirect(route('passenger.menu'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('emergency_reports', [
            'reporter_id' => $passenger->id,
            'bus_id' => $bus->id,
            'type' => 'medical',
            'description' => 'Pasajero con malestar a bordo',
            'status' => 'pending',
        ]);

        $report = EmergencyReport::first();
        $this->assertSame('17.0612345', (string) $report->latitude);
        $this->assertSame('-96.7256789', (string) $report->longitude);
        $this->assertSame('emergency', $bus->fresh()->status);
    }

    public function test_passenger_can_submit_an_emergency_without_bus_or_coordinates(): void
    {
        $passenger = $this->passenger();

        $this->actingAs($passenger)
            ->post(route('passenger.emergency.store'), [
                'type' => 'security',
                'description' => 'Reporte sin unidad ni ubicación',
            ])
            ->assertRedirect(route('passenger.menu'));

        $report = EmergencyReport::first();
        $this->assertNull($report->bus_id);
        $this->assertNull($report->latitude);
        $this->assertNull($report->longitude);
    }

    public function test_invalid_emergency_coordinates_and_bus_are_rejected(): void
    {
        $passenger = $this->passenger();
        $bus = $this->bus();

        $this->actingAs($passenger)
            ->from(route('passenger.emergency.create'))
            ->post(route('passenger.emergency.store'), [
                'type' => 'accident',
                'description' => 'Coordenadas fuera de rango',
                'latitude' => 91,
                'longitude' => -181,
            ])
            ->assertRedirect(route('passenger.emergency.create'))
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->actingAs($passenger)
            ->post(route('passenger.emergency.store'), [
                'type' => 'accident',
                'description' => 'Sólo latitud',
                'latitude' => 17.06,
            ])
            ->assertSessionHasErrors('longitude');

        $this->actingAs($passenger)
            ->post(route('passenger.emergency.store'), [
                'type' => 'accident',
                'description' => 'Unidad inexistente',
                'bus_id' => 9999,
            ])
            ->assertSessionHasErrors('bus_id');

        $this->assertDatabaseCount('emergency_reports', 0);
        $this->assertSame('active', $bus->fresh()->status);
    }

    public function test_emergency_reports_are_limited_to_five_per_hour_per_user(): void
    {
        $passenger = $this->passenger();
        $key = 'emergency-report:'.$passenger->id;
        RateLimiter::clear($key);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($passenger)
                ->postJson(route('passenger.emergency.store'), [
                    'type' => 'other',
                    'description' => 'Reporte '.$attempt,
                ])
                ->assertOk();
        }

        $this->actingAs($passenger)
            ->postJson(route('passenger.emergency.store'), [
                'type' => 'other',
                'description' => 'Reporte excedente',
            ])
            ->assertStatus(429);

        $this->assertDatabaseCount('emergency_reports', 5);

        RateLimiter::clear($key);
    }

    public function test_admin_sees_pending_emergencies_first_and_highlighted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);

        EmergencyReport::create([
            'reporter_id' => $driver->id,
            'type' => 'breakdown',
            'description' => 'Emergencia resuelta',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        EmergencyReport::create([
            'reporter_id' => $driver->id,
            'type' => 'medical',
            'description' => 'Emergencia pendiente',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.emergencies'))
            ->assertOk()
            ->assertSee('Requiere atención');

        $reports = $response->viewData('emergencies');
        $this->assertSame('Emergencia pendiente', $reports->first()->description);
    }

    public function test_driver_emergency_still_defaults_to_assigned_bus_and_coordinates(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $bus = Bus::create([
            'license_plate' => 'DRV001',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
            'current_latitude' => 17.0512345,
            'current_longitude' => -96.7112345,
        ]);
        $key = 'emergency-report:'.$driver->id;
        RateLimiter::clear($key);

        $this->actingAs($driver)
            ->post(route('driver.emergency'), [
                'type' => 'breakdown',
                'description' => 'Falla mecánica heredada',
            ])
            ->assertRedirect();

        $report = EmergencyReport::first();
        $this->assertSame($bus->id, $report->bus_id);
        $this->assertSame('17.0512345', (string) $report->latitude);
        $this->assertSame('-96.7112345', (string) $report->longitude);
        $this->assertSame('emergency', $bus->fresh()->status);

        RateLimiter::clear($key);
    }
}
