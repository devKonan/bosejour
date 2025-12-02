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
        Schema::table('accommodations', function (Blueprint $table) {
            if (!Schema::hasColumn('accommodations', 'room_type_pricing')) {
                $table->json('room_type_pricing')->nullable()->after('room_types');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            if (Schema::hasColumn('accommodations', 'room_type_pricing')) {
                $table->dropColumn('room_type_pricing');
            }
        });
    }
};

