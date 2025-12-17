<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'bus_id',
        'route_id',
        'driver_id',
        'started_at',
        'completed_at',
        'total_passengers',
        'distance_km',
        'path_taken',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_passengers' => 'integer',
            'distance_km' => 'decimal:2',
            'path_taken' => 'array',
        ];
    }

    // Relationships
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    // Helper methods
    public function getDurationAttribute()
    {
        if (!$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInMinutes($this->completed_at);
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
