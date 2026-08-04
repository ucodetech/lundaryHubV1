<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case SUPPORT = 'support';
    case SHOP_OWNER = 'shop_owner';
    case RIDER = 'rider';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SUPPORT => 'Platform Support Staff',
            self::SHOP_OWNER => 'Dry Cleaner / Shop Owner',
            self::RIDER => 'Delivery Rider',
            self::CUSTOMER => 'Customer',
        };
    }
}
