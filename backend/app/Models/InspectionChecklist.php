<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Checklist dynamique pour les inspections
 */
class InspectionChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'type',
        'required',
        'weight',
        'order',
        'active',
        'created_by',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'weight' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Créateur du critère
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Réponses à ce critère
     */
    public function responses()
    {
        return $this->hasMany(InspectionResponse::class, 'checklist_id');
    }

    /**
     * Scope pour les critères actifs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour une catégorie
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

