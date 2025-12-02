<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajout des champs pour la gestion avancée des utilisateurs
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'blocked', 'suspended'])->default('active')->after('role');
            }
            if (!Schema::hasColumn('users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'blocked_by')) {
                $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null')->after('blocked_at');
            }
            if (!Schema::hasColumn('users', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('blocked_by');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('block_reason');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0)->after('last_login_ip');
            }
            
            $table->index('status');
            $table->index('blocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['blocked_by']);
            $table->dropColumn([
                'status',
                'blocked_at',
                'blocked_by',
                'block_reason',
                'last_login_at',
                'last_login_ip',
                'login_count',
            ]);
        });
    }
};

