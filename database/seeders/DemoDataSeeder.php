<?php

namespace Database\Seeders;

use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Price;
use App\Models\RiderProfile;
use App\Models\Service;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin Account
        $admin = User::create([
            'first_name' => 'LaundryHub',
            'last_name' => 'Admin',
            'email' => 'admin@laundryhub.ng',
            'phone' => '+2348000000001',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        // 2. Global Master Platform Templates (shop_id = null)
        $masterShirt = Category::create(['shop_id' => null, 'name' => 'Shirt (Master Template)', 'icon' => '👔', 'sort_order' => 1]);
        $masterJean = Category::create(['shop_id' => null, 'name' => 'Jean / Trousers (Master Template)', 'icon' => '👖', 'sort_order' => 2]);
        $masterNative = Category::create(['shop_id' => null, 'name' => 'Native Wear / Senator (Master Template)', 'icon' => '👘', 'sort_order' => 3]);
        $masterSuit = Category::create(['shop_id' => null, 'name' => 'Suit 2-Piece (Master Template)', 'icon' => '🥼', 'sort_order' => 4]);
        $masterDuvet = Category::create(['shop_id' => null, 'name' => 'Duvet / Bedding (Master Template)', 'icon' => '🛏️', 'sort_order' => 5]);

        $masterWash = Service::create(['shop_id' => null, 'name' => 'Wash Only', 'description' => 'Professional machine washing', 'sort_order' => 1]);
        $masterWashIron = Service::create(['shop_id' => null, 'name' => 'Wash & Iron', 'description' => 'Complete wash and steam pressing', 'sort_order' => 2]);
        $masterIronOnly = Service::create(['shop_id' => null, 'name' => 'Iron Only', 'description' => 'Steam press ironing only', 'sort_order' => 3]);
        $masterStarch = Service::create(['shop_id' => null, 'name' => 'Starch & Press', 'description' => 'Heavy starching and crisp pressing', 'sort_order' => 4]);

        // Default Master Pricing Matrix Template
        Price::create(['shop_id' => null, 'category_id' => $masterShirt->id, 'service_id' => $masterWash->id, 'amount' => 500.00]);
        Price::create(['shop_id' => null, 'category_id' => $masterShirt->id, 'service_id' => $masterWashIron->id, 'amount' => 700.00]);
        Price::create(['shop_id' => null, 'category_id' => $masterJean->id, 'service_id' => $masterWashIron->id, 'amount' => 900.00]);
        Price::create(['shop_id' => null, 'category_id' => $masterNative->id, 'service_id' => $masterStarch->id, 'amount' => 1500.00]);
        Price::create(['shop_id' => null, 'category_id' => $masterSuit->id, 'service_id' => $masterWashIron->id, 'amount' => 2500.00]);
        Price::create(['shop_id' => null, 'category_id' => $masterDuvet->id, 'service_id' => $masterWash->id, 'amount' => 3500.00]);

        // 3. Dry Cleaner / Shop Owner Account
        $owner = User::create([
            'first_name' => 'Emeka',
            'last_name' => 'Okonkwo',
            'email' => 'sparkle@laundryhub.ng',
            'phone' => '+2348023456789',
            'password' => Hash::make('password'),
            'role' => UserRole::SHOP_OWNER,
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $owner->assignRole(UserRole::SHOP_OWNER->value);

        // Demo Shop: Sparkle Laundry
        $shop = Shop::create([
            'owner_id' => $owner->id,
            'name' => 'Sparkle Dry Cleaners',
            'slug' => 'sparkle-dry-cleaners',
            'description' => 'Premium eco-friendly dry cleaning and express laundry service in Victoria Island.',
            'phone' => '+2348023456789',
            'email' => 'sparkle@laundryhub.ng',
            'address' => 'Plot 12, Adeola Odeku Street, Victoria Island, Lagos',
            'latitude' => 6.4281,
            'longitude' => 3.4219,
            'pickup_radius_km' => 15.00,
            'delivery_fee' => 1000.00,
            'status' => ShopStatus::ACTIVE,
            'is_verified' => true,
        ]);

        ShopSetting::create([
            'shop_id' => $shop->id,
            'opening_hours' => [
                'monday' => ['open' => '07:30', 'close' => '19:00', 'is_open' => true],
                'tuesday' => ['open' => '07:30', 'close' => '19:00', 'is_open' => true],
                'wednesday' => ['open' => '07:30', 'close' => '19:00', 'is_open' => true],
                'thursday' => ['open' => '07:30', 'close' => '19:00', 'is_open' => true],
                'friday' => ['open' => '07:30', 'close' => '19:00', 'is_open' => true],
                'saturday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'sunday' => ['open' => '10:00', 'close' => '15:00', 'is_open' => false],
            ],
            'currency' => 'NGN',
            'accepts_pickup' => true,
            'accepts_delivery' => true,
            'min_order_amount' => 2000.00,
            'timezone' => 'Africa/Lagos',
        ]);

        // Demo Shop Specific Categories & Services
        $shirt = Category::create(['shop_id' => $shop->id, 'name' => 'Shirt', 'sort_order' => 1]);
        $jean = Category::create(['shop_id' => $shop->id, 'name' => 'Jean / Trousers', 'sort_order' => 2]);
        $native = Category::create(['shop_id' => $shop->id, 'name' => 'Native Wear (Senator/Agbada)', 'sort_order' => 3]);
        $suit = Category::create(['shop_id' => $shop->id, 'name' => 'Suit (2-Piece)', 'sort_order' => 4]);
        $duvet = Category::create(['shop_id' => $shop->id, 'name' => 'Duvet / Bedding', 'sort_order' => 5]);

        $wash = Service::create(['shop_id' => $shop->id, 'name' => 'Wash Only', 'sort_order' => 1]);
        $washIron = Service::create(['shop_id' => $shop->id, 'name' => 'Wash & Iron', 'sort_order' => 2]);
        $ironOnly = Service::create(['shop_id' => $shop->id, 'name' => 'Iron Only', 'sort_order' => 3]);
        $starch = Service::create(['shop_id' => $shop->id, 'name' => 'Starch & Press', 'sort_order' => 4]);

        Price::create(['shop_id' => $shop->id, 'category_id' => $shirt->id, 'service_id' => $wash->id, 'amount' => 500.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $shirt->id, 'service_id' => $washIron->id, 'amount' => 650.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $jean->id, 'service_id' => $wash->id, 'amount' => 700.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $jean->id, 'service_id' => $washIron->id, 'amount' => 900.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $native->id, 'service_id' => $washIron->id, 'amount' => 1200.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $native->id, 'service_id' => $starch->id, 'amount' => 1500.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $suit->id, 'service_id' => $washIron->id, 'amount' => 2500.00]);
        Price::create(['shop_id' => $shop->id, 'category_id' => $duvet->id, 'service_id' => $wash->id, 'amount' => 3500.00]);

        // 4. Delivery Rider Account
        $riderUser = User::create([
            'first_name' => 'Babajide',
            'last_name' => 'Salami',
            'email' => 'rider@laundryhub.ng',
            'phone' => '+2348034567890',
            'password' => Hash::make('password'),
            'role' => UserRole::RIDER,
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $riderUser->assignRole(UserRole::RIDER->value);

        RiderProfile::create([
            'user_id' => $riderUser->id,
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'LND-452-XY',
            'license_number' => 'DL-98765432',
            'is_online' => true,
            'current_latitude' => 6.4300,
            'current_longitude' => 3.4200,
            'kyc_status' => 'approved',
        ]);

        // 5. Customer Account
        $customer = User::create([
            'first_name' => 'Chioma',
            'last_name' => 'Adeyemi',
            'email' => 'customer@laundryhub.ng',
            'phone' => '+2348045678901',
            'password' => Hash::make('password'),
            'role' => UserRole::CUSTOMER,
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $customer->assignRole(UserRole::CUSTOMER->value);
    }
}
