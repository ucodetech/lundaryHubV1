<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Device;
use App\Models\User;

class UserService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'avatar' => $data['avatar'] ?? $user->avatar,
        ]);

        return $user;
    }

    public function addAddress(User $user, array $data): Address
    {
        if (!empty($data['is_default']) && $data['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        return $user->addresses()->create([
            'label' => $data['label'] ?? 'Home',
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'] ?? 'Lagos',
            'state' => $data['state'] ?? 'Lagos',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_default' => $data['is_default'] ?? false,
        ]);
    }

    public function registerDevice(User $user, string $token, string $platform = 'web'): Device
    {
        return Device::updateOrCreate(
            ['user_id' => $user->id, 'device_token' => $token],
            ['platform' => $platform, 'is_active' => true]
        );
    }
}
