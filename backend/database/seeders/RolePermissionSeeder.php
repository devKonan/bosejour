<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

/**
 * Seeder pour créer les rôles et permissions de base
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les rôles
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrateur',
                'description' => 'Accès total, gestion des autres admins',
                'is_system' => true,
                'level' => 0,
                'active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Gestion utilisateurs, hôtes, biens, données',
                'is_system' => true,
                'level' => 1,
                'active' => true,
            ],
            [
                'name' => 'gerant',
                'display_name' => 'Gérant(e)',
                'description' => 'Suivi opérationnel quotidien',
                'is_system' => true,
                'level' => 2,
                'active' => true,
            ],
            [
                'name' => 'controleur',
                'display_name' => 'Contrôleur',
                'description' => 'Inspection + validation via checklist',
                'is_system' => true,
                'level' => 3,
                'active' => true,
            ],
            [
                'name' => 'host',
                'display_name' => 'Hôte',
                'description' => 'Hôte d\'hébergement',
                'is_system' => true,
                'level' => 4,
                'active' => true,
            ],
            [
                'name' => 'user',
                'display_name' => 'Utilisateur',
                'description' => 'Utilisateur standard',
                'is_system' => true,
                'level' => 5,
                'active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        // Créer les permissions
        $permissions = [
            // Dashboard
            ['name' => 'admin.dashboard.read', 'display_name' => 'Voir le dashboard admin', 'resource' => 'admin', 'action' => 'dashboard.read'],

            // Utilisateurs
            ['name' => 'users.read', 'display_name' => 'Voir les utilisateurs', 'resource' => 'users', 'action' => 'read'],
            ['name' => 'users.create', 'display_name' => 'Créer un utilisateur', 'resource' => 'users', 'action' => 'create'],
            ['name' => 'users.update', 'display_name' => 'Modifier un utilisateur', 'resource' => 'users', 'action' => 'update'],
            ['name' => 'users.delete', 'display_name' => 'Supprimer un utilisateur', 'resource' => 'users', 'action' => 'delete'],
            ['name' => 'users.block', 'display_name' => 'Bloquer un utilisateur', 'resource' => 'users', 'action' => 'block'],
            ['name' => 'users.unblock', 'display_name' => 'Débloquer un utilisateur', 'resource' => 'users', 'action' => 'unblock'],
            ['name' => 'users.manage_roles', 'display_name' => 'Gérer les rôles utilisateurs', 'resource' => 'users', 'action' => 'manage_roles'],

            // Hôtes
            ['name' => 'hosts.read', 'display_name' => 'Voir les hôtes', 'resource' => 'hosts', 'action' => 'read'],
            ['name' => 'hosts.validate', 'display_name' => 'Valider un hôte', 'resource' => 'hosts', 'action' => 'validate'],
            ['name' => 'hosts.reject', 'display_name' => 'Rejeter un hôte', 'resource' => 'hosts', 'action' => 'reject'],
            ['name' => 'hosts.suspend', 'display_name' => 'Suspendre un hôte', 'resource' => 'hosts', 'action' => 'suspend'],
            ['name' => 'hosts.remove_status', 'display_name' => 'Retirer le statut hôte', 'resource' => 'hosts', 'action' => 'remove_status'],

            // Établissements
            ['name' => 'accommodations.read', 'display_name' => 'Voir les établissements', 'resource' => 'accommodations', 'action' => 'read'],
            ['name' => 'accommodations.create', 'display_name' => 'Créer un établissement', 'resource' => 'accommodations', 'action' => 'create'],
            ['name' => 'accommodations.update', 'display_name' => 'Modifier un établissement', 'resource' => 'accommodations', 'action' => 'update'],
            ['name' => 'accommodations.delete', 'display_name' => 'Supprimer un établissement', 'resource' => 'accommodations', 'action' => 'delete'],
            ['name' => 'accommodations.approve', 'display_name' => 'Approuver un établissement', 'resource' => 'accommodations', 'action' => 'approve'],
            ['name' => 'accommodations.reject', 'display_name' => 'Rejeter un établissement', 'resource' => 'accommodations', 'action' => 'reject'],
            ['name' => 'accommodations.remove', 'display_name' => 'Retirer un établissement', 'resource' => 'accommodations', 'action' => 'remove'],
            ['name' => 'accommodations.disable', 'display_name' => 'Désactiver un établissement', 'resource' => 'accommodations', 'action' => 'disable'],

            // Inspections
            ['name' => 'inspections.read', 'display_name' => 'Voir les inspections', 'resource' => 'inspections', 'action' => 'read'],
            ['name' => 'inspections.create', 'display_name' => 'Créer une inspection', 'resource' => 'inspections', 'action' => 'create'],
            ['name' => 'inspections.update', 'display_name' => 'Modifier une inspection', 'resource' => 'inspections', 'action' => 'update'],
            ['name' => 'inspections.complete', 'display_name' => 'Compléter une inspection', 'resource' => 'inspections', 'action' => 'complete'],
            ['name' => 'inspections.approve', 'display_name' => 'Approuver une inspection', 'resource' => 'inspections', 'action' => 'approve'],
            ['name' => 'inspections.reject', 'display_name' => 'Rejeter une inspection', 'resource' => 'inspections', 'action' => 'reject'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assigner les permissions aux rôles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $gerant = Role::where('name', 'gerant')->first();
        $controleur = Role::where('name', 'controleur')->first();

        // Super Admin a toutes les permissions
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Admin a la plupart des permissions sauf gestion des super admins
        $admin->permissions()->sync(Permission::where('name', '!=', 'users.manage_roles')->pluck('id'));

        // Gérant a les permissions de lecture et validation
        $gerant->permissions()->sync(Permission::whereIn('name', [
            'admin.dashboard.read',
            'users.read',
            'hosts.read',
            'hosts.validate',
            'hosts.reject',
            'accommodations.read',
            'accommodations.approve',
            'accommodations.reject',
            'inspections.read',
        ])->pluck('id'));

        // Contrôleur a les permissions d'inspection
        $controleur->permissions()->sync(Permission::whereIn('name', [
            'inspections.read',
            'inspections.create',
            'inspections.update',
            'inspections.complete',
        ])->pluck('id'));

        $this->command->info('Rôles et permissions créés avec succès !');
    }
}

