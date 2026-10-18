<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'license_plate',
        'model',
        'capacity',
        'status',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'driver_id',
        'is_active',
        'current_passengers',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'current_passengers' => 'integer',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'last_location_update' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Asientos libres (capacidad - ocupación actual), nunca negativo.
     */
    public function getAvailableSeatsAttribute(): int
    {
        return max(0, (int) $this->capacity - (int) ($this->current_passengers ?? 0));
    }

    /**
     * Porcentaje de ocupación actual (0-100).
     */
    public function getOccupancyPercentageAttribute(): int
    {
        if (!$this->capacity) {
            return 0;
        }

        return (int) round((($this->current_passengers ?? 0) / $this->capacity) * 100);
    }

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'bus_route')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function emergencyReports()
    {
        return $this->hasMany(EmergencyReport::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isInRoute()
    {
        return $this->status === 'active' && $this->current_latitude && $this->current_longitude;
    }


    public function updateLocation($latitude, $longitude)
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now(),
        ]);

        event(new \App\Events\BusLocationUpdated($this));
    }
}
