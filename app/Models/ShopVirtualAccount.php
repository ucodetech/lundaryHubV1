<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopVirtualAccount extends Model
{
    protected $fillable = [
        'shop_id',
        'owner_id',
        'paystack_customer_id',
        'paystack_customer_code',
        'account_number',
        'account_name',
        'bank_name',
        'bank_slug',
        'bank_id',
        'paystack_account_id',
        'is_active',
        'preferred_bank',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
