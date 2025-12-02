<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccommodationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'url',
        'is_primary',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }
}

