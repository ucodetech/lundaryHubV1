<?php

namespace App\Traits;

use App\Models\Shop;
use App\Services\ShopContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShop
{
    protected static function bootBelongsToShop(): void
    {
        static::addGlobalScope('shop', function (Builder $builder) {
            $shopContext = app(ShopContext::class);

            if ($shopContext->check()) {
                $builder->where($builder->getQuery()->from . '.shop_id', $shopContext->id());
            }
        });

        static::creating(function (Model $model) {
            $shopContext = app(ShopContext::class);

            if ($shopContext->check() && empty($model->shop_id)) {
                $model->shop_id = $shopContext->id();
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
