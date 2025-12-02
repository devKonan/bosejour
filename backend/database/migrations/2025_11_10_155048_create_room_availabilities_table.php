<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('room_availabilities')) {
            Schema::create('room_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
                $table->decimal('price_override', 10, 2)->nullable(); // Override base price for specific dates
                $table->timestamps();
                
                $table->unique(['room_id', 'date']);
                $table->index('date');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_availabilities');
    }
};
