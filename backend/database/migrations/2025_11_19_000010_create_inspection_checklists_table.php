<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Checklist dynamique pour les inspections
     */
    public function up(): void
    {
        if (!Schema::hasTable('inspection_checklists')) {
            Schema::create('inspection_checklists', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Nom du critère
                $table->text('description')->nullable();
                $table->string('category')->nullable(); // Sécurité, Propreté, Confort, etc.
                $table->enum('type', ['boolean', 'rating', 'text', 'photo', 'video'])->default('boolean');
                $table->boolean('required')->default(false); // Critère obligatoire
                $table->integer('weight')->default(1); // Poids dans le calcul du score
                $table->integer('order')->default(0); // Ordre d'affichage
                $table->boolean('active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('category');
                $table->index('active');
                $table->index('order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_checklists');
    }
};

