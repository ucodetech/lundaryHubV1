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
            // Allow access to shop creation, KYC submission, and logout routes
            if ($request->routeIs('shop.kyc', 'shop.kyc.store', 'shop.create', 'shop.store', 'logout', 'profile.*')) {
                return $next($request);
            }

            $shop = $user->ownedShops()->first();

            if (! $shop) {
                return redirect()->route('shop.create')->with('error', 'Please create your digital storefront first.');
            }

            if (! $shop->is_verified || $shop->status->value !== 'active' || $shop->kyc_status !== 'approved') {
                return redirect()->route('shop.kyc')->with('warning', 'Your shop verification is pending approval. Please upload your store photos and KYC documents to request shop activation.');
            }
        }

        return $next($request);
    }
}
