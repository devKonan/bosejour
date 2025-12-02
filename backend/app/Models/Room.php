<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'name',
        'type',
        'description',
        'description_en',
        'capacity',
        'price_per_night',
        'amenities',
        'bedrooms',
        'bathrooms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_per_night' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function availabilities()
    {
        return $this->hasMany(RoomAvailability::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailableForDates($checkIn, $checkOut)
    {
        $dates = $this->getDatesBetween($checkIn, $checkOut);
        
        return $this->availabilities()
            ->whereIn('date', $dates)
            ->where('status', 'available')
            ->count() === count($dates);
    }

    private function getDatesBetween($start, $end)
    {
        $dates = [];
        $current = strtotime($start);
        $end = strtotime($end);

        while ($current < $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
