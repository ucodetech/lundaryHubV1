<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Payment Pending',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
            self::FAILED => 'Payment Failed',
        };
    }
}
