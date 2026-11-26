<?php

namespace Tests\Feature\Passenger;

use App\Models\Bus;
use App\Models\Rating;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-02: calificar a un conductor exige un viaje completado con él.
 */
class RateDriverTripTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    private User $passenger;

    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $this->passenger = User::factory()->create(['role' => 'passenger']);
        $this->route = Route::create([
            'name' => 'Ruta Calificación',
            'code' => 'RAT-R1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
    }

    public function test_rating_a_driver_with_a_completed_trip_is_allowed_and_links_the_trip(): void
    {
        $trip = $this->makeTrip($this->driver, 'completed');

        $this->postRating($this->driver, ['rating' => 5, 'comment' => 'Excelente servicio'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ratings', [
            'passenger_id' => $this->passenger->id,
            'trip_id' => $trip->id,
            'rating' => 5,
        ]);
        $this->assertSame($this->driver->id, (int) Rating::first()->driver->id);
    }

    public function test_rating_with_an_explicit_completed_trip_is_allowed(): void
    {
        $trip = $this->makeTrip($this->driver, 'completed');

        $this->postRating($this->driver, ['trip_id' => $trip->id, 'rating' => 4])
            ->assertSessionHasNoErrors();

        $this->assertSame($trip->id, (int) Rating::first()->trip_id);
    }

    public function test_rating_for_an_active_trip_is_rejected(): void
    {
        $trip = $this->makeTrip($this->driver, 'in_progress');

        $this->postRating($this->driver, ['trip_id' => $trip->id, 'rating' => 5])
            ->assertSessionHasErrors('trip_id');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_rating_without_any_completed_trip_is_rejected(): void
    {
        $this->makeTrip($this->driver, 'in_progress');

        $this->postRating($this->driver, ['rating' => 5])
            ->assertSessionHasErrors('trip_id');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_rating_with_a_completed_trip_of_another_driver_is_rejected(): void
    {
        $otherDriver = User::factory()->create([
            'role' => 'driver',
            'work_shift' => 'morning',
            'is_active' => true,
        ]);
        $foreignTrip = $this->makeTrip($otherDriver, 'completed');

        $this->postRating($this->driver, ['trip_id' => $foreignTrip->id, 'rating' => 5])
            ->assertSessionHasErrors('trip_id');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_rating_a_non_driver_target_is_forbidden(): void
    {
        $trip = $this->makeTrip($this->driver, 'completed');

        $this->actingAs($this->passenger)
            ->post(route('passenger.rate', $this->passenger), [
                'trip_id' => $trip->id,
                'rating' => 5,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ratings', 0);
    }

    private function postRating(User $driver, array $payload)
    {
        return $this->actingAs($this->passenger)
            ->post(route('passenger.rate', $driver), $payload);
    }

    private function makeTrip(User $driver, string $status): Trip
    {
        $bus = Bus::create([
            'license_plate' => 'RAT-'.strtoupper(substr(sha1((string) $driver->id.$status), 0, 6)),
            'model' => 'Mercedes Sprinter',
            'capacity' => 30,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);

        return Trip::create([
            'driver_id' => $driver->id,
            'bus_id' => $bus->id,
            'route_id' => $this->route->id,
            'started_at' => now()->subHour(),
            'completed_at' => $status === 'completed' ? now() : null,
            'status' => $status,
        ]);
    }
}
