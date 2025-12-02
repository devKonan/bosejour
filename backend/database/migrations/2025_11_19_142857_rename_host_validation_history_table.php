<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Renommer la table host_validation_history en host_validation_histories
     * pour respecter la convention de nommage Laravel
     */
    public function up(): void
    {
        // Vérifier si l'ancienne table existe et la renommer
        if (Schema::hasTable('host_validation_history') && !Schema::hasTable('host_validation_histories')) {
            Schema::rename('host_validation_history', 'host_validation_histories');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Renommer en arrière si nécessaire
        if (Schema::hasTable('host_validation_histories') && !Schema::hasTable('host_validation_history')) {
            Schema::rename('host_validation_histories', 'host_validation_history');
        }
    }
};
