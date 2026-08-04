<?php

namespace App\Services;

use App\Enums\ShopStatus;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Support\Str;

class ShopService
{
    public function create(User $owner, array $data): Shop
    {
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $count = 1;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $count++;
        }

        $shop = Shop::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'business_type' => $data['business_type'] ?? 'cac_registered',
            'logo' => $data['logo'] ?? null,
            'banner' => $data['banner'] ?? null,
            'phone' => $data['phone'] ?? $owner->phone,
            'email' => $data['email'] ?? $owner->email,
            'address' => $data['address'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'pickup_radius_km' => $data['pickup_radius_km'] ?? 10.00,
            'delivery_fee' => $data['delivery_fee'] ?? 0.00,
            'status' => ShopStatus::PENDING,
            'is_verified' => false,
            'kyc_status' => 'pending',
        ]);

        // Default Shop Settings
        ShopSetting::create([
            'shop_id' => $shop->id,
            'opening_hours' => $data['opening_hours'] ?? [
                'monday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'tuesday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'wednesday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'thursday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'friday' => ['open' => '08:00', 'close' => '18:00', 'is_open' => true],
                'saturday' => ['open' => '09:00', 'close' => '17:00', 'is_open' => true],
                'sunday' => ['open' => '10:00', 'close' => '14:00', 'is_open' => false],
            ],
            'currency' => 'NGN',
            'accepts_pickup' => true,
            'accepts_delivery' => true,
            'min_order_amount' => 0.00,
            'timezone' => 'Africa/Lagos',
        ]);

        // Add owner to shop_users
        ShopUser::create([
            'shop_id' => $shop->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        return $shop;
    }

    public function update(Shop $shop, array $data): Shop
    {
        $shop->update([
            'name' => $data['name'] ?? $shop->name,
            'description' => $data['description'] ?? $shop->description,
            'business_type' => $data['business_type'] ?? $shop->business_type,
            'logo' => $data['logo'] ?? $shop->logo,
            'banner' => $data['banner'] ?? $shop->banner,
            'phone' => $data['phone'] ?? $shop->phone,
            'email' => $data['email'] ?? $shop->email,
            'address' => $data['address'] ?? $shop->address,
            'latitude' => $data['latitude'] ?? $shop->latitude,
            'longitude' => $data['longitude'] ?? $shop->longitude,
            'pickup_radius_km' => $data['pickup_radius_km'] ?? $shop->pickup_radius_km,
            'delivery_fee' => $data['delivery_fee'] ?? $shop->delivery_fee,
        ]);

        return $shop;
    }

    public function verify(Shop $shop): Shop
    {
        $shop->update([
            'status' => ShopStatus::ACTIVE,
            'is_verified' => true,
            'kyc_status' => 'approved',
        ]);

        return $shop;
    }

    public function suspend(Shop $shop): Shop
    {
        $shop->update([
            'status' => ShopStatus::SUSPENDED,
        ]);

        return $shop;
    }
}
