<?php

namespace App\Policies;

use App\Models\Price;
use App\Models\User;
use App\Services\ShopContext;

class PricePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Price $price): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('shop_owner') || $user->hasRole('super_admin');
    }

    public function update(User $user, Price $price): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $price->shop_id === $currentShopId);
    }

    public function delete(User $user, Price $price): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $price->shop_id === $currentShopId);
    }
}
