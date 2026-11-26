<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'passenger_id',
        'trip_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    // Relationships
    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * El conductor no se persiste aquí: se deriva del viaje calificado.
     */
    public function getDriverAttribute(): ?User
    {
        return $this->trip?->driver;
    }
}
