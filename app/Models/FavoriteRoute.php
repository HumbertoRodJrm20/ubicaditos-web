<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteRoute extends Model
{
    protected $fillable = [
        'user_id',
        'route_id',
    ];

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
