<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle Permission pour le système RBAC
 * 
 * Format des permissions : resource.action
 * Exemples :
 * - users.create
 * - users.update
 * - users.delete
 * - accommodations.approve
 * - accommodations.reject
 * - hosts.validate
 * - inspections.create
 */
class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'resource',
        'action',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Rôles ayant cette permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Scope pour les permissions actives
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour filtrer par ressource
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}

