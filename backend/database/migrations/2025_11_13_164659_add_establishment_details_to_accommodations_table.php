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
            // a) Informations sur l'établissement
            if (!Schema::hasColumn('accommodations', 'opening_year')) {
                $table->year('opening_year')->nullable()->after('type'); // Année d'ouverture
            }
            if (!Schema::hasColumn('accommodations', 'star_rating')) {
                $table->integer('star_rating')->nullable()->after('opening_year'); // Classement (étoile)
            }
            if (!Schema::hasColumn('accommodations', 'room_types')) {
                $table->json('room_types')->nullable()->after('bedrooms'); // Type de chambre
            }
            
            // Salle de conférence
            if (!Schema::hasColumn('accommodations', 'conference_rooms_count')) {
                $table->integer('conference_rooms_count')->default(0)->after('bathrooms'); // Nombre de salles
            }
            if (!Schema::hasColumn('accommodations', 'conference_capacity')) {
                $table->integer('conference_capacity')->default(0)->after('conference_rooms_count'); // Capacité
            }
            
            // Restaurant
            if (!Schema::hasColumn('accommodations', 'restaurant_capacity')) {
                $table->integer('restaurant_capacity')->default(0)->after('conference_capacity'); // Capacité restaurant
            }
            
            // Bar
            if (!Schema::hasColumn('accommodations', 'bar_capacity')) {
                $table->integer('bar_capacity')->default(0)->after('restaurant_capacity'); // Capacité bar
            }
            
            // b) Services et équipements supplémentaires
            if (!Schema::hasColumn('accommodations', 'shuttle_service')) {
                $table->boolean('shuttle_service')->default(false)->after('amenities'); // Navette
            }
            if (!Schema::hasColumn('accommodations', 'laundry')) {
                $table->boolean('laundry')->default(false)->after('shuttle_service'); // Buanderie
            }
            if (!Schema::hasColumn('accommodations', 'breakfast_price')) {
                $table->decimal('breakfast_price', 10, 2)->nullable()->after('laundry'); // Tarif petit déjeuner
            }
            if (!Schema::hasColumn('accommodations', 'reception_24h')) {
                $table->boolean('reception_24h')->default(false)->after('breakfast_price'); // Réception 24H/24
            }
            if (!Schema::hasColumn('accommodations', 'smoking_area')) {
                $table->boolean('smoking_area')->default(false)->after('reception_24h'); // Espace fumeur
            }
            if (!Schema::hasColumn('accommodations', 'pets_allowed')) {
                $table->boolean('pets_allowed')->default(false)->after('smoking_area'); // Animaux acceptés
            }
            if (!Schema::hasColumn('accommodations', 'other_amenities')) {
                $table->text('other_amenities')->nullable()->after('pets_allowed'); // Autres équipements
            }
            
            // c) Tarif et politique
            if (!Schema::hasColumn('accommodations', 'deposit_required')) {
                $table->boolean('deposit_required')->default(true)->after('other_amenities'); // Acompte requis
            }
            if (!Schema::hasColumn('accommodations', 'deposit_amount')) {
                $table->enum('deposit_amount', ['first_night', 'percentage', 'fixed'])->default('first_night')->after('deposit_required'); // Type d'acompte
            }
            if (!Schema::hasColumn('accommodations', 'cancellation_policy_hours')) {
                $table->integer('cancellation_policy_hours')->default(48)->after('deposit_amount'); // Politique d'annulation (heures)
            }
            if (!Schema::hasColumn('accommodations', 'payment_methods')) {
                $table->json('payment_methods')->nullable()->after('cancellation_policy_hours'); // Type de paiement
            }
            if (!Schema::hasColumn('accommodations', 'special_conditions')) {
                $table->text('special_conditions')->nullable()->after('payment_methods'); // Conditions particulières
            }
            if (!Schema::hasColumn('accommodations', 'breakfast_included')) {
                $table->boolean('breakfast_included')->default(false)->after('special_conditions'); // Petit déjeuner inclus
            }
            if (!Schema::hasColumn('accommodations', 'breakfast_included_persons')) {
                $table->integer('breakfast_included_persons')->default(0)->after('breakfast_included'); // Nombre de personnes pour petit déjeuner inclus
            }
            if (!Schema::hasColumn('accommodations', 'check_in_time')) {
                $table->time('check_in_time')->nullable()->after('breakfast_included_persons'); // Horaire Check in
            }
            if (!Schema::hasColumn('accommodations', 'check_out_time')) {
                $table->time('check_out_time')->nullable()->after('check_in_time'); // Horaire Check out
            }
            if (!Schema::hasColumn('accommodations', 'invoice_paid_before_hours')) {
                $table->integer('invoice_paid_before_hours')->default(48)->after('check_out_time'); // Facture soldée X heures avant
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn([
                'opening_year',
                'star_rating',
                'room_types',
                'conference_rooms_count',
                'conference_capacity',
                'restaurant_capacity',
                'bar_capacity',
                'shuttle_service',
                'laundry',
                'breakfast_price',
                'reception_24h',
                'smoking_area',
                'pets_allowed',
                'other_amenities',
                'deposit_required',
                'deposit_amount',
                'cancellation_policy_hours',
                'payment_methods',
                'special_conditions',
                'breakfast_included',
                'breakfast_included_persons',
                'check_in_time',
                'check_out_time',
                'invoice_paid_before_hours',
            ]);
        });
    }
};
