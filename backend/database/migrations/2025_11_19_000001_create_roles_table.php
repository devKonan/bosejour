<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table centrale pour les rôles du système RBAC
     * Synchronisée entre Laravel et NestJS
     */
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // super_admin, admin, gerant, controleur, host, user
                $table->string('display_name'); // Super Administrateur, Administrateur, etc.
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false); // Rôles système non supprimables
                $table->integer('level')->default(0); // Niveau hiérarchique (0 = super admin, 10 = user)
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('name');
                $table->index('active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

