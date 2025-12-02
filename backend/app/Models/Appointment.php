<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'user_id',
        'requested_at',
        'status',
        'notes',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
        ];
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


