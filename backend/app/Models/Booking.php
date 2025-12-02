<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accommodation_id',
        'room_id',
        'check_in',
        'check_out',
        'guests',
        'total_price',
        'deposit_amount',
        'amount_paid',
        'status',
        'payment_status',
        'special_requests',
        'deposit_paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function remainingBalance(): float
    {
        return max((float) $this->total_price - (float) $this->amount_paid, 0);
    }

    public function hasPaidDeposit(): bool
    {
        if ($this->deposit_amount <= 0) {
            return false;
        }

        return (float) $this->amount_paid >= (float) $this->deposit_amount;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }
}

