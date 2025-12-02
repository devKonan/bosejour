<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accommodation_id',
        'plan',
        'starts_at',
        'expires_at',
        'status',
        'amount_paid',
        'payment_method',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',
            'amount_paid' => 'decimal:2',
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

    public function isActive()
    {
        return $this->status === 'active' 
            && Carbon::now()->between($this->starts_at, $this->expires_at);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('expires_at', '<', Carbon::now());
    }

    public function getPlanPriority()
    {
        return match($this->plan) {
            'diamond' => 3,
            'gold' => 2,
            'free' => 1,
            default => 1,
        };
    }
}
