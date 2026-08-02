<?php

namespace App\Models;

use App\Enums\ShopUserRole;
use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopUser extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => ShopUserRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
