<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accommodations')) {
            Schema::create('accommodations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->unique();
                $table->enum('type', ['hotel', 'lodge', 'guesthouse', 'apartment']);
                $table->text('description');
                $table->text('description_en')->nullable();
                $table->string('address');
                $table->string('city');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('price_per_night', 10, 2);
                $table->integer('max_guests');
                $table->integer('bedrooms');
                $table->integer('bathrooms');
                $table->json('amenities')->nullable();
                $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
                $table->boolean('is_featured')->default(false);
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('total_reviews')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};

