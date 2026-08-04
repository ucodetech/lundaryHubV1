<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopHasActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('shop_owner')) {
            $shop = $user->ownedShops()->first();

            if ($shop && ! $shop->hasActiveSubscription()) {
                return redirect()->route('shop.subscription')->with('warning', '⚡ Your shop subscription is inactive or expired. Please subscribe to a monthly plan to unlock operational features and accept online orders!');
            }
        }

        return $next($request);
    }
}
