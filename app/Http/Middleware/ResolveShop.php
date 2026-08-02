<?php

namespace App\Http\Middleware;

use App\Enums\ShopStatus;
use App\Models\Shop;
use App\Services\ShopContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShop
{
    public function __construct(protected ShopContext $shopContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('shop') ?? $request->route('shop_slug') ?? $request->header('X-Shop-Slug');

        if ($slug) {
            $shop = is_string($slug)
                ? Shop::where('slug', $slug)->first()
                : ($slug instanceof Shop ? $slug : null);

            if (!$shop) {
                abort(404, 'Shop not found.');
            }

            if ($shop->status !== ShopStatus::ACTIVE && !$request->user()?->hasRole('super_admin')) {
                abort(403, 'This shop is currently inactive or pending approval.');
            }

            $this->shopContext->set($shop);
        }

        return $next($request);
    }
}
