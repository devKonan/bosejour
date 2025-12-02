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
        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('type'); // single, double, suite, etc.
                $table->text('description')->nullable();
                $table->text('description_en')->nullable();
                $table->integer('capacity'); // max guests
                $table->decimal('price_per_night', 10, 2);
                $table->json('amenities')->nullable();
                $table->integer('bedrooms')->default(1);
                $table->integer('bathrooms')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
