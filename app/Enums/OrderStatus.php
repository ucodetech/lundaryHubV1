<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PICKUP_ASSIGNED = 'pickup_assigned';
    case GARMENTS_PICKED_UP = 'garments_picked_up';
    case CLEANING_IN_PROGRESS = 'cleaning_in_progress';
    case READY_FOR_DELIVERY = 'ready_for_delivery';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::CONFIRMED => 'Order Confirmed',
            self::PICKUP_ASSIGNED => 'Pickup Rider Assigned',
            self::GARMENTS_PICKED_UP => 'Garments Picked Up',
            self::CLEANING_IN_PROGRESS => 'Cleaning in Progress',
            self::READY_FOR_DELIVERY => 'Ready for Delivery',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::READY_FOR_PICKUP => 'Ready for Store Pickup',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
