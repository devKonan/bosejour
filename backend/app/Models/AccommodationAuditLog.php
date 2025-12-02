<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit complet des modifications d'établissements
 */
class AccommodationAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'user_id',
        'action',
        'status_before',
        'status_after',
        'changes',
        'reason',
        'notes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Établissement concerné
     */
    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Utilisateur qui a effectué l'action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

