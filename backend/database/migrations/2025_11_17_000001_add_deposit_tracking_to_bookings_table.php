<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_price');
            }

            if (!Schema::hasColumn('bookings', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('deposit_amount');
            }

            if (!Schema::hasColumn('bookings', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('bookings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('deposit_paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('bookings', 'deposit_paid_at')) {
                $table->dropColumn('deposit_paid_at');
            }
            if (Schema::hasColumn('bookings', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('bookings', 'deposit_amount')) {
                $table->dropColumn('deposit_amount');
            }
        });
    }
};

