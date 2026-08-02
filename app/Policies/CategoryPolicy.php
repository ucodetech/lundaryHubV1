<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Services\ShopContext;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('shop_owner') || $user->hasRole('super_admin');
    }

    public function update(User $user, Category $category): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $category->shop_id === $currentShopId);
    }

    public function delete(User $user, Category $category): bool
    {
        $currentShopId = app(ShopContext::class)->id() ?? $user->ownedShops()->first()?->id;

        return $user->hasRole('super_admin') || ($user->hasRole('shop_owner') && $category->shop_id === $currentShopId);
    }
}
