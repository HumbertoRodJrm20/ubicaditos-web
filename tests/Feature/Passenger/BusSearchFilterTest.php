<?php

namespace Tests\Feature\Passenger;

use App\Models\Bus;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado de autobuses del pasajero: búsqueda y filtrado/ordenamiento.
 */
class BusSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $passenger;
    private Route $centro;
    private Route $reforma;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passenger = User::factory()->create(['role' => 'passenger']);

        $this->centro = Route::create([
            'name' => 'Ruta Centro',
            'code' => 'CEN',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $this->reforma = Route::create([
            'name' => 'Ruta Reforma',
            'code' => 'REF',
            'color' => '#10B981',
            'is_active' => true,
        ]);
    }

    private function bus(array $attrs = []): Bus
    {
        return Bus::create(array_merge([
            'license_plate' => 'AAA111',
            'model' => 'Toyota Coaster',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ], $attrs));
    }

    public function test_listing_shows_active_buses_by_default(): void
    {
        $this->bus(['license_plate' => 'AAA111']);
        $this->bus(['license_plate' => 'BBB222', 'status' => 'maintenance']);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses'))
            ->assertOk()
            ->assertSee('AAA111')
            ->assertDontSee('BBB222');
    }

    public function test_listing_supports_search_by_plate_and_driver_name(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'first_name' => 'Bruno',
            'last_name' => 'Sánchez',
            'maternal_last_name' => 'Díaz',
        ]);
        $this->bus(['license_plate' => 'AAA111', 'driver_id' => $driver->id]);
        $this->bus(['license_plate' => 'ZZZ999']);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['q' => 'Bruno']))
            ->assertOk()
            ->assertSee('AAA111')
            ->assertDontSee('ZZZ999');

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['q' => 'zzz']))
            ->assertOk()
            ->assertSee('ZZZ999')
            ->assertDontSee('AAA111');
    }

    public function test_listing_supports_route_filter(): void
    {
        $busCentro = $this->bus(['license_plate' => 'AAA111']);
        $busCentro->routes()->attach($this->centro->id, ['assigned_at' => now()]);

        $busReforma = $this->bus(['license_plate' => 'BBB222']);
        $busReforma->routes()->attach($this->reforma->id, ['assigned_at' => now()]);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['route_id' => $this->centro->id]))
            ->assertOk()
            ->assertSee('AAA111')
            ->assertDontSee('BBB222');
    }

    public function test_listing_supports_status_filter(): void
    {
        $this->bus(['license_plate' => 'AAA111', 'status' => 'active']);
        $this->bus(['license_plate' => 'BBB222', 'status' => 'emergency']);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['status' => 'all']))
            ->assertOk()
            ->assertSee('AAA111')
            ->assertSee('BBB222');

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['status' => 'emergency']))
            ->assertOk()
            ->assertSee('BBB222')
            ->assertDontSee('AAA111');
    }

    public function test_listing_can_be_sorted_by_occupancy(): void
    {
        $this->bus(['license_plate' => 'AAA111', 'capacity' => 40, 'current_passengers' => 4]);
        $this->bus(['license_plate' => 'BBB222', 'capacity' => 40, 'current_passengers' => 38]);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses', ['sort' => 'occupancy']))
            ->assertOk()
            ->assertViewHas('buses', function ($buses) {
                return $buses->first()->license_plate === 'BBB222'
                    && $buses->last()->license_plate === 'AAA111';
            });
    }

    public function test_listing_reports_available_seats_per_bus(): void
    {
        $bus = $this->bus(['capacity' => 40, 'current_passengers' => 33]);

        $this->actingAs($this->passenger)
            ->get(route('passenger.buses'))
            ->assertOk()
            ->assertViewHas('busDetailsData', function ($details) use ($bus) {
                return $details->firstWhere('id', $bus->id)['available_seats'] === 7;
            });
    }
}
