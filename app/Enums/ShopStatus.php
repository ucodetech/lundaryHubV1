<?php

namespace App\Enums;

enum ShopStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Verification',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::REJECTED => 'Rejected',
        };
    }
}
