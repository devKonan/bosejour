<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->foreignId('payment_id')->constrained()->onDelete('cascade');
                $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
                $table->decimal('booking_amount', 10, 2); // Montant total de la réservation
                $table->decimal('commission_rate', 5, 2); // Taux de commission (ex: 10.00 pour 10%)
                $table->decimal('commission_amount', 10, 2); // Montant de la commission
                $table->decimal('host_amount', 10, 2); // Montant versé à l'hôte (booking_amount - commission_amount)
                $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                
                $table->index('booking_id');
                $table->index('host_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};

