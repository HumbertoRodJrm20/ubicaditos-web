<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'bus_id',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    // Relationships
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    /**
     * Estado de lectura derivado: no se persiste para evitar redundancia.
     */
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    // Helper methods
    public function markAsRead()
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }

        return $this;
    }
}
