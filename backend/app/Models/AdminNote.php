<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notes internes pour admin et gérant uniquement
 */
class AdminNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'noteable_type',
        'noteable_id',
        'created_by',
        'note',
        'visibility',
        'is_important',
    ];

    protected $casts = [
        'is_important' => 'boolean',
    ];

    /**
     * Relation polymorphique : peut être lié à User, Accommodation, etc.
     */
    public function noteable()
    {
        return $this->morphTo();
    }

    /**
     * Créateur de la note
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

