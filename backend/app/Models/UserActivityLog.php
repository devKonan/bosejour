<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour l'historique des activités utilisateurs
 */
class UserActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Utilisateur concerné
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Modèle concerné (polymorphique)
     */
    public function model()
    {
        return $this->morphTo();
    }
}

