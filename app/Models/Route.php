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
            ->withPivot('order', 'estimated_time_from_previous', 'is_frequent')
            ->orderByPivot('order')
            ->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_routes')
            ->withTimestamps();
    }

    // Helper methods
    public function isLikedBy(?User $user): bool
    {
        return $user !== null
            && $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user !== null
            && $this->favoritedByUsers()->where('user_id', $user->id)->exists();
    }

    public function getActiveBuses()
    {
        return $this->buses()->where('status', 'active')->get();
    }

    public function getOrderedStops(bool $includeFrequent = true)
    {
        $query = $this->stops()->orderBy('route_stop.order');

        if (! $includeFrequent) {
            $query->wherePivot('is_frequent', false);
        }

        return $query->get();
    }
}
