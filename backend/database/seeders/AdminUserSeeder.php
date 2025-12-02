<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder pour créer un utilisateur admin par défaut
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer ou mettre à jour l'utilisateur admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@monbeaupays.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Assigner le rôle admin via RBAC
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Optionnel : Créer aussi un super_admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@monbeaupays.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('password123'),
                'role' => 'admin', // Le rôle principal reste admin pour rétrocompatibilité
                'status' => 'active',
            ]
        );

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        $this->command->info('✅ Utilisateurs admin créés :');
        $this->command->info('   - admin@monbeaupays.com / password123 (Admin)');
        $this->command->info('   - superadmin@monbeaupays.com / password123 (Super Admin)');
        $this->command->warn('⚠️  Changez ces mots de passe en production !');
    }
}

