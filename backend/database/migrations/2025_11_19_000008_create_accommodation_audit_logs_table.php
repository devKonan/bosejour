<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Audit complet des modifications d'établissements
     */
    public function up(): void
    {
        if (!Schema::hasTable('accommodation_audit_logs')) {
            Schema::create('accommodation_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accommodation_id')->constrained('accommodations')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action'); // created, updated, approved, rejected, removed, disabled, etc.
                $table->enum('status_before', ['pending', 'published', 'rejected', 'unavailable', 'renovation', 'removed', 'disabled'])->nullable();
                $table->enum('status_after', ['pending', 'published', 'rejected', 'unavailable', 'renovation', 'removed', 'disabled'])->nullable();
                $table->json('changes')->nullable(); // Détails des modifications
                $table->text('reason')->nullable(); // Motif du changement de statut
                $table->text('notes')->nullable(); // Notes internes
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                
                $table->index(['accommodation_id', 'created_at']);
                $table->index('action');
                $table->index('status_after');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_audit_logs');
    }
};

