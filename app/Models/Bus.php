<?php

namespace App\Models;

use App\Events\BusLocationUpdated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    /** HU-014/HU-30: una posición deja de ser confiable después de 5 minutos. */
    public const LOCATION_FRESH_MINUTES = 5;

    protected $fillable = [
        'license_plate',
        'model',
        'capacity',
        'status',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'accuracy',
        'driver_id',
        'is_active',
        'current_passengers',
    ];

    /**
     * El mapa del pasajero consume estas tres banderas derivadas en todas sus
     * fuentes (Blade, polling y WebSocket) para no duplicar la regla de frescura.
     *
     * @var array<int, string>
     */
    protected $appends = ['fresh', 'fresh_until', 'sharing_passengers_count'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'current_passengers' => 'integer',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'last_location_update' => 'datetime',
            'accuracy' => 'integer',
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
        if (! $this->capacity) {
            return 0;
        }

        return (int) round((($this->current_passengers ?? 0) / $this->capacity) * 100);
    }

    public function getFreshAttribute(): bool
    {
        return $this->isLocationFresh();
    }

    public function getFreshUntilAttribute(): ?string
    {
        return $this->locationFreshUntil()?->toIso8601String();
    }

    public function getSharingPassengersCountAttribute(): int
    {
        if (array_key_exists('sharing_passengers_count', $this->attributes)) {
            return (int) $this->attributes['sharing_passengers_count'];
        }

        return (int) $this->locationShares()
            ->active()
            ->distinct()
            ->count('user_id');
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

    public function locationShares()
    {
        return $this->hasMany(LocationShare::class);
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

    /**
     * HU-014: la posición es fresca durante exactamente cinco minutos.
     */
    public function isLocationFresh(): bool
    {
        if ($this->last_location_update === null) {
            return false;
        }

        return $this->last_location_update
            ->greaterThanOrEqualTo(now()->subMinutes(self::LOCATION_FRESH_MINUTES));
    }

    public function locationFreshUntil(): ?Carbon
    {
        return $this->last_location_update?->copy()->addMinutes(self::LOCATION_FRESH_MINUTES);
    }

    public function updateLocation($latitude, $longitude, ?int $accuracy = null)
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now(),
            'accuracy' => $accuracy,
        ]);

        event(new BusLocationUpdated($this));
    }
}
