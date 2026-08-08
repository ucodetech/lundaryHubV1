<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasPushSubscriptions;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'address',
        'city',
        'latitude',
        'longitude',
        'role',
        'is_active',
        'phone_verified_at',
        'referral_code',
        'bonus_balance',
        'referred_by_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'bonus_balance' => 'decimal:2',
        ];
    }

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function ownedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function shopMemberships(): HasMany
    {
        return $this->hasMany(ShopUser::class);
    }

    public function riderProfile(): HasOne
    {
        return $this->hasOne(RiderProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(UserBankAccount::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(UserBankAccount::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }
}
