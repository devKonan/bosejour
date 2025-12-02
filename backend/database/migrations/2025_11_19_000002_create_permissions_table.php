<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table des permissions granulaires
     * Format: resource.action (ex: users.create, accommodations.approve)
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // users.create, accommodations.approve, etc.
                $table->string('display_name'); // Créer un utilisateur, Approuver un établissement
                $table->string('resource'); // users, accommodations, hosts, etc.
                $table->string('action'); // create, read, update, delete, approve, reject, etc.
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('resource');
                $table->index('action');
                $table->index('active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

