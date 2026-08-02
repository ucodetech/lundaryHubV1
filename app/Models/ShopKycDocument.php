<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopKycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'document_type',
        'file_path',
        'status',
        'rejection_reason',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
