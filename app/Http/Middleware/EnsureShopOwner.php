<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->hasRole(UserRole::SHOP_OWNER->value) && !$user->hasRole(UserRole::SUPER_ADMIN->value))) {
            abort(403, 'Access denied. Shop owner privileges required.');
        }

        return $next($request);
    }
}
