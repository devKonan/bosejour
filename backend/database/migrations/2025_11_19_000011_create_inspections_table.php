<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Inspections sur site par les contrôleurs
     */
    public function up(): void
    {
        if (!Schema::hasTable('inspections')) {
            Schema::create('inspections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accommodation_id')->constrained('accommodations')->onDelete('cascade');
                $table->foreignId('inspector_id')->constrained('users')->onDelete('cascade'); // Contrôleur
                $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('score', 5, 2)->nullable(); // Score global (0-100)
                $table->enum('result', ['approved', 'rejected', 'pending_review'])->nullable();
                $table->text('observations')->nullable();
                $table->text('recommendations')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('signature_base64')->nullable(); // Signature numérique
                $table->boolean('offline_mode')->default(false);
                $table->json('location_data')->nullable(); // Coordonnées GPS de l'inspection
                $table->timestamps();
                
                $table->index(['accommodation_id', 'created_at']);
                $table->index(['inspector_id', 'created_at']);
                $table->index('status');
                $table->index('result');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};

