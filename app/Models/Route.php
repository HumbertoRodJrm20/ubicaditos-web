<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'estimated_duration',
        'path_coordinates',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'path_coordinates' => 'array',
            'estimated_duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function buses()
    {
        return $this->belongsToMany(Bus::class, 'bus_route')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function stops()
    {
        return $this->belongsToMany(Stop::class, 'route_stop')
            ->withPivot('order', 'estimated_time_from_previous')
            ->orderByPivot('order')
            ->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    // Helper methods
    public function getActiveBuses()
    {
        return $this->buses()->where('status', 'active')->get();
    }

    public function getOrderedStops()
    {
        return $this->stops()->orderBy('route_stop.order')->get();
    }
}
