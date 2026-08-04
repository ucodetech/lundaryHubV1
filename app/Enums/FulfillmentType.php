<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case HOME_DELIVERY = 'home_delivery';
    case STORE_PICKUP = 'store_pickup';

    public function label(): string
    {
        return match ($this) {
            self::HOME_DELIVERY => 'Doorstep Home Delivery',
            self::STORE_PICKUP => 'In-Store Self Pickup',
        };
    }
}
