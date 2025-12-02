<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Historique des validations/rejets des hôtes
     */
    public function up(): void
    {
        if (!Schema::hasTable('host_validation_histories')) {
            Schema::create('host_validation_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
                $table->enum('action', ['validated', 'rejected', 'suspended', 'removed'])->default('validated');
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('comment')->nullable(); // Motif de validation/rejet
                $table->text('internal_notes')->nullable(); // Notes internes (admin/gérant uniquement)
                $table->json('validation_data')->nullable(); // Données de validation (checklist, etc.)
                $table->timestamps();
                
                $table->index(['host_id', 'created_at']);
                $table->index('action');
                $table->index('validated_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_validation_histories');
    }
};

