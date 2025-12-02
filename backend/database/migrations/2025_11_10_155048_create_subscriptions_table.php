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
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('accommodation_id')->nullable()->constrained()->onDelete('cascade');
                $table->enum('plan', ['free', 'gold', 'diamond'])->default('free');
                $table->date('starts_at');
                $table->date('expires_at');
                $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('status');
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
