<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
                $table->string('payment_method')->nullable(); // stripe, paypal, mobile_money, etc.
                $table->string('transaction_id')->nullable()->unique();
                $table->string('payment_reference')->nullable()->unique();
                $table->text('payment_data')->nullable(); // JSON pour stocker les données de paiement
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                
                $table->index('booking_id');
                $table->index('user_id');
                $table->index('status');
                $table->index('transaction_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

