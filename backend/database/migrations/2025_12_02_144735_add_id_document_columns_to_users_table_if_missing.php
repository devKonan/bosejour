<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vérifier et ajouter id_document_path si elle n'existe pas
            if (!Schema::hasColumn('users', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('id_number');
            }
            
            // Vérifier et ajouter id_document_recto_path si elle n'existe pas
            if (!Schema::hasColumn('users', 'id_document_recto_path')) {
                // Si id_document_path existe, ajouter après, sinon après id_number
                if (Schema::hasColumn('users', 'id_document_path')) {
                    $table->string('id_document_recto_path')->nullable()->after('id_document_path');
                } else {
                    $table->string('id_document_recto_path')->nullable()->after('id_number');
                }
            }
            
            // Vérifier et ajouter id_document_verso_path si elle n'existe pas
            if (!Schema::hasColumn('users', 'id_document_verso_path')) {
                // Si id_document_recto_path existe, ajouter après, sinon après id_document_path ou id_number
                if (Schema::hasColumn('users', 'id_document_recto_path')) {
                    $table->string('id_document_verso_path')->nullable()->after('id_document_recto_path');
                } elseif (Schema::hasColumn('users', 'id_document_path')) {
                    $table->string('id_document_verso_path')->nullable()->after('id_document_path');
                } else {
                    $table->string('id_document_verso_path')->nullable()->after('id_number');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_document_verso_path')) {
                $table->dropColumn('id_document_verso_path');
            }
            if (Schema::hasColumn('users', 'id_document_recto_path')) {
                $table->dropColumn('id_document_recto_path');
            }
            if (Schema::hasColumn('users', 'id_document_path')) {
                $table->dropColumn('id_document_path');
            }
        });
    }
};
