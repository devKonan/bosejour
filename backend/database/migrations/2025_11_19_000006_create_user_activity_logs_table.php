<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Historique des actions utilisateurs pour audit
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('action'); // login, logout, update_profile, change_password, etc.
                $table->string('model_type')->nullable(); // App\Models\User, App\Models\Accommodation, etc.
                $table->unsignedBigInteger('model_id')->nullable();
                $table->json('old_values')->nullable(); // Valeurs avant modification
                $table->json('new_values')->nullable(); // Valeurs après modification
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'created_at']);
                $table->index(['model_type', 'model_id']);
                $table->index('action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};

