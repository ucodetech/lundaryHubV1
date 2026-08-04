<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'shop_id',
        'rider_id',
        'fulfillment_type',
        'is_dispatch_requested',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'pickup_address',
        'delivery_address',
        'pickup_latitude',
        'pickup_longitude',
        'is_legacy',
        'legacy_customer_name',
        'legacy_customer_phone',
        'notes',
        'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'fulfillment_type' => FulfillmentType::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'is_legacy' => 'boolean',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(DispatchBid::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(OrderDispute::class);
    }

    public function logStatusChange(string $toStatus, ?User $user = null, ?string $notes = null): void
    {
        $fromStatus = is_object($this->status) ? $this->status->value : (string) $this->status;

        OrderStatusLog::create([
            'order_id' => $this->id,
            'user_id' => $user?->id,
            'user_name' => $user ? ($user->first_name . ' ' . $user->last_name) : 'System / Webhook',
            'user_role' => $user ? ($user->getRoleNames()->first() ?? (is_object($user->role) ? $user->role->value : $user->role) ?? 'user') : 'system',
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
        ]);
    }
}
