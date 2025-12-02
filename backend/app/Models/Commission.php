<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_id',
        'host_id',
        'booking_amount',
        'commission_rate',
        'commission_amount',
        'host_amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'host_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}

