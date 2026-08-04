<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'subscription_plan_id',
        'role',
        'plan_key',
        'plan_name',
        'amount',
        'status',
        'starts_at',
        'ends_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE &&
            ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
