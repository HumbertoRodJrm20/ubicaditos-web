<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'maternal_last_name',
        'email',
        'password',
        'role',
        'work_shift',
        'phone',
        'avatar',
        'is_active',
        'current_latitude',
        'current_longitude',
        'last_location_update',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'last_location_update' => 'datetime',
        ];
    }

    // Relationships
    public function drivingBus()
    {
        return $this->hasOne(Bus::class, 'driver_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'passenger_id');
    }

    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'driver_id');
    }

    public function favoriteStops()
    {
        return $this->belongsToMany(Stop::class, 'favorite_stops')
            ->withPivot('nickname')
            ->withTimestamps();
    }

    // Accessors
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name} {$this->maternal_last_name}");
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isDriver()
    {
        return $this->role === 'driver';
    }

    public function isPassenger()
    {
        return $this->role === 'passenger';
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratingsReceived()->avg('rating');
    }
}
