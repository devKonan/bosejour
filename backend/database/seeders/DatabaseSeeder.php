<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Review;
use App\Models\AccommodationImage;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@monbeaupays.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Hosts
        $host1 = User::create([
            'name' => 'Jean Kouassi',
            'email' => 'host1@monbeaupays.com',
            'password' => Hash::make('password'),
            'role' => 'host',
            'phone' => '+225 07 12 34 56 78',
            'email_verified_at' => now(),
        ]);

        $host2 = User::create([
            'name' => 'Marie Diabaté',
            'email' => 'host2@monbeaupays.com',
            'password' => Hash::make('password'),
            'role' => 'host',
            'phone' => '+225 05 98 76 54 32',
            'email_verified_at' => now(),
        ]);

        // Create Users
        $user1 = User::create([
            'name' => 'Pierre Yapi',
            'email' => 'user1@monbeaupays.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'phone' => '+225 01 23 45 67 89',
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'Sophie Traoré',
            'email' => 'user2@monbeaupays.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'phone' => '+225 09 87 65 43 21',
            'email_verified_at' => now(),
        ]);

        // Accommodations
        $cities = ['Abidjan', 'Yamoussoukro', 'San-Pédro', 'Bouaké', 'Korhogo'];
        $types = ['hotel', 'lodge', 'guesthouse', 'apartment'];

        $accommodations = [
            [
                'host_id' => $host1->id,
                'name' => 'Hôtel Ivoire Premium',
                'type' => 'hotel',
                'description' => 'Un hôtel de luxe au cœur d\'Abidjan avec vue sur la lagune. Équipements modernes et service exceptionnel.',
                'description_en' => 'A luxury hotel in the heart of Abidjan with lagoon views. Modern amenities and exceptional service.',
                'address' => 'Boulevard de la République, Plateau',
                'city' => 'Abidjan',
                'latitude' => 5.316667,
                'longitude' => -4.033333,
                'price_per_night' => 45000,
                'max_guests' => 4,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'amenities' => ['wifi', 'pool', 'gym', 'restaurant', 'spa', 'parking'],
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'host_id' => $host1->id,
                'name' => 'Lodge Baobab',
                'type' => 'lodge',
                'description' => 'Lodge écologique au bord de la forêt, parfait pour se reconnecter avec la nature.',
                'description_en' => 'Eco-lodge on the edge of the forest, perfect for reconnecting with nature.',
                'address' => 'Route de Yamoussoukro, Km 15',
                'city' => 'Yamoussoukro',
                'latitude' => 6.827623,
                'longitude' => -5.289343,
                'price_per_night' => 25000,
                'max_guests' => 6,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'amenities' => ['wifi', 'garden', 'restaurant', 'parking'],
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'host_id' => $host2->id,
                'name' => 'Villa Cocody',
                'type' => 'apartment',
                'description' => 'Appartement moderne et spacieux dans le quartier résidentiel de Cocody.',
                'description_en' => 'Modern and spacious apartment in the residential area of Cocody.',
                'address' => 'Rue des Jardins, Cocody',
                'city' => 'Abidjan',
                'latitude' => 5.350000,
                'longitude' => -4.016667,
                'price_per_night' => 30000,
                'max_guests' => 4,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'amenities' => ['wifi', 'kitchen', 'parking', 'tv'],
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'host_id' => $host2->id,
                'name' => 'Maison d\'Hôtes San-Pédro',
                'type' => 'guesthouse',
                'description' => 'Charmante maison d\'hôtes près de la plage, idéale pour les vacances en famille.',
                'description_en' => 'Charming guesthouse near the beach, ideal for family vacations.',
                'address' => 'Avenue de la Plage',
                'city' => 'San-Pédro',
                'latitude' => 4.748889,
                'longitude' => -6.636111,
                'price_per_night' => 20000,
                'max_guests' => 5,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'amenities' => ['wifi', 'beach_access', 'restaurant', 'parking'],
                'status' => 'published',
                'is_featured' => false,
            ],
        ];

        foreach ($accommodations as $accData) {
            $accommodation = Accommodation::create(array_merge($accData, [
                'slug' => \Str::slug($accData['name']),
            ]));

            // Add images
            AccommodationImage::create([
                'accommodation_id' => $accommodation->id,
                'url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
                'is_primary' => true,
                'order' => 1,
            ]);

            AccommodationImage::create([
                'accommodation_id' => $accommodation->id,
                'url' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800',
                'is_primary' => false,
                'order' => 2,
            ]);

            AccommodationImage::create([
                'accommodation_id' => $accommodation->id,
                'url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800',
                'is_primary' => false,
                'order' => 3,
            ]);

            // Create rooms for each accommodation
            $roomTypes = ['single', 'double', 'suite', 'deluxe'];
            $numRooms = rand(3, 8);

            for ($i = 1; $i <= $numRooms; $i++) {
                $roomType = $roomTypes[array_rand($roomTypes)];
                $room = Room::create([
                    'accommodation_id' => $accommodation->id,
                    'name' => "Chambre {$i} - " . ucfirst($roomType),
                    'type' => $roomType,
                    'description' => "Chambre confortable de type {$roomType}",
                    'description_en' => "Comfortable {$roomType} room",
                    'capacity' => $roomType === 'single' ? 1 : ($roomType === 'suite' ? 4 : 2),
                    'price_per_night' => $accommodation->price_per_night + (rand(-5000, 10000)),
                    'amenities' => ['wifi', 'tv', 'ac', 'minibar'],
                    'bedrooms' => 1,
                    'bathrooms' => 1,
                    'is_active' => true,
                ]);

                // Create availability for next 90 days
                $startDate = Carbon::now();
                $endDate = Carbon::now()->addDays(90);

                $current = $startDate->copy();
                while ($current->lte($endDate)) {
                    // Randomly set some dates as occupied or maintenance
                    $status = 'available';
                    if (rand(1, 10) <= 2) {
                        $status = rand(1, 2) === 1 ? 'occupied' : 'maintenance';
                    }

                    RoomAvailability::create([
                        'room_id' => $room->id,
                        'date' => $current->format('Y-m-d'),
                        'status' => $status,
                        'price_override' => rand(1, 10) <= 2 ? $room->price_per_night + rand(2000, 5000) : null,
                    ]);

                    $current->addDay();
                }
            }

            // Create subscriptions for some accommodations
            if (rand(1, 2) === 1) {
                $plan = ['gold', 'diamond'][array_rand(['gold', 'diamond'])];
                Subscription::create([
                    'user_id' => $accommodation->host_id,
                    'accommodation_id' => $accommodation->id,
                    'plan' => $plan,
                    'starts_at' => Carbon::now()->subDays(rand(1, 30)),
                    'expires_at' => Carbon::now()->addMonths(rand(1, 6)),
                    'status' => 'active',
                    'amount_paid' => $plan === 'gold' ? 50000 : 100000,
                    'payment_method' => 'placeholder',
                    'transaction_id' => 'TXN-' . uniqid(),
                ]);
            }
        }

        // Create some bookings
        $accommodation1 = Accommodation::first();
        $accommodation2 = Accommodation::skip(1)->first();

        // Get rooms for bookings
        $room1 = $accommodation1->rooms()->first();
        $room2 = $accommodation2->rooms()->first();

        Booking::create([
            'user_id' => $user1->id,
            'accommodation_id' => $accommodation1->id,
            'room_id' => $room1?->id,
            'check_in' => now()->addDays(5),
            'check_out' => now()->addDays(7),
            'guests' => 2,
            'total_price' => 90000,
            'status' => 'confirmed',
        ]);

        Booking::create([
            'user_id' => $user2->id,
            'accommodation_id' => $accommodation2->id,
            'room_id' => $room2?->id,
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(12),
            'guests' => 4,
            'total_price' => 50000,
            'status' => 'pending',
        ]);

        // Create reviews
        Review::create([
            'user_id' => $user1->id,
            'accommodation_id' => $accommodation1->id,
            'rating' => 5,
            'comment' => 'Excellent séjour ! L\'hôtel est magnifique et le service impeccable.',
            'comment_en' => 'Excellent stay! The hotel is beautiful and the service impeccable.',
        ]);

        Review::create([
            'user_id' => $user2->id,
            'accommodation_id' => $accommodation1->id,
            'rating' => 4,
            'comment' => 'Très bon hôtel, je recommande.',
            'comment_en' => 'Very good hotel, I recommend.',
        ]);

        // Update accommodation ratings
        foreach (Accommodation::all() as $acc) {
            $reviews = Review::where('accommodation_id', $acc->id)->get();
            if ($reviews->count() > 0) {
                $avgRating = $reviews->avg('rating');
                $acc->update([
                    'rating' => round($avgRating, 2),
                    'total_reviews' => $reviews->count(),
                ]);
            }
        }
    }
}

