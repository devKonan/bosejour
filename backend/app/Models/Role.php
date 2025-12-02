<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle Role pour le système RBAC
 * 
 * Rôles disponibles :
 * - super_admin : Accès total, gestion des autres admins
 * - admin : Gestion utilisateurs, hôtes, biens, données
 * - gerant : Suivi opérationnel quotidien
 * - controleur : Inspection + validation via checklist
 * - host : Hôte d'hébergement
 * - user : Utilisateur standard
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
        'level',
        'active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Utilisateurs ayant ce rôle
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Permissions associées à ce rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Vérifier si le rôle a une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()
            ->where('name', $permissionName)
            ->where('active', true)
            ->exists();
    }

    /**
     * Scope pour les rôles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour les rôles système
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}

