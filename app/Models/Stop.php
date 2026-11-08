<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function routes()
    {
        return $this->belongsToMany(Route::class, 'route_stop')
            ->withPivot('order', 'estimated_time_from_previous', 'is_frequent')
            ->withTimestamps();
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_stops')
            ->withPivot('nickname')
            ->withTimestamps();
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // Helper methods
    public function isLikedBy(?User $user): bool
    {
        return $user !== null
            && $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
