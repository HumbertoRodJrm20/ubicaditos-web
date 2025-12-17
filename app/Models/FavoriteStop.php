<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteStop extends Model
{
    protected $fillable = [
        'user_id',
        'stop_id',
        'nickname',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stop()
    {
        return $this->belongsTo(Stop::class);
    }
}
