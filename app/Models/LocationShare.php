<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocationShare extends Model
{
    /**
     * Minutos que vive un enlace de ubicación.
     */
    public const TTL_MINUTES = 30;

    protected $fillable = [
        'user_id',
        'token',
        'latitude',
        'longitude',
        'accuracy',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Crea una compartición nueva y revoca cualquier enlace activo previo.
     *
     * @param  array{latitude: float|string, longitude: float|string, accuracy?: int|null}  $coordinates
     */
    public static function startFor(User $user, array $coordinates): self
    {
        $user->locationShares()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);

        return $user->locationShares()->create([
            'token' => Str::random(48),
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'accuracy' => $coordinates['accuracy'] ?? null,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);
    }

    /**
     * @param  array{latitude: float|string, longitude: float|string, accuracy?: int|null}  $coordinates
     */
    public function updatePosition(array $coordinates): bool
    {
        return $this->update([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'accuracy' => $coordinates['accuracy'] ?? $this->accuracy,
        ]);
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => now()]);
    }

    public function publicUrl(): string
    {
        return route('share.show', $this->token);
    }

    /**
     * Datos mínimos que se exponen en el enlace público: nunca el nombre,
     * el correo ni el identificador del pasajero.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'url' => $this->publicUrl(),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy,
            'active' => $this->isActive(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
