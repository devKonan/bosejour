<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Réponses aux critères de la checklist lors d'une inspection
     */
    public function up(): void
    {
        if (!Schema::hasTable('inspection_responses')) {
            Schema::create('inspection_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
                $table->foreignId('checklist_id')->constrained('inspection_checklists')->onDelete('cascade');
                $table->boolean('value_boolean')->nullable();
                $table->integer('value_rating')->nullable(); // 1-5
                $table->text('value_text')->nullable();
                $table->text('comment')->nullable();
                $table->json('media_files')->nullable(); // URLs des photos/vidéos
                $table->timestamps();
                
                $table->unique(['inspection_id', 'checklist_id']);
                $table->index('inspection_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_responses');
    }
};

