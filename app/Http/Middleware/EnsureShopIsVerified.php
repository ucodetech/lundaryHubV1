<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('shop_owner')) {
            $shop = $user->ownedShops()->first();

            if (! $shop) {
                return redirect()->route('shop.create')->with('error', 'Please create your shop storefront first.');
            }

            if (! $shop->is_verified || $shop->status->value !== 'active') {
                return redirect()->route('shop.kyc')->with('error', 'Operational features (Categories, Services & Pricing) are locked until Super Admin approves your shop KYC verification.');
            }
        }

        return $next($request);
    }
}
