<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'target_role',
        'price',
        'interval_days',
        'order_limit',
        'description',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'interval_days' => 'integer',
            'order_limit' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
