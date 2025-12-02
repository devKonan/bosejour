<?php

namespace App\Policies;

use App\Models\Accommodation;
use App\Models\User;

/**
 * Policy pour la gestion des établissements
 */
class AccommodationPolicy
{
    /**
     * Déterminer si l'utilisateur peut voir n'importe quel établissement
     */
    public function viewAny(User $user): bool
    {
        return true; // Public
    }

    /**
     * Déterminer si l'utilisateur peut voir un établissement spécifique
     */
    public function view(User $user, Accommodation $accommodation): bool
    {
        // Public ou propriétaire ou admin
        return true;
    }

    /**
     * Déterminer si l'utilisateur peut créer un établissement
     */
    public function create(User $user): bool
    {
        return $user->hasRole('host') || 
               $user->hasPermission('accommodations.create');
    }

    /**
     * Déterminer si l'utilisateur peut mettre à jour un établissement
     */
    public function update(User $user, Accommodation $accommodation): bool
    {
        // Propriétaire ou admin
        return $accommodation->host_id === $user->id || 
               $user->hasPermission('accommodations.update') ||
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un établissement
     */
    public function delete(User $user, Accommodation $accommodation): bool
    {
        // Propriétaire ou admin
        return $accommodation->host_id === $user->id || 
               $user->hasPermission('accommodations.delete') ||
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut approuver un établissement
     */
    public function approve(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.approve') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut rejeter un établissement
     */
    public function reject(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.reject') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut retirer un établissement
     */
    public function remove(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.remove') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut désactiver un établissement
     */
    public function disable(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.disable') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }
}

