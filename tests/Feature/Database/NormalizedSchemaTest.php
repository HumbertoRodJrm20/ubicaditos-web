<?php

namespace Tests\Feature\Database;

use App\Models\Bus;
use App\Models\Message;
use App\Models\Rating;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verifica que el esquema no guarde datos derivables de otras tablas.
 */
class NormalizedSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_read_state_has_no_redundant_boolean(): void
    {
        $this->assertFalse(Schema::hasColumn('messages', 'is_read'));

        $message = Message::create([
            'sender_id' => User::factory()->create()->id,
            'receiver_id' => User::factory()->create()->id,
            'message' => 'Mensaje de prueba',
        ]);

        $this->assertFalse($message->is_read);

        $message->markAsRead();

        $this->assertTrue($message->fresh()->is_read);
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_rating_driver_is_derived_from_trip_and_trip_is_required(): void
    {
        $this->assertFalse(Schema::hasColumn('ratings', 'driver_id'));

        $driver = User::factory()->create(['role' => 'driver']);
        $bus = Bus::create([
            'license_plate' => 'NORM123',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
            'driver_id' => $driver->id,
        ]);
        $route = Route::create([
            'name' => 'Ruta normalizada',
            'code' => 'NORM-1',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        $trip = Trip::create([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        $rating = Rating::create([
            'passenger_id' => User::factory()->create(['role' => 'passenger'])->id,
            'trip_id' => $trip->id,
            'rating' => 5,
        ]);

        $this->assertSame($driver->id, $rating->driver->id);
        $this->assertTrue($driver->ratingsReceived()->get()->contains(
            fn (Rating $item): bool => $item->id === $rating->id
        ));

        $this->expectException(QueryException::class);

        Rating::create([
            'passenger_id' => User::factory()->create(['role' => 'passenger'])->id,
            'rating' => 4,
        ]);
    }

    public function test_bus_route_assignments_are_unique(): void
    {
        $bus = Bus::create([
            'license_plate' => 'UNIQ123',
            'capacity' => 40,
            'status' => 'active',
            'is_active' => true,
        ]);
        $route = Route::create([
            'name' => 'Ruta única',
            'code' => 'UNIQ-1',
            'color' => '#10B981',
            'is_active' => true,
        ]);

        DB::table('bus_route')->insert([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('bus_route')->insert([
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'assigned_at' => now(),
        ]);
    }
}
