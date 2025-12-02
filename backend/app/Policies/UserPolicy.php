<?php

namespace App\Policies;

use App\Models\User;

/**
 * Policy pour la gestion des utilisateurs
 */
class UserPolicy
{
    /**
     * Déterminer si l'utilisateur peut voir n'importe quel utilisateur
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.read') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut voir un utilisateur spécifique
     */
    public function view(User $user, User $model): bool
    {
        // Un utilisateur peut toujours voir son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.read') || 
               $user->hasAnyRole(['super_admin', 'admin', 'gerant']);
    }

    /**
     * Déterminer si l'utilisateur peut créer un utilisateur
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.create') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut mettre à jour un utilisateur
     */
    public function update(User $user, User $model): bool
    {
        // Un utilisateur peut toujours mettre à jour son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.update') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un utilisateur
     */
    public function delete(User $user, User $model): bool
    {
        // Ne peut pas se supprimer soi-même
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.delete') || 
               $user->hasRole('super_admin');
    }

    /**
     * Déterminer si l'utilisateur peut bloquer un utilisateur
     */
    public function block(User $user, User $model): bool
    {
        // Ne peut pas se bloquer soi-même
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.block') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut débloquer un utilisateur
     */
    public function unblock(User $user, User $model): bool
    {
        return $user->hasPermission('users.unblock') || 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Déterminer si l'utilisateur peut modifier les rôles d'un utilisateur
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Ne peut pas modifier ses propres rôles
        if ($user->id === $model->id) {
            return false;
        }

        // Seul le super_admin peut modifier les rôles
        return $user->hasRole('super_admin');
    }
}

