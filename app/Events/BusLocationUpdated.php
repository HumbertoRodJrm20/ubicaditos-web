<?php

namespace App\Events;

use App\Models\Bus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite cada vez que un autobús reporta su posición GPS.
 * Se publica en el canal privado "buses" (cualquier usuario autenticado
 * puede suscribirse, ver routes/channels.php).
 *
 * Usa ShouldBroadcastNow para publicar de inmediato sin depender de un
 * worker de colas (posiciones que no deben esperar).
 */
class BusLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Bus $bus)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('buses')];
    }

    public function broadcastAs(): string
    {
        return 'bus.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->bus->id,
            'license_plate' => $this->bus->license_plate,
            'latitude' => (float) $this->bus->current_latitude,
            'longitude' => (float) $this->bus->current_longitude,
            'status' => $this->bus->status,
            'current_passengers' => $this->bus->current_passengers,
            'available_seats' => $this->bus->available_seats,
            'route_id' => $this->bus->routes->first()?->id,
            'last_update' => $this->bus->last_location_update?->toISOString(),
        ];
    }
}
