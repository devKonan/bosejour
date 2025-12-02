<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Notes internes pour admin et gérant uniquement
     */
    public function up(): void
    {
        if (!Schema::hasTable('admin_notes')) {
            Schema::create('admin_notes', function (Blueprint $table) {
                $table->id();
                $table->morphs('noteable'); // Polymorphique : User, Accommodation, etc.
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->text('note');
                $table->enum('visibility', ['admin', 'gerant', 'admin_gerant'])->default('admin_gerant');
                $table->boolean('is_important')->default(false);
                $table->timestamps();
                $table->softDeletes();
                
                // Note: morphs() crée automatiquement l'index sur noteable_type et noteable_id
                $table->index('created_by');
                $table->index('visibility');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};

