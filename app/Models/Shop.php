<?php

namespace App\Models;

use App\Enums\ShopStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'pickup_radius_km',
        'delivery_fee',
        'offers_home_delivery',
        'offers_store_pickup',
        'status',
        'is_verified',
        'business_type',
        'is_cac_verified',
        'kyc_status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
            'is_verified' => 'boolean',
            'is_cac_verified' => 'boolean',
            'offers_home_delivery' => 'boolean',
            'offers_store_pickup' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'pickup_radius_km' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ShopSetting::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(ShopKycDocument::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ShopUser::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function virtualAccount(): HasOne
    {
        return $this->hasOne(ShopVirtualAccount::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return Subscription::where(function ($query) {
                $query->where('shop_id', $this->id)->orWhere('user_id', $this->owner_id);
            })
            ->where('role', 'shop_owner')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }
}
