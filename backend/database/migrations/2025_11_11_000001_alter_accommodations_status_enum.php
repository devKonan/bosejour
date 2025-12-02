<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend enum values to include operational statuses
        DB::statement("ALTER TABLE accommodations MODIFY COLUMN status ENUM('pending','published','rejected','unavailable','renovation') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE accommodations MODIFY COLUMN status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending'");
    }
};


