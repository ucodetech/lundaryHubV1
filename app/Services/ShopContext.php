<?php

namespace App\Services;

use App\Models\Shop;

class ShopContext
{
    protected ?Shop $currentShop = null;

    public function set(Shop $shop): void
    {
        $this->currentShop = $shop;
    }

    public function get(): ?Shop
    {
        return $this->currentShop;
    }

    public function id(): ?int
    {
        return $this->currentShop?->id;
    }

    public function check(): bool
    {
        return $this->currentShop !== null;
    }

    public function clear(): void
    {
        $this->currentShop = null;
    }
}
