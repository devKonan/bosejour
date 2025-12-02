<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Wave CI',
                'slug' => 'wave-ci',
                'icon' => '/images/payment-methods/wave-ci.png',
                'description' => 'Payer avec Wave CI',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Visa/Mastercard',
                'slug' => 'visa-mastercard',
                'icon' => '/images/payment-methods/visa-mastercard.png',
                'description' => 'Payer avec votre carte Visa ou Mastercard',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Orange CI',
                'slug' => 'orange-ci',
                'icon' => '/images/payment-methods/orange-ci.png',
                'description' => 'Payer avec Orange Money',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Djamo',
                'slug' => 'djamo',
                'icon' => '/images/payment-methods/djamo.png',
                'description' => 'Payer avec Djamo',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}

