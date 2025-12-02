<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Historique des validations/rejets des hôtes
 */
class HostValidationHistory extends Model
{
    use HasFactory;

    /**
     * Nom de la table (explicite pour éviter les problèmes de pluralisation)
     */
    protected $table = 'host_validation_histories';

    protected $fillable = [
        'host_id',
        'action',
        'validated_by',
        'comment',
        'internal_notes',
        'validation_data',
    ];

    protected $casts = [
        'validation_data' => 'array',
    ];

    /**
     * Hôte concerné
     */
    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * Utilisateur qui a effectué la validation
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

