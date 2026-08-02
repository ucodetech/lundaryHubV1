<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Services\ShopContext;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('shop_owner') || $user->hasRole('super_admin');
    }

    public function update(User $user, Service $service): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $service->shop_id === $currentShopId);
    }

    public function delete(User $user, Service $service): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $service->shop_id === $currentShopId);
    }
}
