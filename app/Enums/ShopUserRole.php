<?php

namespace App\Enums;

enum ShopUserRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::MANAGER => 'Store Manager',
            self::STAFF => 'Laundry Staff',
        };
    }
}
