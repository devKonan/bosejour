<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

/**
 * Policy pour la gestion des inspections
 */
class InspectionPolicy
{
    /**
     * Déterminer si l'utilisateur peut voir n'importe quelle inspection
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inspections.read') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant', 'controleur']);
    }

    /**
     * Déterminer si l'utilisateur peut voir une inspection spécifique
     */
    public function view(User $user, Inspection $inspection): bool
    {
        // Le contrôleur peut voir ses propres inspections
        if ($inspection->inspector_id === $user->id) {
            return true;
        }

        return $user->hasPermission('inspections.read') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut créer une inspection
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('inspections.create') || 
               $user->hasRole('controleur');
    }

    /**
     * Déterminer si l'utilisateur peut mettre à jour une inspection
     */
    public function update(User $user, Inspection $inspection): bool
    {
        // Le contrôleur peut mettre à jour ses propres inspections en cours
        if ($inspection->inspector_id === $user->id && 
            in_array($inspection->status, ['scheduled', 'in_progress'])) {
            return true;
        }

        return $user->hasPermission('inspections.update') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut compléter une inspection
     */
    public function complete(User $user, Inspection $inspection): bool
    {
        // Seul le contrôleur assigné peut compléter
        return $inspection->inspector_id === $user->id && 
               $user->hasRole('controleur');
    }

    /**
     * Déterminer si l'utilisateur peut approuver une inspection
     */
    public function approve(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('inspections.approve') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut rejeter une inspection
     */
    public function reject(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('inspections.reject') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }
}

