<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    public const CATEGORY_ROUTE = 'route';
    public const CATEGORY_BUS = 'bus';
    public const CATEGORY_SERVICE = 'service';
    public const CATEGORY_DRIVER = 'driver';
    public const CATEGORY_STOP = 'stop';
    public const CATEGORY_OTHER = 'other';

    /**
     * Categorías permitidas. La clave se guarda en la base de datos y el valor
     * es el texto que ve el usuario.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        self::CATEGORY_ROUTE => 'Problemas con una ruta',
        self::CATEGORY_BUS => 'Información incorrecta de un autobús',
        self::CATEGORY_SERVICE => 'Problemas con el servicio de transporte',
        self::CATEGORY_DRIVER => 'Problemas con un conductor',
        self::CATEGORY_STOP => 'Problemas con una parada',
        self::CATEGORY_OTHER => 'Otro',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Estados disponibles para un reporte.
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_IN_REVIEW => 'En revisión',
        self::STATUS_RESOLVED => 'Resuelto',
    ];

    public const MAX_DESCRIPTION_LENGTH = 1000;

    protected $fillable = [
        'user_id',
        'category',
        'description',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInReview(): bool
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
