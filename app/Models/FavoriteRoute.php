<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteRoute extends Model
{
    public const MAX_FAVORITES = 5;

    public const MAX_LABEL_LENGTH = 40;

    protected $fillable = [
        'user_id',
        'route_id',
        'position',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
