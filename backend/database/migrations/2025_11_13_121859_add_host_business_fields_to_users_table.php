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
        Schema::table('users', function (Blueprint $table) {
            // Champs pour les propriétaires (hôtes)
            if (!Schema::hasColumn('users', 'establishment_name')) {
                $table->string('establishment_name')->nullable()->after('name'); // Nom de l'établissement
            }
            if (!Schema::hasColumn('users', 'accommodation_type')) {
                $table->enum('accommodation_type', ['hotel', 'motel', 'guesthouse', 'apartment', 'apartment_hotel', 'residence'])->nullable()->after('establishment_name'); // Type d'hébergement
            }
            if (!Schema::hasColumn('users', 'phone_fixed')) {
                $table->string('phone_fixed')->nullable()->after('phone'); // Téléphone fixe
            }
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('phone_fixed'); // Numéro WhatsApp
            }
            if (!Schema::hasColumn('users', 'website')) {
                $table->string('website')->nullable()->after('whatsapp'); // Site internet
            }
            if (!Schema::hasColumn('users', 'facebook_page')) {
                $table->string('facebook_page')->nullable()->after('website'); // Page Facebook
            }
            if (!Schema::hasColumn('users', 'rccm')) {
                $table->string('rccm')->nullable()->after('facebook_page'); // RCCM
            }
            if (!Schema::hasColumn('users', 'cnps_number')) {
                $table->string('cnps_number')->nullable()->after('rccm'); // Numéro CNPS
            }
            if (!Schema::hasColumn('users', 'tax_account_number')) {
                $table->string('tax_account_number')->nullable()->after('cnps_number'); // Numéro compte contribuable
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'establishment_name',
                'accommodation_type',
                'phone_fixed',
                'whatsapp',
                'website',
                'facebook_page',
                'rccm',
                'cnps_number',
                'tax_account_number',
            ]);
        });
    }
};
