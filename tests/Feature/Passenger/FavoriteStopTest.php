<?php

namespace Tests\Feature\Passenger;

use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_uses_the_stop_name_when_no_nickname_is_sent(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);
        $stop = Stop::create([
            'name' => 'Parada Zócalo',
            'address' => 'Oaxaca de Juárez',
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('passenger.favorites.add', $stop), [])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('favorite_stops', [
            'user_id' => $user->id,
            'stop_id' => $stop->id,
            'nickname' => 'Parada Zócalo',
        ]);
    }

    public function test_favorite_keeps_an_explicit_nickname(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);
        $stop = Stop::create([
            'name' => 'Parada Zócalo',
            'address' => 'Oaxaca de Juárez',
            'latitude' => 17.0600000,
            'longitude' => -96.7250000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('passenger.favorites.add', $stop), [
                'nickname' => 'Mi parada',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('favorite_stops', [
            'user_id' => $user->id,
            'stop_id' => $stop->id,
            'nickname' => 'Mi parada',
        ]);
    }
}
