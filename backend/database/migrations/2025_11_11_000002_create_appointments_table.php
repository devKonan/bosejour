<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accommodation_id')->constrained('accommodations')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->dateTime('requested_at');
                $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
                $table->text('notes')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};


