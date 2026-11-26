<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * HU-012: aviso in-app pendiente para un autobús y una parada concretos.
 * Perteneciente siempre al pasajero que lo creó.
 */
class BusStopSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'bus_id',
        'stop_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'bus_id' => 'integer',
            'stop_id' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function stop()
    {
        return $this->belongsTo(Stop::class);
    }
}
