<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const LOCALES = ['es', 'en'];

    /** @use HasFactory<UserFactory> */
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
        'email_verified_at',
        'password',
        'role',
        'work_shift',
        'phone',
        'avatar',
        'is_active',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'approval_status',
        'license_number',
        'license_photo_path',
        'must_change_password',
        'locale',
        'notification_preferences',
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
            'must_change_password' => 'boolean',
            'notification_preferences' => 'array',
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

    public function favoriteRoutes()
    {
        return $this->belongsToMany(Route::class, 'favorite_routes')
            ->withPivot(['position', 'label'])
            ->orderBy('favorite_routes.position')
            ->orderBy('favorite_routes.id')
            ->withTimestamps();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function locationShares()
    {
        return $this->hasMany(LocationShare::class);
    }

    public function busStopSubscriptions()
    {
        return $this->hasMany(BusStopSubscription::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    // Accessors
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name} {$this->maternal_last_name}");
    }

    // Helper methods
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isRejectedApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function requiresPasswordChange(): bool
    {
        return (bool) $this->must_change_password;
    }

    public function notificationPreference(string $key, bool $default = true): bool
    {
        return (bool) ($this->notification_preferences[$key] ?? $default);
    }

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
