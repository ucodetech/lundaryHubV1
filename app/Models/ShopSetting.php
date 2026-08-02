<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'opening_hours',
        'currency',
        'accepts_pickup',
        'accepts_delivery',
        'min_order_amount',
        'timezone',
        'branding',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'branding' => 'array',
            'accepts_pickup' => 'boolean',
            'accepts_delivery' => 'boolean',
            'min_order_amount' => 'decimal:2',
        ];
    }
}
