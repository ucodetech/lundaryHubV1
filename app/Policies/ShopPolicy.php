<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shop $shop): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('shop_owner') || $user->hasRole('super_admin');
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id || $user->hasRole('super_admin');
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id || $user->hasRole('super_admin');
    }

    public function verify(User $user, Shop $shop): bool
    {
        return $user->hasRole('super_admin');
    }
}
