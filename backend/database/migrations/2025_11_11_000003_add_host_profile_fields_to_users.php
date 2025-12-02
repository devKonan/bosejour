<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Informations personnelles
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('date_of_birth');
            }
            
            // Adresse complète
            if (!Schema::hasColumn('users', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
            
            // Identification
            if (!Schema::hasColumn('users', 'id_type')) {
                $table->string('id_type')->nullable()->after('country'); // CNI, Passeport, etc.
            }
            if (!Schema::hasColumn('users', 'id_number')) {
                $table->string('id_number')->nullable()->after('id_type');
            }
            if (!Schema::hasColumn('users', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('id_number'); // Chemin vers le document scanné (pour passeport ou document unique)
            }
            if (!Schema::hasColumn('users', 'id_document_recto_path')) {
                $table->string('id_document_recto_path')->nullable()->after('id_document_path'); // Recto pour CNI/Permis
            }
            if (!Schema::hasColumn('users', 'id_document_verso_path')) {
                $table->string('id_document_verso_path')->nullable()->after('id_document_recto_path'); // Verso pour CNI/Permis
            }
            
            // Documents
            if (!Schema::hasColumn('users', 'proof_of_address_path')) {
                $table->string('proof_of_address_path')->nullable()->after('id_document_verso_path');
            }
            if (!Schema::hasColumn('users', 'business_license_path')) {
                $table->string('business_license_path')->nullable()->after('proof_of_address_path'); // Si applicable
            }
            
            // Statut de vérification
            if (!Schema::hasColumn('users', 'profile_completed')) {
                $table->boolean('profile_completed')->default(false)->after('business_license_path');
            }
            if (!Schema::hasColumn('users', 'profile_verified')) {
                $table->boolean('profile_verified')->default(false)->after('profile_completed');
            }
            if (!Schema::hasColumn('users', 'profile_verified_at')) {
                $table->timestamp('profile_verified_at')->nullable()->after('profile_verified');
            }
            if (!Schema::hasColumn('users', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('profile_verified_at'); // Notes de l'admin
            }
        });

        // Définir la valeur par défaut pour country après la création de la colonne (si elle n'existe pas déjà avec une valeur par défaut)
        if (Schema::hasColumn('users', 'country')) {
            try {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `country` VARCHAR(255) DEFAULT 'Côte d''Ivoire'");
            } catch (\Exception $e) {
                // Ignorer si la colonne a déjà la bonne valeur par défaut
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'bio',
                'address_line1',
                'address_line2',
                'city',
                'postal_code',
                'country',
                'id_type',
                'id_number',
                'id_document_path',
                'id_document_recto_path',
                'id_document_verso_path',
                'proof_of_address_path',
                'business_license_path',
                'profile_completed',
                'profile_verified',
                'profile_verified_at',
                'verification_notes',
            ]);
        });
    }
};

