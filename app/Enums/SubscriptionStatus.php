<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::PENDING => 'Pending Payment',
            self::CANCELLED => 'Cancelled',
        };
    }
}
